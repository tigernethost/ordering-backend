<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\LalamoveController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\LalamoveMetaData;
use Illuminate\Support\Str;

class UpdateLalamoveData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lalamove:update-meta';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and cache cities and vehicle types from Lalamove';

    private $apiSecret;
    private $apiKey;
    private $country;
    private $baseUrl;

    public function __construct()
    {
        parent::__construct();

        $this->apiKey = config('services.lalamove.key');
        $this->apiSecret = config('services.lalamove.secret');
        $this->country = config('services.lalamove.country');
        $this->baseUrl = config('services.lalamove.base_url');
    }
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cityUrl = config('services.lalamove.base_url') . '/v3/cities';

        $this->updateCity();
        $this->info('Lalamove meta data updated.');
    }

    protected function updateCity()
    {
        $method = 'GET';
        $path = '/v3/cities';
    
        $lalamoveController = new LalamoveController();
        $signatureData = $lalamoveController->generateSignature($method, $path);
    
        $response = Http::withHeaders([
            'Authorization' => "hmac {$this->apiKey}:{$signatureData['timestamp']}:{$signatureData['signature']}",
            'Market' => $this->country,
            'Request-ID' => (string) Str::uuid(),
        ])->get("{$this->baseUrl}{$path}");
    
        \Log::info("Lalamove City Data Response", [
            'response' => $response->status(),
            'signature' => $signatureData
        ]);
    
        if (!$response->successful()) {
            $this->error("Failed to fetch city metadata from Lalamove.");
            return;
        }
    
        $cities = $response->json()['data'] ?? [];
    
        foreach ($cities as $city) {
            $locode = $city['locode'];
            $locationName = $city['name'];
    
            foreach ($city['services'] as $service) {
                $vehicleKey = $service['key'];
            
                LalamoveMetaData::updateOrCreate(
                    [
                        'locode' => $locode,
                        'vehicle_key' => $vehicleKey,
                    ],
                    [
                        'location_name' => $locationName,
                        'description' => $service['description'] ?? null,
                        'load' => $service['load'] ?? [],
                        'dimensions' => $service['dimensions'] ?? [],
                        'special_requests' => $service['specialRequests'] ?? [],
                        'delivery_item_specification' => $service['deliveryItemSpecification'] ?? [],
                        'icon' => $this->getVehicleIcon($vehicleKey),
                    ]
                );
            }            
        }
    
        $this->info('Lalamove vehicle metadata updated successfully.');
    }

    private function getVehicleIcon(string $key): string
    {
        $map = [
            'MOTORCYCLE' => 'fas fa-motorcycle',
            'SEDAN' => 'fas fa-car',
            'SEDAN_INTERCITY' => 'fas fa-car',
            'MPV' => 'fas fa-shuttle-van',
            'MPV_INTERCITY' => 'fas fa-shuttle-van',
            'VAN' => 'fas fa-van-shuttle',
            'VAN_INTERCITY' => 'fas fa-van-shuttle',
            'PICKUP_800KG_INTERCITY' => 'fas fa-truck-pickup',
            'TRUCK330' => 'fas fa-truck',
            '2000KG_ALUMINUM' => 'fas fa-truck-monster',
            '2000KG_ALUMINUM_LD' => 'fas fa-truck-monster',
            '2000KG_FB' => 'fas fa-truck-moving',
            '2000KG_FB_LD' => 'fas fa-truck-moving',
            '10WHEEL_TRUCK' => 'fas fa-truck-loading',
            'LD_10WHEEL_TRUCK' => 'fas fa-truck-loading',
        ];

        return $map[$key] ?? 'fas fa-shipping-fast';
    }

    

}
