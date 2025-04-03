<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    public function index()
    {
        $feedback = Feedback::paginate(10);

        return response()->json([
            'data' => $feedback->items(),
            'current_page' => $feedback->currentPage(),
            'per_page' => $feedback->perPage(),
            'total' => $feedback->total(),
            'last_page' => $feedback->lastPage(),
            'next_page_url' => $feedback->nextPageUrl(),
            'prev_page_url' => $feedback->previousPageUrl(),
        ]);
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        try {
            $request->validate([
                'customer_id' => 'required',
                'rating' => 'required',
                'comment' => 'required|string|max:255',
            ]);
        } catch (ValidationException $th) {
            // Return a custom response
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->validator->errors(),
            ], 422);
        }

        // Store the feedback record
        $feedback = Feedback::create($request->all());

        return response()->json([
            'data' => $feedback,
            'message' => 'Feedback submitted successfully',
        ], 201);
    }

    public function show($id)
    {
        try {
            $feedback = Feedback::findOrFail($id);
            return response()->json($feedback);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Feedback not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $feedback = Feedback::findOrFail($id);
            $request->validate([
                'customer_id' => 'required',
                'rating' => 'required',
                'comment' => 'required|string|max:255',
            ]);
            $feedback->update($request->all());
            return response()->json([
                'message' => 'Feedback updated successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Feedback not found'], 404);
        } catch (ValidationException $th) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->validator->errors(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $feedback = Feedback::findOrFail($id);
            $feedback->delete();
            return response()->json(['message' => 'Feedback deleted successfully'], 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Feedback not found'], 404);
        }
    }

    public function averageRating()
    {
        $averageRating = Feedback::avg('rating');
        return response()->json($averageRating != null ? ['average_rating' => $averageRating] : "There is no rating");
    }

    public function positiveFeedback()
    {
        $positiveFeedback = Feedback::where('rating', '>=', 4)->get();
        return response()->json($positiveFeedback->isNotEmpty() ? $positiveFeedback : "There was no feedback");
    }

    public function negativeFeedback()
    {
        $negativeFeedback = Feedback::where('rating', '<', 3)->get();
        return response()->json($negativeFeedback->isNotEmpty() ? $negativeFeedback : "There was no feedback");
    }
}
