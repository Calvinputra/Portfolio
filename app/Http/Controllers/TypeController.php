<?php

namespace App\Http\Controllers;

use App\Imports\TypeImport;
use App\Models\Type;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class TypeController extends Controller
{
    public function create()
    {
        return view('database.type.create', [
        ]);
    }

    public function typeStore(Request $request)
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

        $duplicate = Type::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'type sudah pernah terdaftar.'])
                ->withInput();
        }

        $type = new Type;
        $type->name = $request->name;
        $type->code = $request->code;
        $type->save();

        return redirect()->route('type.list')->with('success', 'type Berhasil ditambahkan!');
    }

    public function list()
    {
        $types = Type::all();
        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.type.list', [
            'types' => $types,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $type = Type::find($id);
        return view('database.type.edit', [
            'type' => $type,
        ]);
    }

    public function typeUpdate(Request $request, $id)
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

        $duplicate = Type::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'type sudah pernah terdaftar.'])
                ->withInput();
        }
        
        $type = Type::findOrFail($id);

        $type->name = $request->name;
        $type->code = $request->code;
        $type->save();

        return redirect()->route('type.list')->with('success', 'type Berhasil diubah!');
    }

    public function destroy($id)
    {
        $type = Type::findOrFail($id);
        $type->delete();

        return redirect()->route('type.list')->with('success', 'type berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new TypeImport, $request->file('file'));

        return back()->with('success', 'Data Type berhasil diimport!');
    }
}
