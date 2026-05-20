<?php

namespace App\Http\Controllers;

use App\Services\RajaOngkirService;
use Illuminate\Http\Request;

class RajaOngkirController extends Controller
{
    private RajaOngkirService $rajaOngkir;

    public function __construct(RajaOngkirService $rajaOngkir)
    {
        $this->rajaOngkir = $rajaOngkir;
    }

    public function searchDestination(Request $request)
    {
        $keyword = $request->query('keyword', '');
        if (strlen($keyword) < 3) {
            return response()->json([]);
        }
        return response()->json($this->rajaOngkir->searchDestination($keyword));
    }

    public function calculateCost(Request $request)
    {
        $request->validate([
            'origin' => 'required',
            'destination' => 'required',
            'weight' => 'required|numeric',
            'courier' => 'required|string',
        ]);

        try {
            $cost = $this->rajaOngkir->calculateCost(
                $request->origin,
                $request->destination,
                $request->weight,
                $request->courier
            );
            return response()->json($cost);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
