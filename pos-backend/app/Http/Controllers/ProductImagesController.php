<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductImages;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Response;

class ProductImagesController extends Controller
{
    public function index()
    {
        try {
            $images = ProductImages::all();
            return $this->successResponse('Images retrieved successfully', $images);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve images: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve images', 500);
        }
    }

    public function show($id)
    {
        try {
            $image = ProductImages::findOrFail($id);
            return $this->successResponse('Image found', $image);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Image not found', 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving image: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve image', 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'images' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        try {
            $file = $request->file('images');
            $imagePath = $file->store('products', 'public');
            $imageData = [
                'product_id' => $request->product_id,
                'path' => $imagePath,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
            $productImage = ProductImages::create($imageData);
            return $this->successResponse('Image created successfully', $productImage, 201);
        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $image = ProductImages::findOrFail($id);
            $request->validate([
                'name' => 'sometimes|string',
                'size' => 'sometimes|integer',
                'images' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            $updateData = $request->except(['product_id', 'images']);
            if ($request->hasFile('images')) {
                // Delete old image
                Storage::disk('public')->delete($image->path);
                // Store new image
                $file = $request->file('images');
                $imagePath = $file->store('products', 'public');
                $updateData['path'] = $imagePath;
                $updateData['name'] = $file->getClientOriginalName();
                $updateData['size'] = $file->getSize();
            }
            $image->update($updateData);
            return $this->successResponse('Image updated successfully', $image);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Image not found', 404);
        } catch (\Exception $e) {
            Log::error('Error updating image: ' . $e->getMessage());
            return $this->errorResponse('Failed to update image', 500);
        }
    }

    public function showByProduct($productId)
    {
        try {
            $images = ProductImages::where('product_id', $productId)->get();
            return $this->successResponse('Images retrieved successfully', $images);
        } catch (\Exception $e) {
            Log::error('Error retrieving images for product: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve images', 500);
        }
    }

    public function destroy($id)
    {
        try {
            $image = ProductImages::findOrFail($id);
            // Delete the image file
            Storage::disk('public')->delete($image->path);
            $image->delete();
            return $this->successResponse('Image deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Image not found', 404);
        } catch (\Exception $e) {
            Log::error('Error deleting image: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete image', 500);
        }
    }

    // Response methods
    private function successResponse($message, $data = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    private function errorResponse($message, $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $code);
    }
}
