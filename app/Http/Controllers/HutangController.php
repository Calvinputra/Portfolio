<?php

namespace App\Http\Controllers;

use App\Models\BundleProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use ProductBundle;
use PurchaseInvoices;

use function view;

class HutangController extends Controller
{

    public function list()
    {
        $purchase_invoices = PurchaseInvoice::with('order_item_supplier.product','supplier')
        ->where('status', '=', 'Ngutang')->get();
        $payment_methods = PaymentMethod::get();

        return view('hutang.list', [
            'purchase_invoices' => $purchase_invoices,
            'payment_methods' => $payment_methods,
        ]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'purchase_invoice_number' => 'required|exists:purchase_invoices,number_purchase_invoice',
            'paid_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_method,id',
            'description' => 'nullable|string|max:255',
        ]);

        $invoice = PurchaseInvoice::findOrFail($request->purchase_invoice_id);

        $invoice->status = 'Lunas';
        $invoice->paid_date = $request->paid_date;
        $invoice->payment_id = $request->payment_method_id;
        $invoice->paid_description = $request->description;
        $invoice->save();

        return redirect()->route('hutang.list')->with('success', 'Status Hutang berhasil ditandai Lunas.');
    }
}
