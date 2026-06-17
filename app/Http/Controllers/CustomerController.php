<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CustomerController extends Controller
{

    public function store(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:csv,txt|max:2048',
    ]);

    $file = $request->file('file');
    $handle = fopen($file->getRealPath(), 'r');

    // Skip header row
    fgetcsv($handle);

    $importedCount = 0;
    $emailSentCount = 0;

    while (($data = fgetcsv($handle, 1000, ',')) !== false) {

        if (empty($data[2])) {
            continue;
        }

        $fullName = trim(($data[0] ?? '') . ' ' . ($data[1] ?? ''));
        $email = trim($data[2]);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $fullName,
                'phone'    => $data[3] ?? ($data[12] ?? null),
                'address'  => $data[4] ?? null,
                'password' => Hash::make(Str::random(20)),
            ]
        );

        try {

            // Generate password reset token
            $token = Password::createToken($user);

            // Laravel reset password URL
            $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($user->email));

            Mail::html(
                "
                <h3>Hello {$user->name},</h3>

                <p>Your account has been created successfully.</p>

                <p>Email: {$user->email}</p>

                <p>Please click the button below to create/change your password:</p>

                <p>
                    <a href='{$resetUrl}'
                       style='background:#0d6efd;color:#fff;padding:10px 20px;
                       text-decoration:none;border-radius:5px;'>
                        Set Password
                    </a>
                </p>

                <p>If you did not request this account, please ignore this email.</p>

                <p>Thank you.</p>
                ",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Set Your Password');
                }
            );

            $emailSentCount++;

        } catch (\Exception $e) {
            \Log::error('Email send failed: ' . $e->getMessage());
        }

        $importedCount++;
    }

    fclose($handle);

    return back()->with(
        'success',
        "{$importedCount} users imported successfully. {$emailSentCount} emails sent."
    );
}
    public function addnewuser(Request $request) {
    
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => 'required|digits:10', 
            'address'  => 'nullable|string',
        ]);
         $shopifyResponse = $this->shopify_create_customer($validated);
        
        $user =User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make('test@123'), 
            'phone'    => $validated['phone'] ?? null,
            'address'  => $validated['address'] ?? null,
            'Role'     => 'vendor',
        ]);

            $token = Password::createToken($user);
            $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($user->email));

         Mail::html(
                "
                <h3>Hello {$user->name},</h3>

                <p>Your account has been created successfully.</p>

                <p>Email: {$user->email}</p>

                <p>Please click the button below to create/change your password:</p>

                <p>
                    <a href='{$resetUrl}'
                       style='background:#0d6efd;color:#fff;padding:10px 20px;
                       text-decoration:none;border-radius:5px;'>
                        Set Password
                    </a>
                </p>

                <p>If you did not request this account, please ignore this email.</p>

                <p>Thank you.</p>
                ",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Set Your Password');
                }
            );


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