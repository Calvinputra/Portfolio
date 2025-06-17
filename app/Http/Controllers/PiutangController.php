<?php

namespace App\Http\Controllers;

use App\Models\BundleProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use ProductBundle;

use function view;

class PiutangController extends Controller
{

    public function list()
    {
        $sale_invoices = SalesInvoice::with('order_item.product','customer')
        ->where('status', '=', 'Ngutang')->get();
        $payment_methods = PaymentMethod::get();
        
        return view('piutang.list', [
            'sale_invoices' => $sale_invoices,
            'payment_methods' => $payment_methods,
        ]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'sale_invoice_id' => 'required|exists:sales_invoices,id',
            'sale_invoice_number' => 'required|exists:sales_invoices,number_invoice',
            'paid_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_method,id',
            'description' => 'nullable|string|max:255',
        ]);

        $invoice = SalesInvoice::findOrFail($request->sale_invoice_id);
        $invoice->status = 'Lunas';
        $invoice->paid_date = $request->paid_date;
        $invoice->payment_id = $request->payment_method_id;
        $invoice->paid_description = $request->description;
        $invoice->save();

        return redirect()->route('piutang.list')->with('success', 'Status Piutang berhasil diupdate menjadi Lunas.');
    }
}
