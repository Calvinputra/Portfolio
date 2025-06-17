<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    public function create()
    {
        return view('database.payment_method.create', [
        ]);
    }

    public function paymentMethodStore(Request $request)
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

        $duplicate = PaymentMethod::where('name', $request->name)
        ->where('bank', $request->bank)
        ->where('account_name', $request->account_name)
        ->where('account_number', $request->account_number)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Payment Method sudah pernah terdaftar.'])
                ->withInput();
        }

        $payment_method = new PaymentMethod;
        $payment_method->name = $request->name;
        $payment_method->bank = $request->bank;
        $payment_method->account_name = $request->account_name;
        $payment_method->account_number = $request->account_number;
        $payment_method->save();

        return redirect()->route('payment_method.list')->with('success', 'Payment Method Berhasil ditambahkan!');
    }

    public function list()
    {
        $payment_methods = PaymentMethod::all();
        $user = User::with('role_user')->find(Auth::id());
        
        return view('database.payment_method.list', [
            'payment_methods' => $payment_methods,
            'user' => $user,
        ]);
    }
    public function edit($id)
    {
        $payment_method = PaymentMethod::find($id);
        return view('database.payment_method.edit', [
            'payment_method' => $payment_method,
        ]);
    }

    public function paymentMethodUpdate(Request $request, $id)
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

        $duplicate = PaymentMethod::where('name', $request->name)
        ->where('bank', $request->bank)
        ->where('account_name', $request->account_name)
        ->where('account_number', $request->account_number)
        ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->with(['error' => 'Payment Method sudah pernah terdaftar.'])
                ->withInput();
        }
        
        $payment_method = PaymentMethod::findOrFail($id);

        $payment_method->name = $request->name;
        $payment_method->bank = $request->bank;
        $payment_method->account_name = $request->account_name;
        $payment_method->account_number = $request->account_number;
        $payment_method->save();

        return redirect()->route('payment_method.list')->with('success', 'Payment Method Berhasil diubah!');
    }

    public function destroy($id)
    {
        $payment_method = PaymentMethod::findOrFail($id);
        $payment_method->delete();

        return redirect()->route('payment_method.list')->with('success', 'Payment Method berhasil dihapus.');
    }

}
