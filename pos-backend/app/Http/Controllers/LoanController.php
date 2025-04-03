<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::all();
        return response()->json($loans);
    }

    public function show($id)
    {
        $loan = Loan::find($id);
        if (!$loan) {
            return response()->json(['message' => 'Loan not found'], 404);
        }
        return response()->json($loan);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'borrower_name' => 'required|string|max:255',
                'amount' => 'required|numeric',
                'loan_date' => 'required|date',
                'due_date' => 'required|date',
                'status' => 'required|string|in:pending,paid,overdue',
                'description' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()], 400);
        }

        $loan = Loan::create($request->all());
        return response()->json($loan, 201);
    }

    public function update(Request $request, $id)
    {
        $loan = Loan::find($id);
        if (!$loan) {
            return response()->json(['message' => 'Loan not found'], 404);
        }

        try {
            $request->validate([
                'borrower_name' => 'required|string|max:255',
                'amount' => 'required|numeric',
                'loan_date' => 'required|date',
                'due_date' => 'required|date',
                'status' => 'required|string|in:pending,paid,overdue',
                'description' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()], 400);
        }

        $loan->update($request->all());
        return response()->json($loan);
    }

    public function destroy($id)
    {
        $loan = Loan::find($id);
        if (!$loan) {
            return response()->json(['message' => 'Loan not found'], 404);
        }

        $loan->delete();
        return response()->json(['message' => 'Loan deleted successfully']);
    }
}
