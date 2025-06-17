<?php

namespace App\Http\Controllers;

use App\Imports\ShapeImport;
use App\Models\Shape;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ShapeController extends Controller
{
    public function create()
    {
        return view('database.shape.create', [
        ]);
    }

    public function shapeStore(Request $request)
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

        $duplicate = Shape::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'shape sudah pernah terdaftar.'])
                ->withInput();
        }

        $shape = new Shape;
        $shape->name = $request->name;
        $shape->code = $request->code;
        $shape->save();

        return redirect()->route('shape.list')->with('success', 'shape Berhasil ditambahkan!');
    }

    public function list()
    {
        $shapes = Shape::all();
        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.shape.list', [
            'shapes' => $shapes,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $shape = Shape::find($id);
        return view('database.shape.edit', [
            'shape' => $shape,
        ]);
    }

    public function shapeUpdate(Request $request, $id)
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

        $duplicate = Shape::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'shape sudah pernah terdaftar.'])
                ->withInput();
        }
        
        $shape = Shape::findOrFail($id);

        $shape->name = $request->name;
        $shape->code = $request->code;
        $shape->save();

        return redirect()->route('shape.list')->with('success', 'shape Berhasil diubah!');
    }

    public function destroy($id)
    {
        $shape = Shape::findOrFail($id);
        $shape->delete();

        return redirect()->route('shape.list')->with('success', 'shape berhasil dihapus.');
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new shapeImport, $request->file('file'));

        return back()->with('success', 'Data shape berhasil diimport!');
    }

}
