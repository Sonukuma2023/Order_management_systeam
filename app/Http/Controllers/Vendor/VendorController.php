<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;



class VendorController extends Controller
{

    public function vendordash(Request $request)
    {

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $auth_id = Auth::id();

        $product_count = Product::where('auth_id', $auth_id)
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->count();

        $orderdata = $this->get_orders($startDate,$endDate);

        $userProductIds = Product::where('auth_id', $auth_id)
            ->pluck('shopify_id')
            ->toArray();

        $order_count = 0;
        $total_sales = 0;
        $result = [];

       if (!empty($orderdata['draft_orders'])) {
    foreach ($orderdata['draft_orders'] as $order) {
        $hasMatch = false;

        if (!empty($order['line_items'])) {
            foreach ($order['line_items'] as $item) {
                $productId = $item['product_id'] ?? null;

                // Check if product belongs to logged user
                if (in_array($productId, $userProductIds)) {
                    $hasMatch = true;

                    // FIX: Get the individual item price, fallback to calculated price if missing
                    $itemPrice = isset($item['price']) ? (float)$item['price'] : 0;
                    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;

                    // Calculate actual sales for this specific vendor item
                    $itemTotalSales = $itemPrice * $quantity;
                    $total_sales += $itemTotalSales;

                    $result[] = [
                        'order' => $order['name'] ?? null,
                        'title' => $item['title'] ?? null,
                        'name'  => $item['name'] ?? null,
                        'price' => $itemTotalSales, // Send the item total to the chart array
                    ];
                }
            }
        }

        // Count unique orders for this vendor
        if ($hasMatch) {
            $order_count++;
        }
    }
}

        return Inertia::render('VendorDashboard', [
            'product_count' => $product_count,
            'order_count'   => $order_count,
            'total_sales'   => $total_sales,
            'result'              =>$result,
            'server_filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function vendorproduct()
    {
        $category    = Category::select('id', 'name')->get();
        $auth_id = Auth::id();
        $products = Product::where('auth_id',$auth_id)->latest()->paginate(10);
        
        return Inertia::render('Vendor/Products',compact('category','products'));
    }

    // ✅ New Method for Orders
    // public function vendororders()
    // {
    //    return Inertia::render('Vendor/Orders');
    // }

    public function edit_profile()
    {
        return  'proile';
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

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            // Ignore unique check for the current product id
            'sku_code'    => 'required|string|unique:products,sku_code,' . $id,
            'category'    => 'required|string|exists:categories,name',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:0',
            'status'      => 'required|in:active,inactive',
            'description' => 'nullable|string', 
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image && file_exists(public_path('asset/product/images/' . $product->image))) {
                unlink(public_path('asset/product/images/' . $product->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('asset/product/images'), $imageName);
            $validated['image'] = $imageName;
        } else {
            // Keep the existing image if no new one is uploaded
            unset($validated['image']);
        }

        if (!empty($product->shopify_id)) {

            $this->shopify_products_update($product->shopify_id, $validated);
        }

        $product->update($validated);

        return redirect()->back()->with('success', 'Product updated successfully!');
    }
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if (!empty($product->shopify_id)) {
                $this->shopify_product_delete($product->shopify_id);
            }

        if ($product->image && file_exists(public_path('asset/product/images/' . $product->image))) {
            unlink(public_path('asset/product/images/' . $product->image));
        }

        $product->delete();

        return redirect()->back()->with('success', 'Product deleted successfully!');
    }
    public function update_profile(Request $request)
    {
        $user = $request->user();

        // 1. Validate the request
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // 2. Update Name and Email
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

       
        $user->save();

        return back();
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


     public function get_orders($start = null, $end = null)
    {
        $baseUrl = env('SHOPIFY_URL') . '/admin/api/2026-01/draft_orders.json';
        
        $queryParams = [];
        if ($start) {
            
            $queryParams['updated_at_min'] = Carbon::parse($start)->startOfDay()->toIso8601String();
        }
        
        if ($end) {
            $queryParams['updated_at_max'] = Carbon::parse($end)->endOfDay()->toIso8601String();
        }

        $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
        $fullUrl = $baseUrl . $queryString;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // Don't leave this at 0 in production
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'X-Shopify-Access-Token:  ' . env('SHOPIFY_TOKEN'),
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);
        
        // Check for cURL errors
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return ['error' => $error_msg];
        }

        curl_close($curl);

        return json_decode($response, true);
    }

    public function vendororders()
    {
        $data = $this->get_orders();
        $result = [];
        $productIds = [];

        if (!empty($data['draft_orders'])) {
            foreach ($data['draft_orders'] as $order) {
                $orderId = $order['id'] ?? null;
                $orderName = $order['name'] ?? null;
                $status = $order['status'] ?? 'Processing'; // Default fallback status
                $createdAt = $order['created_at'] ?? null;

                $customer = $order['customer'] ?? [];
                $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                $email = $customer['email'] ?? null;

                if (!empty($order['line_items'])) {
                    foreach ($order['line_items'] as $item) {
                        $price = isset($item['price']) ? (float)$item['price'] : 0;
                        $productName = $item['title'] ?? null;
                        $productId = $item['product_id'] ?? null;
                        
                        if ($productId) {
                            $productIds[] = $productId;
                        }

                        $result[] = [
                            'status'        => $status,
                            'customer_name' => $customerName ?: 'Guest Customer',
                            'email'         => $email,
                            'order_id'      => $orderId,
                            'order_name'    => $orderName,
                            'product_id'    => $productId,
                            'product_name'  => $productName,
                            'price'         => $price,
                            'date'          => $createdAt ? date('M d, Y', strtotime($createdAt)) : 'N/A', // Format date on backend
                        ];
                    }
                }
            }
        }

        $productIds = array_unique($productIds);

        $vendorProductIds = Product::whereIn('shopify_id', $productIds)
            ->where('auth_id', Auth::id())
            ->pluck('shopify_id')
            ->toArray();

        
        $filteredOrders = array_values(array_filter($result, function($order) use ($vendorProductIds) {
            return in_array($order['product_id'], $vendorProductIds);
        }));

        
        return Inertia::render('Vendor/Orders', [
            'orders' => $filteredOrders
        ]);
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

            // ✅ CURL ERROR
            if (curl_errno($curl)) {
                \Log::error('Shopify Delete Curl Error: ' . curl_error($curl));
            }

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            // ✅ LOG RESPONSE
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

            // ✅ IMAGE UPDATE (BASE64)
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



}