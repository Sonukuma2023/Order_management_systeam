<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
       
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
       
        $data = $this->get_orders($startDate, $endDate);
        $salesData = $this->calculateTotalSales($startDate, $endDate);
        
        $totalsale = 0;
        $result = [];

        if (!empty($data['draft_orders'])) {
            foreach ($data['draft_orders'] as $order) {
                 $totalPrice = isset($order['total_price']) ? (float)$order['total_price'] : 0;
                if (!empty($order['line_items'])) {
                    foreach ($order['line_items'] as $item) {
                        // $price = isset($item['price']) ? (float)$item['price'] : 0;

                        $result[] = [
                            'order' => $order['name'] ?? null,
                            'title' => $item['title'] ?? null,
                            'name'  => $item['name'] ?? null,
                            'price' => $totalPrice,
                        ];
                        $totalsale += $totalPrice;
                    }
                }
            }
        }

        $product_active_count = Product::where('status', 'active')
            ->when($startDate, fn($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->count();
        $user_count = User::where('role','vendor')->count();

        return Inertia::render('Dashboard', [
            'totalsale' => $totalsale,
            'product_active_count' => $product_active_count,
            'result' => $result, 
            'user_count' => $user_count,
            'server_filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
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
        
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return ['error' => $error_msg];
        }

        curl_close($curl);

        return json_decode($response, true);
    }

    public function orders()
    {
        $data = $this->get_orders();
        $result = [];
        if (!empty($data['draft_orders'])) {
            foreach ($data['draft_orders'] as $order) {
                $orderId = $order['id'] ?? null;
                $orderName = $order['name'] ?? null;
                $status = $order['status'] ?? null;
                $createdAt = $order['created_at'] ?? null;

                $customer = $order['customer'] ?? [];
                $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                $email = $customer['email'] ?? null;

                if (!empty($order['line_items'])) {
                    foreach ($order['line_items'] as $item) {
                        $price = isset($item['price']) ? (float)$item['price'] : 0;
                        $productName = $item['title'] ?? null;

                        $result[] = [
                            'status'        => $status,
                            'customer_name' => $customerName,
                            'email'         => $email,
                            'order_id'      => $orderId,
                            'order_name'    => $orderName,
                            'product_name'  => $productName,
                            'price'         => $price,
                            'date'          => $createdAt,
                        ];
                    }
                }
            }
        }

        return Inertia::render('Orderscomponets', compact('result'));
    }
    public function getcustomer()
    {
        // Change ->get() to ->paginate(10)
        $users = User::where('role', 'Vendor')->paginate(10);
        
        return Inertia::render('Usercomponets', [
            'users' => $users
        ]);
    }

   public function update_user(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $user->update($request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]));

        return redirect()->back()->with('success', 'User updated successfully');
    }



   public function destroy_user($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'User deleted successfully');
    }

    public function calculateTotalSales($startDate = null, $endDate = null)
    {
        $data = $this->get_orders($startDate, $endDate);

        $totalsale = 0;
        $result = [];

        if (!empty($data['draft_orders'])) {

            foreach ($data['draft_orders'] as $order) {

                $totalPrice = isset($order['total_price'])
                    ? (float)$order['total_price']
                    : 0;    

                if (!empty($order['line_items'])) {

                    foreach ($order['line_items'] as $item) {

                        $result[] = [
                            'order' => $order['name'] ?? null,
                            'title' => $item['title'] ?? null,
                            'name'  => $item['name'] ?? null,
                            'price' => $totalPrice,
                        ];

                        $totalsale += $totalPrice;
                    }
                }
            }
        }

        return [
            'totalsale' => $totalsale,
            'result' => $result
        ];
    }



     
}