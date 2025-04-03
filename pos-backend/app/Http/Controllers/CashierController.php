<?php

namespace App\Http\Controllers;

use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CashierController extends Controller
{
    public function index()
    {
        $cashiers = Cashier::all();
        $data = $cashiers->map(function ($cashier) {
            return [
                'id' => $cashier->id,
                'name' => $cashier->name,
                'email' => $cashier->email,
                'password' => $cashier->password,
                'image_url' => $cashier->image_path
                    ? url('storage/' . $cashier->image_path)
                    : null
            ];
        });

        return response()->json([
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validatedAttributes = $request->validate([
                'name' => 'required|string',
                'email' => 'required|string|email',
                'password' => 'required|string|min:8',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($request->hasFile('image')) {
                $image_path = $request->file('image')->store('image', 'public');
                $validatedAttributes['image_path'] = $image_path;
            }

            $validatedAttributes['password'] = Hash::make($validatedAttributes['password']);

            if (isset($validatedAttributes['image'])) {
                unset($validatedAttributes['image']);
            }

            $createdCashier = Cashier::create($validatedAttributes);

            $responseData = [
                'id' => $createdCashier->id,
                'name' => $createdCashier->name,
                'email' => $createdCashier->email,
                'image_url' => $createdCashier->image_path
                    ? url('storage/' . $createdCashier->image_path)
                    : null
            ];

            return response()->json([
                'message' => 'Successfully Created a new Cashier',
                'data' => $responseData
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'An error occurred while creating a new cashier',
                'details' => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $existingCashier = Cashier::find($id);
        if (!$existingCashier) {
            return response()->json([
                'error' => 'Cashier not found',
                'id' => $id
            ], 404);
        }
        return response()->json([
            'message' => 'Show Cashier Details',
            'id' => $id,
            'data' => $existingCashier
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedAttributes = $request->validate([
                'name' => 'required|string',
                'email' => 'required|string|email',
                'password' => 'nullable|string|min:8',
            ]);

            $existingCashier = Cashier::find($id);
            if (!$existingCashier) {
                return response()->json([
                    'error' => 'Cashier not found',
                    'id' => $id
                ], 404);
            }

            if (isset($validatedAttributes['password'])) {
                $validatedAttributes['password'] = Hash::make($validatedAttributes['password']);
            } else {
                unset($validatedAttributes['password']);
            }

            try {
                $updatedCashier = $existingCashier->update($validatedAttributes);
            } catch (\Throwable $th) {
                return response()->json([
                    'error' => 'An error occurred while updating the cashier',
                    'details' => $th->getMessage()
                ], 500);
            }

            return response()->json([
                'message' => 'Update Successful',
                'id' => $id,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy($id)
    {
        $existingCashier = Cashier::find($id);
        if (!$existingCashier) {
            return response()->json([
                'error' => 'Cashier not found',
                'id' => $id
            ], 404);
        }

        try {
            $existingCashier->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'An error occurred while deleting the cashier',
                'details' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Deleted the Cashier',
            'id' => $id,
        ]);
    }

    public function updateImage(Request $request, $id)
    {
        $existingCashier = Cashier::find($id);
        if (!$existingCashier) {
            return response()->json([
                'error' => 'Cashier not found',
                'id' => $id
            ], 404);
        }
        try {
            $validatedImagePath = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        try {
            $image_path = $request->file('image')->store('image', 'public');
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'An error occurred while uploading the image',
                'details' => $th->getMessage()
            ], 500);
        }

        try {
            $existingCashier->update(['image_path' => $image_path]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'An error occurred while updating the cashier with image',
                'details' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Uploaded the Cashier Image',
            'id' => $id,
        ]);
    }

    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'email' => 'required|email',
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $existingCashier = Cashier::find($request->id);
        if (!$existingCashier) {
            return response()->json([
                'error' => 'Cashier not found',
                'id' => $request->id
            ], 404);
        }

        if ($existingCashier->email != $request->email) {
            return response()->json([
                'error' => 'Email does not match',
                'id' => $request->id,
            ], 401);
        }

        if (Hash::check($request->current_password, $existingCashier->password)) {
            $existingCashier->update(['password' => bcrypt($request->new_password)]);
            return response()->json([
                'message' => 'Password updated successfully',
                'id' => $request->id,
            ]);
        } else {
            return response()->json([
                'error' => 'Current password does not match',
                'id' => $request->id,
            ], 401);
        }
    }
}
