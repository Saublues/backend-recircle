<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.key');
        $this->baseUrl = 'https://rajaongkir.komerce.id/api/v1';
    }

    /**
     * Search Destination (Autocomplete)
     */
    public function searchDestination(string $keyword): array
    {
        $response = Http::withHeaders(['key' => $this->apiKey])
            ->get("{$this->baseUrl}/destination/domestic-destination", [
                'search' => $keyword,
                'limit' => 20,
                'offset' => 0
            ]);
            
        if ($response->failed()) Log::error('RO Komerce Error searchDestination', $response->json());
        
        // Komerce returns data array
        return $response->json('data') ?? [];
    }

    /**
     * Calculate Cost (Form UrlEncoded)
     */
    public function calculateCost(int|string $origin, int|string $destination, int $weight, string $courier): array
    {
        $response = Http::asForm()->withHeaders(['key' => $this->apiKey])
            ->post("{$this->baseUrl}/calculate/domestic-cost", [
                'origin'      => $origin,
                'destination' => $destination,
                'weight'      => $weight,
                'courier'     => strtolower($courier)
            ]);

        if ($response->failed()) {
            Log::error('RO Komerce Error calculateCost', $response->json());
            throw new \Exception("Gagal menghitung ongkir: " . ($response->json('meta.message') ?? 'Unknown Error'));
        }

        return $response->json('data') ?? [];
    }
}
