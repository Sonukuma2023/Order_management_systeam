<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportProductsJob implements ShouldQueue
{
    use Queueable;

    public string $filePath;
    public int $userId;

    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $fullPath = Storage::disk('local')->path($this->filePath);

        Log::info('Import Job Started', [
            'path' => $fullPath,
            'exists' => file_exists($fullPath)
        ]);

        if (! file_exists($fullPath)) {
            Log::error('CSV file not found', [
                'path' => $fullPath
            ]);

            return;
        }

        $handle = fopen($fullPath, 'r');

        if (! $handle) {
            Log::error('Unable to open CSV', [
                'path' => $fullPath
            ]);

            return;
        }

        $headers = fgetcsv($handle, 1000, ',');

        if (!$headers) {
            fclose($handle);
            return;
        }

        $headers = array_map('trim', $headers);

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {

            if (count($headers) !== count($data)) {
                continue;
            }

            $row = array_combine($headers, $data);

            if (empty($row['sku_code'])) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Image Download
            |--------------------------------------------------------------------------
            */

            $imageName = null;

            if (!empty($row['image'])) {

                try {

                    File::ensureDirectoryExists(
                        public_path('asset/product/images')
                    );

                    $extension = pathinfo(
                        parse_url($row['image'], PHP_URL_PATH),
                        PATHINFO_EXTENSION
                    );

                    $imageName = Str::random(20) . '.' . ($extension ?: 'jpg');

                    $response = Http::timeout(30)->get($row['image']);

                    if ($response->successful()) {

                        file_put_contents(
                            public_path(
                                'asset/product/images/' . $imageName
                            ),
                            $response->body()
                        );
                    }

                } catch (\Exception $e) {

                    Log::error('Image Download Failed', [
                        'url' => $row['image'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Product::updateOrCreate(
                [
                    'sku_code' => trim($row['sku_code']),
                ],
                [
                    'name' => $row['name'] ?? '',
                    'image' => $imageName,
                    'category' => $row['category'] ?? null,
                    'price' => (float) ($row['price'] ?? 0),
                    'description' => $row['description'] ?? null,
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'status' => $row['status'] ?? 'active',
                    'move_to_shopify' => 0,
                    'auth_id' => $this->userId,
                ]
            );
        }

        fclose($handle);

        Log::info('Import Job Finished');
    }
}