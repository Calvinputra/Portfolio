<?php

namespace App\Http\Controllers;

use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function create()
    {
        $user = User::with('role_user')->find(Auth::id());

        $products = Product::with('category', 'brand')->get();
        return view('database.customer.create', [
            'user' => $user,
            'products' => $products,
        ]);
    }

    public function customerStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'phone' => ['required', 'regex:/^08[0-9]{9,}$/'],
        ], [
            'name.required' => 'Kolom Customer Name wajib diisi.',
            // 'phone.required' => 'Kolom Customer Phone wajib diisi.',
            // 'phone.regex' => 'Nomor telepon harus dimulai dengan "08" dan hanya berisi angka minimal 11 digit.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Customer::where('name', $request->name)
        ->where('phone', $request->phone)
        ->where('store', $request->store)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Customer dengan Name, Phone, dan Store ini sudah terdaftar.'])
                ->withInput();
        }

        $customer = new Customer;
        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->store = $request->store;
        $customer->save();

        return redirect()->route('customer.list')->with('success', 'Customer Berhasil ditambahkan!');
    }

    public function list()
    {
        $customers = Customer::all();
        $user = User::with('role_user')->find(Auth::id());

        return view('database.customer.list', [
            'customers' => $customers,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $customer = customer::find($id);
        return view('database.customer.edit', [
            'customer' => $customer,
        ]);
    }

    public function customerUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'phone' => ['required', 'regex:/^08[0-9]{9,}$/'],
        ], [
            'name.required' => 'Kolom Customer Name wajib diisi.',
            // 'phone.required' => 'Kolom Customer Phone wajib diisi.',
            // 'phone.regex' => 'Nomor telepon harus dimulai dengan "08" dan hanya berisi angka minimal 11 digit.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $duplicate = Customer::where('name', $request->name)
        ->where('phone', $request->phone)
        ->where('store', $request->store)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Customer dengan Name, Phone, dan Store ini sudah terdaftar.'])
                ->withInput();
        }

        $customer = Customer::findOrFail($id);

        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->store = $request->store;
        $customer->save();

        return redirect()->route('customer.list')->with('success', 'Customer Berhasil diubah!');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customer.list')->with('success', 'Customer berhasil dihapus.');
    }

    public function import(Request $request){
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new CustomerImport, $request->file('file'));

        return back()->with('success', 'Data pelanggan berhasil diimport!');
    }

}
