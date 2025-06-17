<?php

namespace App\Http\Controllers;

use App\Imports\SupplierImport;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    public function create()
    {
        return view('database.supplier.create', [
        ]);
    }

    public function supplierStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'company' => 'required',
            // 'phone' => ['required', 'regex:/^08[0-9]{9,}$/'],
        ], [
            'name.required' => 'Kolom Supplier Name wajib diisi.',
            // 'phone.required' => 'Kolom Supplier Phone wajib diisi.',
            // 'phone.regex' => 'Nomor telepon harus dimulai dengan "08" dan hanya berisi angka minimal 11 digit.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Supplier::where('name', $request->name)
        ->where('company', $request->company)
        ->where('phone', $request->phone)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Supplier dengan Name, Company, dan Phone ini sudah terdaftar.'])
                ->withInput();
        }

        $supplier = new Supplier;
        $supplier->name = $request->name;
        $supplier->company = $request->company;
        $supplier->phone = $request->phone;
        $supplier->due_date = $request->due_date;
        $supplier->save();

        return redirect()->route('supplier.list')->with('success', 'Supplier Berhasil ditambahkan!');
    }

    public function list()
    {
        $suppliers = Supplier::all();
        $user = User::with('role_user')->find(Auth::id());

        return view('database.supplier.list', [
            'suppliers' => $suppliers,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $supplier = supplier::find($id);
        return view('database.supplier.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function supplierUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'company' => 'required',
            // 'phone' => ['required', 'regex:/^08[0-9]{9,}$/'],
        ], [
            'name.required' => 'Kolom Supplier Name wajib diisi.',
            // 'phone.required' => 'Kolom Supplier Phone wajib diisi.',
            // 'phone.regex' => 'Nomor telepon harus dimulai dengan "08" dan hanya berisi angka minimal 11 digit.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Supplier::where('name', $request->name)
        ->where('company', $request->company)
        ->where('phone', $request->phone)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Supplier dengan Name, Company, dan Phone ini sudah terdaftar.'])
                ->withInput();
        }
        
        $supplier = Supplier::findOrFail($id);

        $supplier->name = $request->name;
        $supplier->company = $request->company;
        $supplier->phone = $request->phone;
        $supplier->due_date = $request->due_date;
        $supplier->save();

        return redirect()->route('supplier.list')->with('success', 'Supplier Berhasil diubah!');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('supplier.list')->with('success', 'Supplier berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new SupplierImport, $request->file('file'));

        return back()->with('success', 'Data Supplier berhasil diimport!');
    }

}
