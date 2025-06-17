<?php

namespace App\Http\Controllers;

use App\Imports\StockWarehouseImport;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class StockWarehouseController extends Controller
{
    public function create()
    {
        $user = User::with('role_user')->find(Auth::id());

        $warehouses = Warehouse::get();
        $products = Product::with('category', 'brand')->get();
        return view('database.stock_warehouse.create', [
            'user' => $user,
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    public function stockWarehouseStore(Request $request)
    {
        $validatedData = $request->validate([
            'warehouse_id' => 'required',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.cost_price' => 'required',
        ]);

        foreach ($validatedData['products'] as $productData) {
            StockWarehouse::create([
                'warehouse_id' => $validatedData['warehouse_id'],
                'product_id' => $productData['product_id'],
                'quantity' => $productData['quantity'],
                'cost_price' => $productData['cost_price'],
            ]);
        }

        return redirect()->route('stock_warehouse.list.product')->with('success', 'Stock Warehouse Berhasil ditambahkan!');
    }

    // public function test()
    // {
    //     // Ambil semua produk dengan cost_price
    //     $products = Product::whereNotNull('cost_price')->pluck('cost_price', 'id');

    //     // Ambil semua StockWarehouse yang memiliki product_id yang sesuai dengan Product
    //     $stockWarehouses = StockWarehouse::whereIn('product_id', $products->keys())->get();

    //     // Array untuk menyimpan data yang tidak cocok
    //     $mismatch = [];

    //     // Loop untuk memeriksa apakah cost_price sesuai
    //     foreach ($stockWarehouses as $stock) {
    //         if ($stock->cost_price != $products[$stock->product_id]) {
    //             $mismatch[] = [
    //                 'product_id' => $stock->product_id,
    //                 'product_cost_price' => $products[$stock->product_id],
    //                 'stock_warehouse_cost_price' => $stock->cost_price,
    //                 'stock_warehouse_id' => $stock->id
    //             ];
    //         }
    //     }

    //     // Cek apakah ada perbedaan
    //     if (empty($mismatch)) {
    //         dd('Semua cost_price sudah sesuai.');
    //     } else {
    //         dd('Ada perbedaan cost_price:', $mismatch);
    //     }
    // }



    public function listProduct()
    {
        $stock_warehouses = StockWarehouse::with('product','warehouse')->get();
        $user = User::with('role_user')->find(Auth::id());

        $grouped_stock = $stock_warehouses->groupBy('product_id')->map(function ($items) {
            return [
                'product' => $items->first()->product,
                'total_quantity' => $items->sum('quantity'),
                'cost_price' => $items->first()->cost_price,
                'warehouses' => $items->map(function ($item) {
                    return [
                        'id' => $item->warehouse->id ?? null,
                        'name' => $item->warehouse->name ?? 'N/A',
                        'stock_warehouse_id' => $item->id,
                        'quantity' => $item->quantity,
                        'cost_price' => $item->cost_price,
                    ];
                }),
            ];
        });


        return view('database.stock_warehouse.list_product', [
            'grouped_stock' => $grouped_stock,
            'stock_warehouses' => $stock_warehouses,
            'user' => $user,
        ]);
    }

    public function exportStockPDF()
    {
        // Ambil data stock warehouse yang quantity-nya 100
        $stocks = StockWarehouse::with(['product.classification', 'product.category', 'warehouse'])
            ->where(function ($query) {
                $query->where('quantity', 100)
                    ->orWhere('quantity', '<', 0);
            })
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = optional($items->first())->product;

                return [
                    'product' => $product,
                    'total_quantity' => $items->sum('quantity'),
                    'warehouses' => $items->map(function ($item) {
                        return [
                            'stock_warehouse_id' => $item->id,
                            'name' => $item->warehouse->name ?? 'Unknown Warehouse',
                            'quantity' => $item->quantity,
                        ];
                    })->toArray()
                ];
            })
            ->sortBy(function ($item) {
                return $item['product']->category->name ?? ''; // or 'code' or 'id'
            })
            ->values();

        // Kirim ke blade
        $data = [
            'grouped_stock' => $stocks,
        ];

        // return view('exports.stock_pdf', $data); 
        $pdf = Pdf::loadView('exports.stock_pdf', $data)->setPaper('A4', 'portrait');
        return $pdf->download('Laporan_Stock_Warehouse.pdf');
    }


    public function listWarehouse()
    {
        $stockWarehouses = StockWarehouse::with('product', 'warehouse')->get();
        $user = User::with('role_user')->find(Auth::id());

        // Mengelompokkan stok berdasarkan warehouse
        $grouped_by_warehouse = $stockWarehouses->groupBy('warehouse.name')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'stock_id' => $item->id ?? 'No id',
                    'sku' => $item->product->sku ?? 'No SKU',
                    'name' => $item->product->description ?? 'No Description',
                    'quantity' => $item->quantity ?? 'No quantity',
                    'cost_price' => $item->cost_price ?? 'No cost_price',
                ];
            });
        });

        return view('database.stock_warehouse.list_warehouse', [
            'user' => $user,
            'grouped_by_warehouse' => $grouped_by_warehouse,
        ]);
    }


    public function updateQuantity(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer',
        ]);

        $productId = $request->input('product_id');

        $stockWarehouse = StockWarehouse::with('product', 'warehouse')
            ->where('id', $id) 
            ->whereHas('product', function ($query) use ($productId) {
                $query->where('id', $productId);
            })
            ->first();
        if (!$stockWarehouse) {
            return redirect()->route('stock_warehouse.list.product')->with('error', 'Stock warehouse not found.');
        }

        // Update kuantitas
        $stockWarehouse->quantity = $request->input('quantity');
        $stockWarehouse->save();

        return redirect()->route('stock_warehouse.list.product')->with('success', 'Stock quantity updated successfully!');
    }

    public function edit($id)
    {
        $stockWarehouse  = StockWarehouse::with('product' ,'warehouse')->findOrFail($id);
        $warehouses = Warehouse::get();
        $products = Product::with('category', 'brand')->get();
        
        return view('database.stock_warehouse.edit', [
            'stockWarehouse' => $stockWarehouse,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }


    public function stockWarehouseUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required',
            'cost_price' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $stockWarehouse = StockWarehouse::findOrFail($id);
        // Update data
        $stockWarehouse->warehouse_id = $request->warehouse_id;
        $stockWarehouse->product_id = $request->product_id;
        $stockWarehouse->quantity = $request->quantity;
        $stockWarehouse->cost_price = $request->cost_price;
        $stockWarehouse->save();

        return redirect()->route('stock_warehouse.list.product')->with('success', 'Stock Warehouse berhasil diperbarui!');
    }


    public function destroy($id)
    {
        $stock_warehouse = StockWarehouse::findOrFail($id);
        $stock_warehouse->delete();

        return redirect()->route('stock_warehouse.list.product')->with('success', 'StockWarehouse berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required',
        ]);

        Excel::import(new StockWarehouseImport, $request->file('file'));

        return back()->with('success', 'Data Product Stock Warehouse berhasil diimport!');
    }

    public function addStockDummy(){

        $productIds = Product::whereDoesntHave('stockWarehouse')->pluck('id');
        
        foreach($productIds as $productId){
            $stock_warehouse = new StockWarehouse;
            $stock_warehouse->product_id = $productId;
            $stock_warehouse->warehouse_id = 1;
            $stock_warehouse->quantity = 100;
            $stock_warehouse->save(); 
        }

    }

}
