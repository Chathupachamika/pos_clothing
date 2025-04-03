<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function index()
    {
        $investments = Investment::all();
        return response()->json($investments);
    }

    public function show($id)
    {
        $investment = Investment::find($id);
        if (!$investment) {
            return response()->json(['message' => 'Investment not found'], 404);
        }
        return response()->json($investment);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'investor_name' => 'required|string|max:255',
                'amount' => 'required|numeric',
                'investment_date' => 'required|date',
                'description' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()], 400);
        }

        $investment = Investment::create($request->all());
        return response()->json($investment, 201);
    }

    public function update(Request $request, $id)
    {
        $investment = Investment::find($id);
        if (!$investment) {
            return response()->json(['message' => 'Investment not found'], 404);
        }

        try {
            $request->validate([
                'investor_name' => 'required|string|max:255',
                'amount' => 'required|numeric',
                'investment_date' => 'required|date',
                'description' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()], 400);
        }

        $investment->update($request->all());
        return response()->json($investment);
    }

    public function destroy($id)
    {
        $investment = Investment::find($id);
        if (!$investment) {
            return response()->json(['message' => 'Investment not found'], 404);
        }

        $investment->delete();
        return response()->json(['message' => 'Investment deleted successfully']);
    }
}
