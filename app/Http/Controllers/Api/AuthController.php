<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
//use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15|regex:/^\+?[0-9]{7,15}$/',
            'address' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'region' => 'required|string|max:100',
            'marketing' => 'nullable|boolean',
            'zip_code' => 'required|string|max:10',
            'province' => 'required|string|max:100',
        ]);

        $user = User::create([
            'name'     => $request->first_name . ' ' . $request->last_name,
            'email'    => strtolower($request->email),
            'password' => Hash::make($request->password),
        ]);

        // Create the customer profile linked to the user
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => strtolower($request->email),
            'phone' => $request->phone,
            'address' => $request->address,
            'address2' => $request->address2,
            'city' => $request->city,
            'region' => $request->region,
            'marketing' => $request->marketing,
            'zip_code' => $request->zip_code,
            'province' => $request->province,
        ]);

        $user->assignRole('customer');

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'User and customer created successfully',
            'token' => $token,
            'user' => $user,
            'customer' => $customer
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || ! Hash::check($request->password, $user->password) || !$user->hasRole('customer')) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function saveCustomerFromLogin(Request $request, FirebaseAuth $auth)
    {
        $idToken = $request->input('idToken');


        try {
            $verifiedIdToken = $auth->verifyIdToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub');

            //dd($uid);

            $firebaseUser = $auth->getUser($uid);

            $customer = Customer::where('email', $firebaseUser->email)->first();

            if($customer) {
                $customer->update([
                    'name' => $firebaseUser->displayName,
                    'email' => $firebaseUser->email,
                    'photo_url' => $firebaseUser->photoUrl,
                    'firebase_uid' => $uid
                ]);
            } else {
                $customer = Customer::updateOrCreate(
                    ['firebase_uid' => $uid],
                    [
                        'name' => $firebaseUser->displayName,
                        'email' => $firebaseUser->email,
                        'photo_url' => $firebaseUser->photoUrl,
                        'firebase_uid' => $uid
                    ]
                );
            }

            
            return response()->json([
                'message' => 'Customer synced succesfully',
                'customer' => $customer,
            ]);


        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 401);
        }
    }

    public function checkUserDetails(Request $request)
    {
        $fireBaseUid = $request->query('uid');

        if ($fireBaseUid) { // Firebase UID provided
            $customer = Customer::where('firebase_uid', $fireBaseUid)->first();
    
            if ($customer) {
                return response()->json([
                    'message' => 'Customer details fetched successfully.',
                    'customer' => $customer,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No saved customer details found for these credentials.',
                ], 404);
            }
        } else {
            // Firebase UID is missing or null
            return response()->json([
                'message' => 'Missing firebase UID. User not logged in.',
            ], 400);
        }

    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out'], 200);
    }
}
