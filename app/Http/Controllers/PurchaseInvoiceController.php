<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\OrderItemSupplier;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\StockWarehouse;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseInvoiceController extends Controller
{
    public function create()
    {
        $user = User::with('role_user')->find(Auth::id());

        $suppliers = Supplier::get();
        $warehouses = Warehouse::get();
        $payment_methods = PaymentMethod::get();
        
        $products = Product::with('category', 'brand')->get();
        $warehouses = Warehouse::get();

        return view('purchase_invoice.create', [
            'user' => $user,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'payment_methods' => $payment_methods,
            'products' => $products,
        ]);
    }

    public function Store(Request $request)
    {
        $validatedData = $request->validate([
            'number_purchase_invoice' => 'required',
            'delivery_order_id' => 'nullable',
            'invoice_date' => 'required',
            'supplier_id' => 'required',
            'payment_method_id' => 'required',
            'status' => 'required',
            'due_date' => 'nullable',
            'total_paid_dp' => 'nullable',
            'other_expenses' => 'nullable',
            'other_expenses_status' => 'nullable',
            'other_expenses_description' => 'nullable',
            'other_expenses_amount' => 'nullable',
            'products' => 'required',
            'products.*.product_id' => 'required',
            'products.*.quantity' => 'required',
            'products.*.price' => 'required',
            'products.*.warehouse_id' => 'required',
            'products.*.discount_type' => 'required',
            'products.*.discount' => 'required',
        ]);
        $totalAfterDiscount = 0;

        foreach ($validatedData['products'] as $product) {
            $quantity = $product['quantity'];
            $price = $product['price'];
            $discount = floatval($product['discount']);
            $discountType = $product['discount_type'];

            // Hitung total sebelum diskon
            $productTotal = $quantity * $price;

            // Hitung diskon
            if ($discountType === 'percent') {
                $productTotal -= ($productTotal * ($discount / 100));
            } elseif ($discountType === 'amount') {
                $productTotal -= $discount;
            }

            // Pastikan tidak negatif
            $productTotal = max(0, $productTotal);

            // Harga satuan setelah diskon
            $priceAfterDiscount = $quantity > 0 ? $productTotal / $quantity : 0;

            $totalAfterDiscount += $productTotal;

            // Ambil atau buat stock warehouse
            $stock = StockWarehouse::where('product_id', $product['product_id'])
                ->where('warehouse_id', $product['warehouse_id'])
                ->where('cost_price', $priceAfterDiscount)
                ->first();

            if ($stock) {
                $stock->quantity += $quantity;
                $stock->save();
            } else {
                $stock = StockWarehouse::create([
                    'product_id' => $product['product_id'],
                    'warehouse_id' => $product['warehouse_id'],
                    'quantity' => $quantity,
                    'cost_price' => $priceAfterDiscount,
                ]);
            }

            // Simpan ke Order Item Supplier dengan stock_warehouse_id dari $stock
            OrderItemSupplier::create([
                'purchase_invoice_number' => $request->number_purchase_invoice,
                'product_id' => $product['product_id'],
                'stock_warehouse_id' => $stock->id, // <- diambil dari yang sudah ada atau baru dibuat
                'quantity' => $quantity,
                'price' => $priceAfterDiscount,
                'total_price' => $productTotal,
                'discount' => $product['discount'],
                'discount_type' => $product['discount_type'],
            ]);
        }

        // Tambahkan other expenses (jika ada)
        $otherExpensesAmount = floatval($request->other_expenses_amount ?? 0);
        $grandTotal = $totalAfterDiscount + $otherExpensesAmount;

        if($request->status == 'Lunas'){
            $paid_date = $request->invoice_date;
        }

        // Simpan Purchase Invoice
        PurchaseInvoice::create([
            'supplier_id' => $request->supplier_id,
            'payment_id' => $request->payment_method_id,
            'delivery_order_id' => $request->delivery_order_id,
            'number_purchase_invoice' => $request->number_purchase_invoice,
            'total' => $grandTotal,
            'total_paid_dp' => $request->total_paid_dp,
            'other_expenses' => $request->other_expenses,
            'other_expenses_status' => $request->other_expenses_status,
            'other_expenses_price' => $otherExpensesAmount,
            'other_expenses_description' => $request->other_expenses_description ?? '',
            'date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'paid_date' => $paid_date ?? null,
            'status' => $request->status,
        ]);

        return redirect()->route('purchase_invoice.list')->with('success', 'Stock Warehouse Berhasil ditambahkan!');
    }


    public function list()
    {
        $purchase_invoices = PurchaseInvoice::with('order_item_supplier.product', 'supplier')->get();
        $user = User::with('role_user')->find(Auth::id());

        return view('purchase_invoice.list', [
            'purchase_invoices' => $purchase_invoices,
            'user' => $user,
        ]);
    }

    public function view($id)
    {
        $purchase_invoice = PurchaseInvoice::with('order_item_supplier.product', 'order_item_supplier.stock_warehouse.warehouse', 'supplier', 'payment_method')->findOrFail($id);
        $user = User::with('role_user')->find(Auth::id());

        return view('purchase_invoice.view', [
            'purchase_invoice' => $purchase_invoice,
            'user' => $user,
        ]);
    }

    public function edit($id)
    {
        $purchase_invoice = PurchaseInvoice::with('order_item_supplier.stock_warehouse.warehouse', 'order_item_supplier.product')->findOrFail($id);
        $suppliers = Supplier::all();
        $payment_methods = PaymentMethod::all();
        $products = Product::all();
        $warehouses = Warehouse::all();

        return view('purchase_invoice.edit', compact('purchase_invoice', 'suppliers', 'payment_methods', 'products', 'warehouses'));
    }


    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
            'payment_method_id' => 'required|exists:payment_method,id',
            'status' => 'required|in:Lunas,DP,Ngutang',
            'due_date' => 'nullable|integer',
            'total_paid_dp' => 'nullable|numeric|min:0',
            'other_expenses' => 'nullable|in:Yes,No',
            'other_expenses_description' => 'nullable|string',
            'other_expenses_amount' => 'nullable|numeric|min:0',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.warehouse_id' => 'required|exists:warehouses,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.discount_type' => 'required|in:none,percent,amount',
            'products.*.discount' => 'required|numeric|min:0',
        ]);

        $purchaseInvoice = PurchaseInvoice::with('order_item_supplier')->findOrFail($id);

        // Hapus semua item lama
        foreach ($purchaseInvoice->order_item_supplier as $item) {
            $stock = StockWarehouse::find($item->stock_warehouse_id);
            if ($stock) {
                $stock->quantity -= $item->quantity;
                $stock->save();
            }
            $item->delete();
        }

        $totalAfterDiscount = 0;
        $newItems = [];

        foreach ($validatedData['products'] as $product) {
            $quantity = $product['quantity'];
            $discount = $product['discount'];
            $discountType = $product['discount_type'];

            $price = $product['price']; // Harga setelah diskon
            $priceBeforeDiscount = $price;

            // Hitung harga awal sebelum diskon
            if ($discountType === 'percent') {
                $priceBeforeDiscount = $price / (1 - $discount / 100);
            } elseif ($discountType === 'amount') {
                $priceBeforeDiscount = $price + $discount;
            }

            $totalPrice = $quantity * $price;
            $totalAfterDiscount += $totalPrice;

            // Cari atau buat stock
            $stock = StockWarehouse::where([
                'product_id' => $product['product_id'],
                'warehouse_id' => $product['warehouse_id'],
                'cost_price' => $price
            ])->first();

            if ($stock) {
                $stock->quantity += $quantity;
                $stock->save();
            } else {
                $stock = StockWarehouse::create([
                    'product_id' => $product['product_id'],
                    'warehouse_id' => $product['warehouse_id'],
                    'quantity' => $quantity,
                    'cost_price' => $price,
                ]);
            }

            // Simpan order item supplier baru
            OrderItemSupplier::create([
                'purchase_invoice_number' => $purchaseInvoice->number_purchase_invoice,
                'product_id' => $product['product_id'],
                'stock_warehouse_id' => $stock->id,
                'quantity' => $quantity,
                'price' => $price,
                'total_price' => $totalPrice,
                'discount_type' => $discountType,
                'discount' => $discount,
            ]);
        }

        // Tambahkan other expenses
        $totalAfterDiscount += floatval($request->other_expenses_amount ?? 0);

        // Update purchase invoice
        $purchaseInvoice->update([
            'supplier_id' => $request->supplier_id,
            'payment_id' => $request->payment_method_id,
            'status' => $request->status,
            'due_date' => $request->due_date,
            'total_paid_dp' => $request->total_paid_dp,
            'other_expenses' => $request->other_expenses ?? 'No',
            'other_expenses_description' => $request->other_expenses_description ?? '',
            'other_expenses_price' => $request->other_expenses_amount ?? 0,
            'total' => $totalAfterDiscount,
        ]);

        return redirect()->route('purchase_invoice.list')->with('success', 'Purchase Invoice berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $purchases_invoice = PurchaseInvoice::findOrFail($id);

        $order_items = OrderItemSupplier::where('purchase_invoice_number', $purchases_invoice->number_purchase_invoice)->get();

        // Kembalikan stok ke StockWarehouse
        foreach ($order_items as $item) {
            $stock = StockWarehouse::where('product_id', $item->product_id)
                ->where('id', $item->stock_warehouse_id)
                ->first();

            if ($stock) {
                // Kurangi stok
                $stock->quantity -= $item->quantity;
                $stock->save();

                // Cek apakah stok sekarang 0
                if ($stock->quantity <= 0) {
                    // Cek apakah ada lebih dari 1 data StockWarehouse untuk produk yang sama
                    $stock_count = StockWarehouse::where('product_id', $item->product_id)->count();

                    if ($stock_count > 1) {
                        // Hapus stock ini karena stoknya sudah nol dan ada entri lain untuk produk ini
                        $stock->delete();
                    }
                }
            }
        }

        OrderItemSupplier::where('purchase_invoice_number', $purchases_invoice->number_purchase_invoice)->delete();
        $purchases_invoice->delete();

        return redirect()->route('purchase_invoice.list')->with('success', 'Purchase Invoice berhasil dihapus dan stok dikembalikan.');
    }


    public function getWarehousesByProduct($productId)
    {
        $warehouses = StockWarehouse::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get(['id', 'quantity', 'warehouse_id'])
            ->map(function ($stock) {
                return [
                    'stock_warehouse_id' => $stock->id, 
                    'warehouse_id' => $stock->warehouse->id, 
                    'warehouse_name' => $stock->warehouse->name ?? 'Unknown', 
                    'quantity' => $stock->quantity,
                ];
            });

        return response()->json($warehouses);
    }


    public function getProductDetails($productId)
    {
        $product = Product::findOrFail($productId);
        return response()->json([
            'cost_price' => $product->cost_price
        ]);
    }

}
