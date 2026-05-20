<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Tampilkan halaman chat. Menerima optional ?user_id dan ?product_id
     */
    public function index(Request $request)
    {
        $user = $request->user();

        try {
            // 1. Dapatkan daftar kontak/percakapan terakhir
            // Menggunakan MongoDB Aggregation Pipeline untuk mengambil 1 pesan terakhir (O(1)) per room_id secara instan.
            $latestMessages = ChatMessage::raw(function ($collection) use ($user) {
                $userId = (int) $user->id;
                return $collection->aggregate([
                    ['$match' => ['$or' => [['sender_id' => $userId], ['receiver_id' => $userId]]]],
                    ['$sort' => ['created_at' => -1]],
                    ['$group' => [
                        '_id' => '$room_id',
                        'original' => ['$first' => '$$ROOT']
                    ]],
                    ['$replaceRoot' => ['newRoot' => '$original']],
                    ['$sort' => ['created_at' => -1]]
                ]);
            });
            
            // Konversi BSON Array kembali ke Model Eloquent Laravel
            $latestMessages = ChatMessage::hydrate(iterator_to_array($latestMessages));

            $contacts = [];
            foreach ($latestMessages as $msg) {
                $otherUserId = $msg->sender_id === $user->id ? $msg->receiver_id : $msg->sender_id;
                $otherUser = User::find($otherUserId);
                
                if ($otherUser) {
                    $contacts[] = [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'avatar' => $otherUser->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($otherUser->name) . '&background=f3f4f6&color=43552c',
                        'last_message' => $msg->message,
                        'created_at' => $msg->created_at->diffForHumans(),
                        'is_read' => $msg->is_read,
                        'unread_count' => ChatMessage::where('sender_id', $otherUserId)
                            ->where('receiver_id', $user->id)
                            ->where('is_read', false)
                            ->count(),
                    ];
                }
            }

            // Sort contacts by latest message (desc)
            usort($contacts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: -1);

            // Jika ada query parameter untuk mulai chat baru
            $selectedUserId = $request->query('user_id');
            $productId = $request->query('product_id');

            $activeProduct = null;
            if ($productId) {
                $productModel = Product::find($productId);
                if ($productModel) {
                    $activeProduct = [
                        'id' => $productModel->id,
                        'name' => $productModel->nama_barang,
                        'price' => $productModel->harga,
                        'img' => $productModel->foto_barang_url,
                    ];
                }
            }

            return Inertia::render('Chat/Index', [
                'contacts' => collect($contacts)->values()->all(),
                'initial_selected_user_id' => $selectedUserId ? (int) $selectedUserId : null,
                'initial_product' => $activeProduct,
            ]);

        } catch (\Exception $e) {
            Log::error('Chat Index Error: ' . $e->getMessage());
            // Safe fallback UI data if database crashes entirely
            return Inertia::render('Chat/Index', [
                'contacts' => [],
                'initial_selected_user_id' => null,
                'initial_product' => null,
                'error' => 'Gagal memuat pesan. Hubungi administrator.'
            ]);
        }
    }

    /**
     * Ambil riwayat chat dengan user spesifik.
     */
    public function show(Request $request, $otherUserId)
    {
        $userId = $request->user()->id;

        // Mark as read
        ChatMessage::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $roomId = ChatMessage::generateRoomId((int)$userId, (int)$otherUserId);
        $messages = ChatMessage::where('room_id', $roomId)
            ->with(['product', 'sender:id,name'])
            ->orderBy('created_at', 'asc') // chronological
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'message' => $msg->message,
                    'is_read' => $msg->is_read,
                    'created_at' => $msg->created_at->toISOString(),
                    'time' => $msg->created_at->format('H:i'),
                    'product_id' => $msg->product_id,
                    'product' => $msg->product ? [
                        'id' => $msg->product->id,
                        'name' => $msg->product->nama_barang,
                        'price' => $msg->product->harga,
                        'img' => $msg->product->foto_barang_url,
                    ] : null,
                ];
            });

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Kirim pesan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'message' => 'required|string|max:1000',
        ]);

        try {
            $roomId = ChatMessage::generateRoomId((int)$request->user()->id, (int)$validated['receiver_id']);

            $message = ChatMessage::create([
                'room_id' => $roomId,
                'sender_id' => $request->user()->id,
                'receiver_id' => $validated['receiver_id'],
                'product_id' => $validated['product_id'] ?? null,
                'message' => $validated['message'],
                'is_read' => false,
            ]);

            // Eager load for broadcast
            $message->load(['product', 'sender']);

            try {
                // Broadcast event automatically dispatches to pusher/reverb
                broadcast(new MessageSent($message))->toOthers();
            } catch (\Exception $e) {
                Log::error('Reverb Broadcast Error: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'message' => $message->message,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->toISOString(),
                    'time' => $message->created_at->format('H:i'),
                    'product_id' => $message->product_id,
                    'product' => $message->product ? [
                        'id' => $message->product->id,
                        'name' => $message->product->nama_barang,
                        'price' => $message->product->harga,
                        'img' => $message->product->foto_barang_url,
                    ] : null,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Chat Store Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim pesan',
            ], 500);
        }
    }
}
