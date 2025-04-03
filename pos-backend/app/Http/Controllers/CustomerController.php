<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Customer_contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use function Illuminate\Log\log;

class CustomerController extends Controller
{
    public function index()
    {
        // Fetch all customers
        $customers = Customer::with('contact')->paginate(10);
        log($customers);

        return response()->json([
            'data' => $customers->items(), // The paginated items
            'current_page' => $customers->currentPage(),
            'per_page' => $customers->perPage(),
            'total' => $customers->total(),
            'last_page' => $customers->lastPage(),
            'next_page_url' => $customers->nextPageUrl(),
            'prev_page_url' => $customers->previousPageUrl(),
        ]);
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        try {
            $validatedAttributes = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:customers',
                'contact_number' => 'required|string|max:20',
            ]);
        } catch (ValidationException $th) {
            // Return a custom response
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->errors()
            ], 422);
        }

        // Create a new customer record
        DB::beginTransaction();
        try {
            log($validatedAttributes);
            $customer = Customer::create(['name' => $validatedAttributes['name'], 'email' => $validatedAttributes['email']]);
            Customer_contact::create(['contact_number' => $validatedAttributes['contact_number'], "customer_id" => $customer['id']]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            // Return a generic error message
            return response()->json([
                'message' => 'An error occurred while saving the customer.',
                'errors' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Saved a new Customer',
            $customer['id']
        ]);
    }

    public function show($id)
    {
        // search customer
        $existingCustomer = Customer::with('contact')->find($id);
        if (!$existingCustomer) {
            return response()->json([
                'error' => 'Customer not found',
            ], 404);
        }

        return response()->json($existingCustomer);
    }

    public function update(Request $request, $id)
    {
        $existingCustomer = Customer::find($id);
        if (!$existingCustomer) {
            return response()->json([
                'error' => 'Customer not found',
            ], 404);
        }

        // Validate the incoming request
        try {
            $validatedAttributes = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ["required", "string", "email", "max:255", Rule::unique('customers')->ignore($id)],
                'contact_number' => 'required|string|max:20',
            ]);
        } catch (ValidationException $th) {
            // Return a custom response
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->errors()
            ], 422);
        }

        // Update the customer record
        DB::beginTransaction();
        try {
            $existingCustomer->update(['name' => $validatedAttributes['name'], 'email' => $validatedAttributes['email']]);
            Customer_contact::where('customer_id', $existingCustomer['id'])->update(['contact_number' => $validatedAttributes['contact_number']]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            // Return a generic error message
            return response()->json([
                'message' => 'An error occurred while updating the customer.',
                'errors' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Updated Customer',
            $existingCustomer['id']
        ]);
    }

    public function destroy($id)
    {
        $existingCustomer = Customer::find($id);
        if (!$existingCustomer) {
            return response()->json([
                'error' => 'Customer not found',
            ], 404);
        }

        // Delete the customer record
        DB::beginTransaction();
        try {
            $existingCustomer->delete();
            Customer_contact::where('customer_id', $existingCustomer['id'])->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            // Return a generic error message
            return response()->json([
                'message' => 'An error occurred while deleting the customer.',
                'errors' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Deleted Customer',
            $existingCustomer['id']
        ]);
    }
}
