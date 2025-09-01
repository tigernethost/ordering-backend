<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Contract\Auth;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all customers
        $customers = Customer::all();

        return response()->json([
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'firebase_uid' => 'nullable|string',
            'name' => 'nullable|string',
            'photo_url' => 'nullable|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'phone' => 'required|string|max:15|regex:/^\+?[0-9]{7,15}$/',
            'address' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'region' => 'required|string|max:100',
            'marketing' => 'nullable|boolean',
            'zip_code' => 'required|string|max:10',
            'province' => 'required|string|max:100',
        ]);


        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        return response()->json([
            'customer' => $customer,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    
        $customer = Customer::where('email', $request->input('email'))->whereNotNull('firebase_uid')->first();
    
        if (!$customer) {
            return response()->json(['message' => 'Customer with account not found'], 404);
        }
    
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15|regex:/^\+?[0-9]{7,15}$/',
            'address' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'zip_code' => 'required|string|max:10',
            'province' => 'required|string|max:100',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
        ]);
    
        $customer->update($validated);
    
        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer,
        ]);
    }
    
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully.',
        ]);
    }

}
