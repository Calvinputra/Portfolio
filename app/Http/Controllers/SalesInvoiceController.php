<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\StockWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesInvoiceController extends Controller
{
    public function create()
    {
        $user = User::with('role_user')->find(Auth::id());

        $customers = Customer::get();
        $warehouses = Warehouse::get();
        $payment_methods = PaymentMethod::get();
        
        $products = Product::with('category', 'brand')->get();
        $warehouses = Warehouse::get();

        return view('sales_invoice.create', [
            'user' => $user,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'payment_methods' => $payment_methods,
            'products' => $products,
        ]);
    }

    public function Store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_method_id' => 'required|exists:payment_method,id',
            'status' => 'required',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.stock_warehouse_id' => 'required|exists:stock_warehouse,id',
            'other_expenses' => 'nullable|string',
            'other_expenses_status' => 'nullable|in:plus,minus',
            'other_expenses_price' => 'nullable|numeric|min:0',
            'other_expenses_description' => 'nullable',
            'other_expenses_percent_admin' => 'nullable',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable',
            'total_paid_dp' => 'nullable',
        ]);
        $total = 0;
        // Simpan Order Item dan kurangi stok
        foreach ($validatedData['products'] as $productData) {
            $total += $productData['quantity'] * $productData['price'];

            // Cek apakah stok tersedia sebelum pengurangan
            $stock = StockWarehouse::where('product_id', $productData['product_id'])
                ->where('id', $productData['stock_warehouse_id'])
                ->first();

            // if (!$stock || $stock->quantity < $productData['quantity']) {
            //     return back()->withErrors(['error' => 'Stok tidak mencukupi untuk produk: ' . $productData['product_id']]);
            // }

            // Kurangi stok jika tersedia
            $stock->quantity -= $productData['quantity'];
            $stock->save();

            // Simpan OrderItem
            OrderItem::create([
                'sales_invoice_number' => $request->sales_invoice_number,
                'product_id' => $productData['product_id'],
                'stock_warehouse_id' => $productData['stock_warehouse_id'],
                'quantity' => $productData['quantity'],
                'price' => $productData['price'],
                'total_price' => $productData['quantity'] * $productData['price'],
            ]);
        }

        // 🔽 Tambahan: potong total dengan persen admin
        $adminPercent = $validatedData['other_expenses_percent_admin'] ?? 0;
        if ($adminPercent > 0) {
            $adminCut = ($total * $adminPercent) / 100;
            $total -= $adminCut;
        }

        // Menyesuaikan biaya tambahan (other_expenses)
        $otherExpensesPrice = $validatedData['other_expenses_price'] ?? 0;
        if (!empty($validatedData['other_expenses_status']) && $otherExpensesPrice > 0) {
            if ($validatedData['other_expenses_status'] === 'minus') {
                $total -= $otherExpensesPrice;
            } else {
                $total += $otherExpensesPrice;
            }
        }
        if ($request->status == 'Lunas') {
            $paid_date = $request->invoice_date;
        }
        // Simpan invoice baru
        $SalesInvoice = SalesInvoice::create([
            'customer_id' => $request->customer_id,
            'payment_id' => $request->payment_method_id,
            'number_invoice' => $request->sales_invoice_number,
            'total' => $total,
            'total_paid_dp' => $request->total_paid_dp ?? 0,
            'other_expenses' => $request->other_expenses ?? null,
            'other_expenses_status' => $request->other_expenses_status ?? 'plus',
            'other_expenses_price' => $request->other_expenses_price ?? 0,
            'other_expenses_description' => $request->other_expenses_description ?? 0,
            'other_expenses_percent_admin' => $request->other_expenses_percent_admin ?? 0,
            'date' => $request->invoice_date ?? null,
            'paid_date' => $paid_date ?? null,
            'due_date' => $request->due_date ?? null,
            'status' => $request->status,
        ]);

        return redirect()->route('sales_invoice.list')->with('success', 'Stock Warehouse Berhasil ditambahkan!');
    }


    public function list()
    {
        $sales_invoices = SalesInvoice::with('order_item.product', 'customer', 'order_item.stock_warehouse')
        ->orderBy('date','desc')->get();

        $user = User::with('role_user')->find(Auth::id());

        return view('sales_invoice.list', [
            'sales_invoices' => $sales_invoices,
            'user' => $user,
        ]);
    }

    public function view($id)
    {
    $sales_invoice = SalesInvoice::with('order_item.product', 'order_item.stock_warehouse.warehouse', 'customer', 'payment_method')->findOrFail($id);
        $user = User::with('role_user')->find(Auth::id());
        return view('sales_invoice.view', [
            'sales_invoice' => $sales_invoice,
            'user' => $user,
        ]);
    }

    public function edit($id)
    {
        $salesInvoice = SalesInvoice::with('order_item')->findOrFail($id);
        $customers = Customer::all();
        $paymentMethods = PaymentMethod::all();
        $products = Product::orderBy('sku', 'asc')->get(); // sort SKU
        $warehouses = Warehouse::all();

        // Group stock warehouse by product_id
        $stockWarehouses = DB::table('stock_warehouse')
            ->join('warehouse', 'stock_warehouse.warehouse_id', '=', 'warehouse.id')
            ->select(
                'stock_warehouse.id as stock_warehouse_id',
                'stock_warehouse.product_id',
                'stock_warehouse.quantity',
                'warehouse.name as warehouse_name'
            )
            ->get()
            ->groupBy('product_id');

        $warehousesGroupedByProduct = $stockWarehouses;

        return view('sales_invoice.edit', compact(
            'salesInvoice',
            'customers',
            'paymentMethods',
            'products',
            'warehouses',
            'warehousesGroupedByProduct'
        ));
    }


    public function update(Request $request, $id)
    {
        // Validasi input
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_method_id' => 'required|exists:payment_method,id',
            'status' => 'required',
            'other_expenses' => 'nullable|string',
            'other_expenses_status' => 'nullable|in:plus,minus',
            'other_expenses_price' => 'nullable|numeric|min:0',
            'other_expenses_description' => 'nullable',
            'other_expenses_percent_admin' => 'nullable',
            'due_date' => 'nullable',
            'total' => 'nullable',
            'total_paid_dp' => 'nullable',
            'date' => 'nullable',
            'products' => 'array|min:1',
            'products.*.product_id' => 'exists:products,id',
            'products.*.quantity' => 'integer|min:1',
            'products.*.price' => 'numeric|min:0',
            'products.*.deleted' => 'numeric|min:0',
            'products_add' => 'nullable|array',
            'products_add.*.product_id' => 'exists:products,id',
            'products_add.*.stock_warehouse_id' => 'exists:stock_warehouse,id',
            'products_add.*.quantity' => 'integer|min:1',
            'products_add.*.price' => 'numeric|min:0',
        ]);

        // Ambil data SalesInvoice dan OrderItem lama
        $salesInvoice = SalesInvoice::with('order_item')->findOrFail($id);

        // Hapus produk jika ada yang dihapus
        if (!empty($validatedData['products'])) {
            foreach ($salesInvoice->order_item as $orderItem) {
                $productData = collect($validatedData['products'])->firstWhere('product_id', $orderItem->product_id);

                if ($productData && isset($productData['deleted']) && $productData['deleted'] == 1) {
                    // Kembalikan stok sebelum menghapus
                    $stock = StockWarehouse::where('id', $orderItem->stock_warehouse_id)->first();
                    if ($stock) {
                        $stock->quantity += $orderItem->quantity;
                        $stock->save();
                    }

                    $orderItem->delete();
                }
            }
        }
        // Update produk yang ada
        if (!empty($validatedData['products'])) {
            foreach ($validatedData['products'] as $productData) {
                if (isset($productData['deleted']) && $productData['deleted'] == 1) {
                    continue;
                }

                if (!isset($productData['order_item_id'])) continue;

                $orderItem = OrderItem::find($productData['order_item_id']);

                if ($orderItem) {
                    // Kembalikan stok lama
                    if ($oldStock = StockWarehouse::find($orderItem->stock_warehouse_id)) {
                        $oldStock->quantity += $orderItem->quantity;
                        $oldStock->save();
                    }

                    // Kurangi stok baru
                    $newStock = StockWarehouse::find($productData['stock_warehouse_id']);
                    if (!$newStock || $newStock->quantity < $productData['quantity']) {
                        return back()->withErrors([
                            'error' => 'Stok tidak mencukupi untuk produk: ' . $productData['product_id']
                        ]);
                    }

                    $newStock->quantity -= $productData['quantity'];
                    $newStock->save();

                    // Update order item
                    $orderItem->update([
                        'stock_warehouse_id' => $productData['stock_warehouse_id'],
                        'product_id' => $productData['product_id'],
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                        'total_price' => $productData['quantity'] * $productData['price'],
                    ]);
                }
            }
        }


        // Tambahkan produk baru jika ada
        if (!empty($validatedData['products_add'])) {
            foreach ($validatedData['products_add'] as $addProductData) {
                $stock = StockWarehouse::where('id', $addProductData['stock_warehouse_id'])->first();

                if (!$stock || $stock->quantity < $addProductData['quantity']) {
                    return back()->withErrors(['error' => 'Stok tidak mencukupi untuk produk: ' . $addProductData['product_id']]);
                }

                OrderItem::create([
                    'sales_invoice_number' => $salesInvoice->number_invoice,
                    'product_id' => $addProductData['product_id'],
                    'stock_warehouse_id' => $addProductData['stock_warehouse_id'],
                    'quantity' => $addProductData['quantity'],
                    'price' => $addProductData['price'],
                    'total_price' => $addProductData['quantity'] * $addProductData['price'],
                ]);

                // Kurangi stok setelah produk ditambahkan
                $stock->quantity -= $addProductData['quantity'];
                $stock->save();
            }
        }

        // Hitung ulang total setelah semua perubahan
        $total = OrderItem::where('sales_invoice_number', $salesInvoice->number_invoice)->sum('total_price');

        // 🔽 Tambahan: potong total dengan persen admin
        $adminPercent = $validatedData['other_expenses_percent_admin'] ?? 0;
        if ($adminPercent > 0) {
            $adminCut = ($total * $adminPercent) / 100;
            $total -= $adminCut;
        }

        // Menyesuaikan biaya tambahan (other_expenses)
        $otherExpensesPrice = $validatedData['other_expenses_price'] ?? 0;
        if (!empty($validatedData['other_expenses_status']) && $otherExpensesPrice > 0) {
            if ($validatedData['other_expenses_status'] === 'minus') {
                $total -= $otherExpensesPrice;
            } else {
                $total += $otherExpensesPrice;
            }
        }

        // Simpan nomor invoice lama sebelum perubahan
        $oldNumberInvoice = $salesInvoice->number_invoice;
        // Cek apakah ada perubahan pada nomor invoice
        $isNumberInvoiceChanged = ($oldNumberInvoice != $request->sales_invoice_number);

        // Cek apakah ada perubahan data sebelum update invoice
        if (
            $salesInvoice->customer_id != $request->customer_id ||
            $salesInvoice->payment_id != $request->payment_method_id ||
            $salesInvoice->total != $total ||
            $salesInvoice->number_invoice != $request->sales_invoice_number ||
            $salesInvoice->date != $request->date ||
            $salesInvoice->total_paid_dp != ($request->total_paid_dp ?? 0) ||
            $salesInvoice->other_expenses != ($request->other_expenses ?? null) ||
            $salesInvoice->other_expenses_status != ($request->other_expenses_status ?? 'plus') ||
            $salesInvoice->other_expenses_price != ($request->other_expenses_price ?? 0) ||
            $salesInvoice->other_expenses_description != ($request->other_expenses_description ?? null) ||
            $salesInvoice->other_expenses_percent_admin != ($request->other_expenses_percent_admin ?? null) ||
            $salesInvoice->due_date != ($request->due_date !== null ? (int) $request->due_date : null) ||
            $salesInvoice->status != $request->status
        ) {
            $salesInvoice->update([
                'customer_id' => $request->customer_id,
                'payment_id' => $request->payment_method_id,
                'total' => $total,
                'number_invoice' => $request->sales_invoice_number,
                'date' => $request->date,
                'total_paid_dp' => $request->total_paid_dp ?? 0,
                'other_expenses' => $request->other_expenses ?? null,
                'other_expenses_status' => $request->other_expenses_status ?? 'plus',
                'other_expenses_price' => $request->other_expenses_price ?? 0,
                'other_expenses_description' => $request->other_expenses_description ?? null,
                'other_expenses_percent_admin' => $request->other_expenses_percent_admin ?? null,
                'due_date' => $request->due_date !== null ? (int) $request->due_date : null,
                'status' => $request->status,
            ]);
        }

        if ($isNumberInvoiceChanged) {
            OrderItem::where('sales_invoice_number', $oldNumberInvoice)
                ->update(['sales_invoice_number' => $request->sales_invoice_number]);
        }
        
        return redirect()->route('sales_invoice.list')->with('success', 'Sales Invoice berhasil diperbarui!');
    }


    public function destroy($id)
    {
        $sales_invoice = SalesInvoice::findOrFail($id);
        $order_items = OrderItem::where('sales_invoice_number', $sales_invoice->number_invoice)->get();

        // Kembalikan stok ke StockWarehouse
        foreach ($order_items as $item) {
            $stock = StockWarehouse::where('product_id', $item->product_id)
            ->where('id', $item->stock_warehouse_id)
            ->first();

            if ($stock) {
                // Tambahkan kembali stok yang sebelumnya dikurangi
                $stock->quantity += $item->quantity;
                $stock->save();
            }
        }

        OrderItem::where('sales_invoice_number', $sales_invoice->number_invoice)->delete();
        $sales_invoice->delete();

        return redirect()->route('sales_invoice.list')->with('success', 'Order Item di Sales Invoice berhasil dihapus dan stok dikembalikan.');
    }

    public function destroySalesInvoice($id)
    {
        $sales_invoice = SalesInvoice::findOrFail($id);
        $sales_invoice->delete();

        return redirect()->route('sales_invoice.list')->with('success', 'Sales Invoice berhasil dihapus dan stok dikembalikan.');
    }

    public function getWarehousesByProduct($productId)
    {
        $warehouses = StockWarehouse::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get(['id', 'quantity', 'warehouse_id', 'cost_price'])
            ->map(function ($stock) {
                return [
                    'stock_warehouse_id' => $stock->id, 
                    'warehouse_id' => $stock->warehouse->id, 
                    'warehouse_name' => $stock->warehouse->name ?? 'Unknown', 
                    'quantity' => $stock->quantity,
                    'cost_price' => $stock->cost_price,
                ];
            });

        return response()->json($warehouses);
    }


    public function getProductDetails($stockWarehouseId)
    {
        $stockWarehouse = StockWarehouse::findOrFail($stockWarehouseId);
        return response()->json([
            'cost_price' => $stockWarehouse->cost_price
        ]);
    }

}
