<?php

namespace App\Http\Controllers;

use App\Models\GRNNote;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GRNNoteController extends Controller
{
    public function index()
    {
        $grnNotes = GRNNote::with(['supplier', 'product', 'admin'])->get();
        return response()->json(['status' => 'success', 'data' => $grnNotes]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate request with more lenient rules
            $validatedData = $request->validate([
                'grn_number' => 'required|string|unique:grn_notes',
                'product_id' => 'required|exists:products,id',
                'supplier_id' => 'nullable|exists:suppliers,id', // Made supplier_id optional
                'admin_id' => 'nullable|exists:admins,id',      // Made admin_id optional
                'price' => 'required|numeric|min:0',
                'product_details' => 'required|array',
                'product_details.name' => 'required|string',
                'product_details.description' => 'nullable|string',
                'product_details.brand_name' => 'nullable|string', // Made brand_name optional
                'product_details.size' => 'nullable|string',       // Made size optional
                'product_details.color' => 'nullable|string',      // Made color optional
                'product_details.bar_code' => 'nullable|string',
                'received_date' => 'required|date',
                'previous_quantity' => 'required|integer',
                'new_quantity' => 'required|integer',
                'adjusted_quantity' => 'required|integer',
                'adjustment_type' => 'required|string|in:addition,reduction'
            ]);

            // Set default values for optional fields
            $validatedData['product_details'] = array_merge([
                'description' => '',
                'brand_name' => '',
                'size' => '',
                'color' => '',
                'bar_code' => '',
            ], $validatedData['product_details']);
            // Create GRN Note
            $grnNote = GRNNote::create($validatedData);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'GRN Note created successfully',
                'data' => $grnNote
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('GRN Validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GRN Note creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create GRN Note: ' . $e->getMessage()
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $grnNote = GRNNote::with(['supplier', 'product', 'admin'])->findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $grnNote]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'GRN Note not found'
            ], 404);
        }
    }
}
