<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();
        
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('cus_name', 'like', "%{$search}%")
                  ->orWhere('cus_contact', 'like', "%{$search}%");
            });
        }
        
        $customers = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cus_name' => 'required|string|max:100',
            'cus_contact' => 'nullable|string|max:10|unique:customers',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }

    public function show(string $id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    public function update(Request $request, string $id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'cus_name' => 'sometimes|string|max:100',
            'cus_contact' => 'nullable|string|max:10|unique:customers,cus_contact,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer
        ]);
    }

    public function destroy(string $id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
        
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }
}
