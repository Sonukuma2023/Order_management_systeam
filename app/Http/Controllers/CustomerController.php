<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{

    public function store(Request $request)
    {

        
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
       
        // Skip the header row
        fgetcsv($handle);

        $importedCount = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
           

            if (isset($data[2]) && !empty($data[2])) {
                // Concatenate First and Last name for your 'name' field
                $fullName = trim(($data[0] ?? '') . ' ' . ($data[1] ?? ''));

                User::updateOrCreate(
                    ['email' => $data[2]], // Unique identifier
                    [
                        'name'     => $fullName,
                        'phone'    => $data[11] ?? ($data[12] ?? null), // Uses Address Phone, falls back to Phone column
                        'address'  => $data[5] ?? null,
                        'password' => Hash::make('password123'), // Default password
                    ]
                );
                $importedCount++;
            }
        }

        fclose($handle);

        return back()->with('success', "$importedCount Customers imported successfully!");
    }
    public function addnewuser(Request $request) {
    
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => 'required|digits:10', 
            'address'  => 'nullable|string',
        ]);
         $shopifyResponse = $this->shopify_create_customer($validated);
        
        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make('test@123'), 
            'phone'    => $validated['phone'] ?? null,
            'address'  => $validated['address'] ?? null,
            'Role'     => 'user',
        ]);

        return redirect()->back()->with('message', 'Registration successful!');
    }


    public function shopify_create_customer($validated)
    {
        $nameParts = explode(' ', trim($validated['name']));

        $firstName = $nameParts[0] ?? '';
        $lastName  = $nameParts[1] ?? '';
        $customerData = [
            "customer" => [
                "first_name"     => $firstName,
                "last_name"      => $lastName,
                "email"          => $validated['email'],
                "phone"          => "+91" . $validated['phone'],
                "verified_email" => true,
                "tags"           => "Laravel Customer"
            ]
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('SHOPIFY_URL') . '/admin/api/2025-04/customers.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($customerData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Shopify-Access-Token: ' . env('SHOPIFY_TOKEN'),
            ],
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {

            $error = curl_error($curl);

            curl_close($curl);

            return [
                'status'  => false,
                'message' => $error
            ];
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {

            return [
                'status'  => false,
                'message' => 'Shopify customer creation failed',
                'errors'  => $responseData
            ];
        }

        return [
            'status' => true,
            'data'   => $responseData
        ];
    }


}