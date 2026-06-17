<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Inertia\Inertia;
use App\Models\Category;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $product_data = Product::latest()->paginate(10);
        
        $category    = Category::select('id', 'name')->get();
        return Inertia::render('ProductComponent',compact('product_data','category'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'sku_code'         => 'required|string|unique:products,sku_code',
            'category'    => 'required|string|exists:categories,name',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:0',
            'status'      => 'required|in:active,inactive',
            'description' => 'nullable|string', 
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB Max
        ]);
        $validated['auth_id'] = Auth::id();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            $image->move(public_path('asset/product/images'), $imageName);

            $validated['image'] = $imageName;
        }
         
        $shopifyResponse = $this->shopify_products_create($validated);

        if (isset($shopifyResponse['product']['id'])) {
            $validated['shopify_id'] = $shopifyResponse['product']['id'];
        }

        $data = Product::create($validated);
        return redirect()->back()->with('success', 'Product created successfully!');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'sku_code'    => 'required|string|unique:products,sku_code,' . $product->id,
            'category'    => 'required|string|exists:categories,name',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:0',
            'status'      => 'required|in:active,inactive',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
         
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('asset/product/images'), $imageName);
            $validated['image'] = $imageName;
        } else {
           $validated['image'] = $product->image;
        }
    
        if (!empty($product->shopify_id)) {

            $this->shopify_products_update($product->shopify_id, $validated);
           
        }

       $data =  $product->update($validated);
       
        return redirect()->back()->with('success', 'Product updated successfully!');
    }
    public function destroy(Product $product)
    {
        try {

            if (!empty($product->shopify_id)) {
                $this->shopify_product_delete($product->shopify_id);
            }
            $product->delete();

            return redirect()->back()->with('success', 'Product deleted successfully!');

        } catch (\Exception $e) {

            \Log::error('Delete Error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Delete failed!');
        }
    }
    public function webhook_create_products(Request $request)
    {
        try {
           
            $data = $request->all();
            $variant = $data['variants'][0] ?? [];
            $shopifyId = $data['id'] ?? null;
            $imageUrl = $data['image']['src'] ?? ($data['images'][0]['src'] ?? null);
            $imageName = null;

            if ($imageUrl) {
                $folderPath = public_path('asset/product/images');

                if (!File::exists($folderPath)) {
                    File::makeDirectory($folderPath, 0755, true);
                }

                $imageName = time() . '_' . Str::random(5) . '.jpg';
                $response = Http::get($imageUrl);

                if ($response->successful()) {
                    File::put($folderPath . '/' . $imageName, $response->body());
                } else {
                    \Log::warning("Failed to download image: {$imageUrl}");
                    $imageName = null;
                }
            }

           
            $sku = $variant['sku'] ?? Str::random(6);

            Product::updateOrCreate(
                ['sku_code' => $sku],
                [
                    'name'        => $data['title'] ?? null,
                    'description' => $data['body_html'] ?? null,
                    'price'       => $variant['price'] ?? 0,
                    'quantity'    => $variant['inventory_quantity'] ?? 0,
                    'sku_code'    => $sku,
                    'status'      => $data['status'] ?? 'active',
                    'category'    => $data['category']['name'] ?? 'Default',
                    'image'       => $imageName,
                    'shopify_id'  => $shopifyId,
                    'auth_id'     => 28,
                ]
            );

         
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
             
            \Log::error('Webhook Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function webhook_update_products(Request $request)
    {
        try { 
            $data = $request->all();
            $variant = $data['variants'][0] ?? [];

            $shopifyId = $data['id'] ?? null;
            $product = Product::where('shopify_id', $shopifyId)->first();

            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            $imageUrl = $data['image']['src'] ?? ($data['images'][0]['src'] ?? null);

            $imageName = $product->image;

            if ($imageUrl) {

                $folderPath = public_path('asset/product/images');

                if (!File::exists($folderPath)) {
                    File::makeDirectory($folderPath, 0755, true);
                }

                $imageName = time() . '_' . Str::random(5) . '.jpg';

                $imageContent = Http::get($imageUrl)->body();

                File::put($folderPath . '/' . $imageName, $imageContent);
            }

            // ✅ Update product
            $product->update([
                'name'        => $data['title'] ?? $product->name,
                'description' => $data['body_html'] ?? $product->description,
                'price'       => $variant['price'] ?? $product->price,
                'quantity'    => $variant['inventory_quantity'] ?? $product->quantity,
                'status'      => $data['status'] ?? $product->status,
                'category'    => $data['category']['name'] ?? $product->category,
                'image'       => $imageName,    
            ]);

            return response()->json(['status' => 'updated'], 200);

        } catch (\Exception $e) {

            \Log::error('Update Webhook Error: ' . $e->getMessage());

            return response()->json(['error' => 'failed'], 500);
        }
    }
    public function webhook_delete_products(Request $request)
    {
        try {

            $data = $request->all();
            $shopifyId = $data['id'] ?? null;

            if (!$shopifyId) {
                return response()->json(['error' => 'Shopify ID missing'], 400);
            }
            $product = Product::where('shopify_id', $shopifyId)->first();

            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }
            if ($product->image) {
                $imagePath = public_path('asset/product/images/' . $product->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $product->delete();

            return response()->json(['status' => 'deleted'], 200);

        } catch (\Exception $e) {

            \Log::error('Delete Webhook Error: ' . $e->getMessage());

            return response()->json(['error' => 'failed'], 500);
        }
    }

    public function shopify_products_create($validated)
    {
        $productData = [
            "product" => [
                "title" => $validated['name'],
                "body_html" => $validated['description'] ?? '',
                "vendor" => "My Store",
                "product_type" => $validated['category'],
                "status" => $validated['status'],
                "variants" => [
                    [
                        "price" => (string)$validated['price'],
                        "sku" => $validated['sku_code'],
                        "inventory_quantity" => (int)$validated['quantity']
                    ]
                ]
            ]
        ];
        if (!empty($validated['image'])) {

            $imagePath = public_path('asset/product/images/' . $validated['image']);

            if (file_exists($imagePath)) {

                $imageBase64 = base64_encode(file_get_contents($imagePath));

                $productData['product']['images'] = [
                    [
                        "attachment" => $imageBase64,
                        "filename"   => $validated['image']
                    ]
                ];
            }
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('SHOPIFY_URL') . '/admin/api/2026-01/products.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($productData),
            CURLOPT_HTTPHEADER => [
                'X-Shopify-Access-Token: ' . env('SHOPIFY_TOKEN'),
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            \Log::error('Shopify Curl Error: ' . curl_error($curl));
        }

        curl_close($curl);

        return json_decode($response, true);
    }

    public function shopify_products_update($shopifyId, $validated)
    {
        try {

            $productData = [
                "product" => [
                    "id" => $shopifyId,
                    "title" => $validated['name'],
                    "body_html" => $validated['description'] ?? '',
                    "product_type" => $validated['category'],
                    "status" => $validated['status'],
                    "variants" => [
                        [
                            "price" => (string)$validated['price'],
                            "sku" => $validated['sku_code']
                        ]
                    ]
                ]
            ];
            if (!empty($validated['image'])) {

                $imagePath = public_path('asset/product/images/' . $validated['image']);

                if (file_exists($imagePath)) {

                    $imageBase64 = base64_encode(file_get_contents($imagePath));

                    $productData['product']['images'] = [
                        [
                            "attachment" => $imageBase64,
                            "filename"   => $validated['image']
                        ]
                    ];
                }
            }

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => env('SHOPIFY_URL') . '/admin/api/2024-01/products/' . $shopifyId . '.json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT', // ✅ UPDATE METHOD
                CURLOPT_POSTFIELDS => json_encode($productData),
                CURLOPT_HTTPHEADER => [
                    'X-Shopify-Access-Token: ' . env('SHOPIFY_TOKEN'),
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                \Log::error('Shopify Update Error: ' . curl_error($curl));
            }

            curl_close($curl);

            return json_decode($response, true);

        } catch (\Exception $e) {

            \Log::error('Shopify Update Exception: ' . $e->getMessage());

            return false;
        }
    }
    public function shopify_product_delete($shopifyId)
    {
        try {   
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => env('SHOPIFY_URL') . '/admin/api/2024-01/products/' . $shopifyId . '.json',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => [
                    'X-Shopify-Access-Token: ' . env('SHOPIFY_TOKEN'),
                    'Content-Type: application/json'
                ],
            ]);
            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                \Log::error('Shopify Delete Curl Error: ' . curl_error($curl));
            }
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            \Log::info('Shopify Delete Response', [
                'shopify_id' => $shopifyId,
                'status' => $httpCode,
                'response' => $response
            ]);
            return $httpCode == 200;
        } catch (\Exception $e) {
            \Log::error('Shopify Delete Exception: ' . $e->getMessage());
            return false;
        }
    }

}
