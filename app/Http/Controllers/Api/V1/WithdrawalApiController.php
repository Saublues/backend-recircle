<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SellerBalance;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WithdrawalApiController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bankCode' => 'required|string',
            'accountNumber' => 'required|string'
        ]);

        $amount = (float) $validated['amount'];
        $user = $request->user();

        try {
            return DB::transaction(function () use ($user, $amount, $validated) {
                // 1. Calculate Available Balance (dilepas + ditarik -> ditarik is negative, so it deducts)
                $availableBalance = SellerBalance::where('user_id', $user->id)
                    ->whereIn('status', ['dilepas', 'ditarik'])
                    ->sum('jumlah');

                if ($availableBalance < $amount) {
                    return $this->errorResponse('Saldo tidak mencukupi untuk penarikan ini.', 400);
                }

                $referenceNo = 'WD-' . date('YmdHis') . '-' . Str::random(4);

                // 2. Create Transaction record (type withdrawal, status pending - wait, we just use ditarik)
                $withdrawalRecord = SellerBalance::create([
                    'user_id' => $user->id,
                    'jumlah' => -$amount, // Negative amount to deduct
                    'status' => 'ditarik', // Mark as withdrawn
                    'catatan' => "Penarikan ke {$validated['bankCode']} ({$validated['accountNumber']}) - Ref: {$referenceNo}",
                    'ditarik_at' => now(),
                ]);

                // 3. Call Midtrans IRIS Create Payout API
                $irisKey = config('services.midtrans.iris_api_key', env('MIDTRANS_SERVER_KEY')); // Fallback to server key if iris key isn't set
                
                // Note: In production, you would hit the production IRIS URL. 
                // Using Sandbox URL for standard implementation
                $response = Http::withBasicAuth($irisKey, '')
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://app.sandbox.midtrans.com/iris/api/v1/payouts', [
                        'payouts' => [
                            [
                                'beneficiary_name' => $user->name,
                                'beneficiary_account' => $validated['accountNumber'],
                                'beneficiary_bank' => $validated['bankCode'],
                                'beneficiary_email' => $user->email,
                                'amount' => (string) $amount,
                                'notes' => 'Withdrawal ReCircle - ' . $referenceNo,
                                'reference_no' => $referenceNo // CRITICAL: Must be passed for tracking!
                            ]
                        ]
                    ]);

                // 4. Handle Midtrans Response
                if ($response->successful()) {
                    return $this->successResponse([
                        'reference_no' => $referenceNo,
                        'iris_response' => $response->json(),
                    ], 'Penarikan dana sedang diproses.', 201);
                }

                // If Midtrans fails, rollback the transaction by throwing exception
                throw new \Exception('Gagal terhubung ke gateway pembayaran: ' . $response->body());
            });
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
