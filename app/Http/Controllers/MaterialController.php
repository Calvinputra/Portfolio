<?php

namespace App\Http\Controllers;

use App\Imports\MaterialImport;
use App\Models\Material;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class MaterialController extends Controller
{
    public function create()
    {
        return view('database.material.create', [
        ]);
    }

    public function materialStore(Request $request)
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

        $duplicate = Material::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'material sudah pernah terdaftar.'])
                ->withInput();
        }

        $material = new Material;
        $material->name = $request->name;
        $material->code = $request->code;
        $material->save();

        return redirect()->route('material.list')->with('success', 'material Berhasil ditambahkan!');
    }

    public function list()
    {
        $materials = Material::all();
        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.material.list', [
            'materials' => $materials,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $material = Material::find($id);
        return view('database.material.edit', [
            'material' => $material,
        ]);
    }

    public function materialUpdate(Request $request, $id)
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

        $duplicate = Material::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'material sudah pernah terdaftar.'])
                ->withInput();
        }
        
        $material = Material::findOrFail($id);

        $material->name = $request->name;
        $material->code = $request->code;
        $material->save();

        return redirect()->route('material.list')->with('success', 'material Berhasil diubah!');
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();

        return redirect()->route('material.list')->with('success', 'material berhasil dihapus.');
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new materialImport, $request->file('file'));

        return back()->with('success', 'Data material berhasil diimport!');
    }

}
