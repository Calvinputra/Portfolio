<?php

namespace App\Http\Controllers;

use App\Imports\ColourImport;
use App\Models\Colour;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ColourController extends Controller
{
    public function create()
    {
        return view('database.colour.create', [
        ]);
    }

    public function colourStore(Request $request)
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

        $duplicate = Colour::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'colour sudah pernah terdaftar.'])
                ->withInput();
        }

        $colour = new Colour;
        $colour->name = $request->name;
        $colour->code = $request->code;
        $colour->save();

        return redirect()->route('colour.list')->with('success', 'colour Berhasil ditambahkan!');
    }

    public function list()
    {
        $colours = Colour::all();
        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.colour.list', [
            'colours' => $colours,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $colour = Colour::find($id);
        return view('database.colour.edit', [
            'colour' => $colour,
        ]);
    }

    public function colourUpdate(Request $request, $id)
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

        $duplicate = Colour::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'colour sudah pernah terdaftar.'])
                ->withInput();
        }
        
        $colour = Colour::findOrFail($id);

        $colour->name = $request->name;
        $colour->code = $request->code;
        $colour->save();

        return redirect()->route('colour.list')->with('success', 'colour Berhasil diubah!');
    }

    public function destroy($id)
    {
        $colour = Colour::findOrFail($id);
        $colour->delete();

        return redirect()->route('colour.list')->with('success', 'colour berhasil dihapus.');
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new ColourImport, $request->file('file'));

        return back()->with('success', 'Data Colour berhasil diimport!');
    }

}
