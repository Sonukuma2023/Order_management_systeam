<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
// use App\Models\Order;
use App\Http\Controllers\DashboardController;


class ChatbotController extends Controller
{
    public function handleQuery(Request $request)
    {

        $userController = new DashboardController();

        $salesData = $userController->calculateTotalSales();

        $total = $salesData['totalsale'];
        $total_order = $salesData['result'];
       
        $totalOrderCount = count($total_order);
        $orderList = collect($total_order)->map(function($order) {
            return "- Order ID: {$order['order']}, Title: {$order['title']},Product Name: {$order['name']}, Price: ₹{$order['price']}";
        })->implode("\n");


        $request->validate([
            'question' => 'required|string|max:250'
        ]);

        $adminQuestion = $request->input('question');

        $totalCustomers = User::where('role','vendor')->count();
        $totalCategories = Category::count();
        $activeProducts  = Product::where('status', 'active')->count();
        $totalProducts   = Product::count();
        $totalSales      = $total;
        $productList = Product::select  ('name', 'price', 'quantity', 'status')
            ->get()
            ->map(function($product) {
                return "- {$product->name}: Price ₹{$product->price}, Stock/Qty: {$product->quantity}, Status: {$product->status}";
            })->implode("\n");

        $customerList = User::select('name', 'email', 'phone', 'role')
            ->where('role', 'vendor')
            ->latest()
            ->take(50)
            ->get()
            ->map(function($user) {
                return "- Name: {$user->name}, Email: {$user->email}, Phone: {$user->phone}";
            })
            ->implode("\n");
        $categoryList = Category::select('name')
            ->get()
            ->map(function($cat) {
                return "- {$cat->name}";
            })->implode("\n");
        
        
        $systemInstructions = "
            You are the highly intelligent assistant for this store's Admin Dashboard.
            The administrator will ask general metric questions OR specific detail lookups about individual products and customers. 
            They might use messy syntax, bad grammar, or typos. Understand their true intent and reply with a flawless, natural sentence.

            OVERVIEW METRICS:
            - Total Customers / Registered Users: {$totalCustomers}
            - Total Product Categories: {$totalCategories}
            - Active Products: {$activeProducts}
            - Total Products in Catalog: {$totalProducts}
            - Total Sales Financial Revenue: ₹" . number_format($totalSales, 2) . "
            - Total Orders Placed: {$totalOrderCount}

            AVAILABLE PRODUCT INVENTORY DETAILS:
            {$productList}

            AVAILABLE CUSTOMER DIRECTORY LOGS:
            {$customerList}

            AVAILABLE CUSTOMER DIRECTORY LOGS:
            {$categoryList}

            AVAILABLE ORDER RECOGNITION DETAILS:
            {$orderList}

            RULES:
            - Use the specific product list to answer questions about a product's price, inventory status, or quantity.
            - Use the customer logs to verify if a user exists or to find their profile details.
            - If an admin asks for a product or customer that is not listed in the data above, state clearly and politely that you couldn't find a record matching that specific identifier in the system database.
            - Keep responses clear, professional, concise, and straight to the point. Do not mention that a system prompt or context list was provided to you.
            - CRITICAL FORMATTING RULE: Do NOT use markdown formatting characters like asterisks (** or *), hashes (#), or bullet points in your output. 
            - If the user asks for a list (like users, products, or categories), separate the items using clean text formatting, commas, or standard line breaks (\n). Do NOT prepend lists with asterisks or dashes.
            - Keep responses clear, professional, concise, and straight to the point.
            CRITICAL FORMATTING RULE: 
            - Do NOT use markdown formatting characters like asterisks (** or *), hashes (#), or markdown table pipelines (|).
            - IF THE ADMIN ASKS FOR A LIST (like orders, customers, products, or categories), you MUST present the data in a clean, readable text TABLE format using plain spacing and capital headers.
            - Use standard line breaks (\\n) for new rows. Align the data into clear columns using spaces or tabs so it looks like a clean spreadsheet matrix.
            
            Example of expected text table format for orders:
            ORDER ID    TITLE      NAME       PRICE
            #D1         test       test       ₹120.00
            #D2         Apc        Apc        ₹125.00

            - Keep responses clear, professional, concise, and straight to the point. Do not mention that a system prompt or context list was provided to you.
            - OFF-TOPIC REFUSAL RULE: If the admin asks any question that is NOT related to this store's data (such as asking about famous personalities like MS Dhoni, general knowledge, sports, or news), you must NOT answer it. Instead, reply strictly with this exact phrase: I am an AI assistant. I have knowledge of this store. Ask me any question regarding categories, sales totals, or configurations.
        ";
        $apiKey = env('GEMINI_API_KEY');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $systemInstructions . "\n\nAdmin Question: " . $adminQuestion]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? "I processed the query but ran into a parsing error.";
                
                return response()->json([
                    'success' => true,
                    'reply' => trim($aiReply)
                ]);
            }

            return response()->json([
                'success' => false,
                'reply' => "The AI engine returned an error code: " . $response->status()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'reply' => "Database connection failure or API timeout: " . $e->getMessage()
            ]);
        }
    }

}
