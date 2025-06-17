<?php

namespace App\Http\Controllers;

use App\Imports\ClassificationImport;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ClassificationController extends Controller
{
    public function create()
    {
        $categories = Category::all();

        $classification = Classification::orderBy('code', 'desc')->first();

        return view('database.classification.create', [
            'categories' => $categories,
            'classification' => $classification,
        ]);
    }

    public function classificationStore(Request $request)
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

        $duplicate = Classification::where('name', $request->name)
        ->where('code', $request->code)
        ->where('categories_id', $request->classification_category)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'classification sudah pernah terdaftar.'])
                ->withInput();
        }
        $classification = new Classification;
        $classification->name = $request->name;
        $classification->code = $request->code;
        $classification->categories_id = $request->classification_category_id;
        $classification->save();

        return redirect()->route('classification.list')->with('success', 'classification Berhasil ditambahkan!');
    }

    public function list()
    {
        $classifications = Classification::with('categories')->get();

        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.classification.list', [
            'classifications' => $classifications,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $categories = Category::all();

        $classification = Classification::find($id);
        return view('database.classification.edit', [
            'categories' => $categories,
            'classification' => $classification,
        ]);
    }

    public function classificationUpdate(Request $request, $id)
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

        $duplicate = Classification::where('name', $request->name)
            ->where('code', $request->code)
            ->where('categories_id', $request->classification_category_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Classification sudah pernah terdaftar.'])
                ->withInput();
        }

        $classification = Classification::findOrFail($id);
        $classification->name = $request->name;
        $classification->code = $request->code;
        $classification->categories_id = $request->classification_category_id;
        $classification->save();

        return redirect()->route('classification.list')->with('success', 'Classification Berhasil diubah!');
    }


    public function destroy($id)
    {
        $classification = Classification::findOrFail($id);
        $classification->delete();

        return redirect()->route('classification.list')->with('success', 'classification berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new ClassificationImport, $request->file('file'));

        return back()->with('success', 'Data Classification berhasil diimport!');
    }

}
