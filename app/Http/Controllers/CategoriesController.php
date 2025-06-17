<?php

namespace App\Http\Controllers;

use App\Imports\CategoriesImport;
use App\Models\categories;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CategoriesController extends Controller
{
    public function create()
    {
        $categories = Category::orderBy('code','desc')->first();

        return view('database.categories.create', [
            'categories' => $categories
        ]);
    }

    public function categoriesStore(Request $request)
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

        $duplicate = Category::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'categories sudah pernah terdaftar.'])
                ->withInput();
        }

        $categories = new Category;
        $categories->name = $request->name;
        $categories->code = $request->code;
        $categories->save();

        return redirect()->route('categories.list')->with('success', 'categories Berhasil ditambahkan!');
    }

    public function list()
    {
        $categories = Category::all();
        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.categories.list', [
            'categories' => $categories,
            'user' => $user,
        ]);
    }
    
    public function edit($id)
    {
        $categories = Category::find($id);
        return view('database.categories.edit', [
            'categories' => $categories,
        ]);
    }

    public function categoriesUpdate(Request $request, $id)
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

        $duplicate = Category::where('name', $request->name)
        ->where('code', $request->code)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'categories sudah pernah terdaftar.'])
                ->withInput();
        }
        
        $categories = Category::findOrFail($id);

        $categories->name = $request->name;
        $categories->code = $request->code;
        $categories->save();

        return redirect()->route('categories.list')->with('success', 'categories Berhasil diubah!');
    }

    public function destroy($id)
    {
        $categories = Category::findOrFail($id);
        $categories->delete();

        return redirect()->route('categories.list')->with('success', 'categories berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new CategoriesImport, $request->file('file'));

        return back()->with('success', 'Data Category berhasil diimport!');
    }
}
