<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class WarehouseController extends Controller
{
    public function create()
    {
        $user = User::with('role_user')->find(Auth::id());

        $products = Product::with('category', 'brand')->get();
        return view('database.warehouse.create', [
            'user' => $user,
            'products' => $products,
        ]);
    }

    public function warehouseStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_name' => 'required',
            'warehouse_description' => 'required',
        ], [
            'warehouse_name.required' => 'Kolom Warehouse Name wajib diisi.',
            'warehouse_description.required' => 'Kolom Warehouse Description wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Warehouse::where('name', $request->warehouse_name)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Warehouse dengan Name sudah terdaftar.'])
                ->withInput();
        }

        $warehouse = new Warehouse;
        $warehouse->name = $request->warehouse_name;
        $warehouse->description = $request->warehouse_description;
        $warehouse->save();

        return redirect()->route('warehouse.list')->with('success', 'Warehouse Berhasil ditambahkan!');
    }

    public function list()
    {
        $warehouses = Warehouse::all();
        $user = User::with('role_user')->find(Auth::id());

        return view('database.warehouse.list', [
            'warehouses' => $warehouses,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $warehouse = Warehouse::find($id);
        return view('database.warehouse.edit', [
            'warehouse' => $warehouse,
        ]);
    }

    public function warehouseUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_name' => 'required',
            'warehouse_description' => 'required',
        ], [
            'warehouse_name.required' => 'Kolom Warehouse Name wajib diisi.',
            'warehouse_description.required' => 'Kolom Warehouse Description wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Warehouse::where('name', $request->warehouse_name)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Warehouse dengan Name sudah terdaftar.'])
                ->withInput();
        }
        
        $warehouse = Warehouse::findOrFail($id);

        $warehouse->name = $request->warehouse_name;
        $warehouse->description = $request->warehouse_description;
        $warehouse->save();

        return redirect()->route('warehouse.list')->with('success', 'Warehouse Berhasil diubah!');
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return redirect()->route('warehouse.list')->with('success', 'Warehouse berhasil dihapus.');
    }

}
