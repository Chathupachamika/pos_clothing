<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class PromotionController extends Controller
{
    public function index()
    {
        try {
            $promotions = Promotion::all();
            return response()->json($promotions);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve promotions', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            return response()->json($promotion);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Promotion not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve promotion', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'product_id' => 'required|exists:products,id',
                'name' => 'required|string|max:100',
                'discount' => 'required|numeric',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);

            $promotion = Promotion::create($validatedData);
            return response()->json($promotion, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create promotion', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            $validatedData = $request->validate([
                'name' => 'string|max:100',
                'discount' => 'numeric',
                'start_date' => 'date',
                'end_date' => 'date',
            ]);

            $promotion->update($validatedData);
            return response()->json($promotion);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Promotion not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update promotion', 'message' => $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            $promotion->delete();
            return response()->json(['message' => 'Promotion deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Promotion not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete promotion', 'message' => $e->getMessage()], 500);
        }
    }
    public function showByProduct($id){
        try {
            $promotions = Promotion::where('product_id', $id)->get();
            return response()->json($promotions);
        }catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Promotion not found'], 404);
        }catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve promotions', 'message' => $e->getMessage()], 500);
        }
    }
}
