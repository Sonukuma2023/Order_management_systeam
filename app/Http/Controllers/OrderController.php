<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;

class OrderController extends Controller
{
    public function get_orders()
    {

       $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('SHOPIFY_URL') . '/admin/api/2025-10/draft_orders.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'X-Shopify-Access-Token: ' . env('SHOPIFY_TOKEN'),
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);
        $totalsale = 0;
        $result = [];

        if (!empty($data['draft_orders'])) {

            foreach ($data['draft_orders'] as $order) {

                if (!empty($order['line_items'])) {

                    foreach ($order['line_items'] as $item) {

                        $price = isset($item['price']) ? (float)$item['price'] : 0;

                        $result[] = [
                            'order' => $order['name'] ?? null,
                            'title' => $item['title'] ?? null,
                            'name'  => $item['name'] ?? null,
                            'price' => $price,
                        ];

                        $totalsale += $price;
                    }
                }
            }
        }
        $product_active_count    = Product::where('status', 'active')->count();
        
        return Inertia::render('Dashboard', [
            'totalsale' => $totalsale,
            'product_active_count' => $product_active_count,
            'result' => $result
        ]);

    }
}
