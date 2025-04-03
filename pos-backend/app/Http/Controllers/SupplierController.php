<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(){
        $suppliers = Supplier::all();
        return response()->json($suppliers);
    }
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:suppliers,email',
                'contact' => 'required|string',
            ]);

            // Get the first admin id from the admins table
            $adminId = DB::table('admins')->first()->id;
            if (!$adminId) {
                throw new \Exception('No admin found in the system');
            }

            $supplier = new Supplier();
            $supplier->admin_id = $adminId;
            $supplier->name = $request->name;
            $supplier->email = $request->email;
            $supplier->contact = $request->contact;
            $supplier->save();

            DB::commit();
            return response()->json($supplier, 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function show($id){
        try {
            $supplier = Supplier::findOrFail($id);
            return response()->json($supplier);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }
    }
    public function showProduct($id){
        try {
            $supplier = Supplier::findOrFail($id);
            return response()->json($supplier->products);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }
    }
    public function update(Request $request, $id){
        try {
            $supplier = Supplier::findOrFail($id);
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'contact' => 'sometimes|required|numeric',
            ]);
            $supplier->update($request->all());
            return response()->json($supplier);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Supplier not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        }
    }
    public function destroy($id){
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();
            return response()->json("$id Successfully deleted", 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }
    }
}
