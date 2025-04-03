<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::all();
        return response()->json($assets);
    }

    public function show($id)
    {
        $asset = Asset::find($id);
        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }
        return response()->json($asset);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'value' => 'required|numeric',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $asset = Asset::create($request->all());
        return response()->json($asset, 201);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::find($id);
        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'value' => 'required|numeric',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $asset->update($request->all());
        return response()->json($asset);
    }

    public function destroy($id)
    {
        $asset = Asset::find($id);
        if (!$asset) {
            return response()->json(['message' => 'Asset not found'], 404);
        }

        $asset->delete();
        return response()->json(['message' => 'Asset deleted successfully']);
    }
}
