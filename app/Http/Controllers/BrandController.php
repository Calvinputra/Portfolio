<?php

namespace App\Http\Controllers;

use App\Imports\BrandImport;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class BrandController extends Controller
{
    public function create()
    {
        return view('database.brand.create', [
        ]);
    }

    public function brandStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => 'Kolom Name wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Brand::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Brand sudah pernah terdaftar.'])
                ->withInput();
        }

        $brand = new Brand;
        $brand->name = $request->name;
        $brand->code = $request->code;
        $brand->save();

        return redirect()->route('brand.list')->with('success', 'Brand Berhasil ditambahkan!');
    }

    public function list()
    {
        $brands = Brand::all();
        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.brand.list', [
            'brands' => $brands,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $brand = Brand::find($id);
        return view('database.brand.edit', [
            'brand' => $brand,
        ]);
    }

    public function brandUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'name.required' => 'Kolom Name wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Brand::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Brand sudah pernah terdaftar.'])
                ->withInput();
        }
        
        $brand = Brand::findOrFail($id);

        $brand->name = $request->name;
        $brand->code = $request->code;
        $brand->save();

        return redirect()->route('brand.list')->with('success', 'Brand Berhasil diubah!');
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();

        return redirect()->route('brand.list')->with('success', 'Brand berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new BrandImport, $request->file('file'));

        return back()->with('success', 'Data Brand berhasil diimport!');
    }
}
