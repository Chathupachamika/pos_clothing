<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupplierAdmin;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Response;

class SupplierAdminController extends Controller
{
    public function index()
    {
        try {
            $SupplierAdmins = SupplierAdmin::with('supplier')->get();
            return response()->json($SupplierAdmins, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'message not found'], 400);
        }
    }
    public function store(Request $request)
    {
        try {
            $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'admin_id' => 'required|exists:admins,id',
                'alert_message' => 'required|string'
            ]);
            $supplierAdmin = SupplierAdmin::create($request->all());
            return response()->json($supplierAdmin, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        try {
            $supplierAdmin = SupplierAdmin::findOrFail($id);
            return response()->json($supplierAdmin, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'message not found'], 400);
        }
    }
}
