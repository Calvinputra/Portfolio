<?php

namespace App\Http\Controllers;

use App\Models\BundleProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use ProductBundle;

use function view;

class MainController extends Controller
{

    public function index(Request $request)
    {
        $products = Product::with('category', 'brand', 'stockWarehouse')->get();
        $productBundles = BundleProduct::get();
        $user = User::with('role_user')->find(auth()->id());
        $filter = $request->query('filter', 'monthly');

        $product_count = $products->filter(function ($product) {
            return $product->stockWarehouse->sum('quantity') == 100;
        })->count();

        $product_count_done = $products->filter(function ($product) {
            return $product->stockWarehouse->sum('quantity') != 100;
        })->count();

        // Hitung jumlah produk dengan modal 100 atau 0
        $product_is_low_modal = $products->filter(function ($product) {
            return $product->stockWarehouse->pluck('cost_price')->contains(100) ||
                $product->stockWarehouse->pluck('cost_price')->contains(0);
        })->count();

        $product_is_modal = $products->filter(function ($product) {
            return !$product->stockWarehouse->pluck('cost_price')->contains(100) &&
                !$product->stockWarehouse->pluck('cost_price')->contains(0);
        })->count();

        $product_stock_minus_list = $products->filter(function ($product) {
            return $product->stockWarehouse->sum('quantity') < 0;
        });

        $product_stock_minus_count = $product_stock_minus_list->count();

        $product_stock_minus_details = $product_stock_minus_list->map(function ($product) {
            return "{$product->name} (SKU: {$product->sku})";
        })->implode(', ');

        // dd($products->first());

        // Ambil tanggal dari request (jika ada)
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Tentukan rentang waktu berdasarkan filter atau inputan tanggal manual
        if ($filter === 'custom' && $startDate && $endDate) {
            // Gunakan tanggal yang dipilih user
            $dateRange = [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
        } else {
            // Default berdasarkan filter
            $dateRange = match ($filter) {
                'today' => [now()->startOfDay(), now()->endOfDay()],
                'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
                'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
                'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
                'this_year' => [now()->startOfYear(), now()->endOfYear()],
                'all_time' => [now()->subYears(10), now()],
                default => [now()->startOfDay(), now()->endOfDay()],
            };
        }

        // Hitung total pendapatan, total invoice, dan produk terjual berdasarkan filter

        $totalOmset = DB::table('order_item')
            ->join('sales_invoices', 'order_item.sales_invoice_number', '=', 'sales_invoices.number_invoice')
            ->whereBetween('sales_invoices.date', [$dateRange[0], $dateRange[1]])
            ->selectRaw('
                SUM(order_item.total_price) 
                + SUM(CASE WHEN sales_invoices.other_expenses_status = "plus" THEN sales_invoices.other_expenses_price ELSE 0 END) 
                - SUM(CASE WHEN sales_invoices.other_expenses_status = "minus" THEN sales_invoices.other_expenses_price ELSE 0 END) 
                as total_omset
            ')
            ->value('total_omset');



        // $totalMargin = DB::table('order_item')
        // ->join('sales_invoices', 'order_item.sales_invoice_number', '=', 'sales_invoices.number_invoice')
        // ->join('products', 'order_item.product_id', '=', 'products.id')
        // ->whereBetween('sales_invoices.date', [$dateRange[0], $dateRange[1]])
        // ->selectRaw('SUM(order_item.total_price - (products.cost_price * order_item.quantity)) as total_margin')
        // ->value('total_margin');

        // $totalMargin = DB::table('order_item as oi')
        // ->join('sales_invoices as si', 'oi.sales_invoice_number', '=', 'si.number_invoice')
        // ->join('products as p', 'oi.product_id', '=', 'p.id')
        // ->join('stock_warehouse as sw', 'sw.id', '=', 'oi.stock_warehouse_id')
        // ->whereBetween('si.date', [$dateRange[0], $dateRange[1]])
        // ->selectRaw('SUM(oi.total_price - (sw.cost_price * oi.quantity)) as total_margin')
        // ->value('total_margin');

        $totalMargin = DB::table('order_item as oi')
            ->join('sales_invoices as si', 'oi.sales_invoice_number', '=', 'si.number_invoice')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->join('stock_warehouse as sw', 'sw.id', '=', 'oi.stock_warehouse_id')
            ->whereBetween('si.date', [$dateRange[0], $dateRange[1]])
            ->selectRaw('
        SUM(
            oi.total_price 
            - (sw.cost_price * oi.quantity)
            - ((oi.total_price * IFNULL(si.other_expenses_percent_admin, 0)) / 100)
            + (
                CASE 
                    WHEN si.other_expenses_status = "plus" THEN si.other_expenses_price
                    WHEN si.other_expenses_status = "minus" THEN -si.other_expenses_price
                    ELSE 0
                END
            )
        ) as total_margin
    ')
            ->value('total_margin');


        $totalInvoices = DB::table('sales_invoices')
        ->whereBetween('sales_invoices.date', [$dateRange[0], $dateRange[1]])
        ->count();

        $totalProducts = DB::table('order_item')
        ->join('sales_invoices', 'order_item.sales_invoice_number', '=', 'sales_invoices.number_invoice')
        ->whereBetween('sales_invoices.date', [$dateRange[0], $dateRange[1]])
        ->sum('order_item.quantity');

        // Ambil data untuk grafik
        $salesData = DB::select("
        SELECT 
            DATE(sales_invoices.date) as date, 
            MONTH(sales_invoices.date) as month, 
            YEAR(sales_invoices.date) as year, 
            SUM(order_item.quantity) as total_sales, 
            SUM(order_item.total_price) as total_omset,
            SUM(order_item.total_price - (stock_warehouse.cost_price * order_item.quantity)) as total_margin
        FROM order_item
        JOIN sales_invoices ON sales_invoices.number_invoice = order_item.sales_invoice_number
        JOIN products ON order_item.product_id = products.id
        JOIN stock_warehouse ON order_item.stock_warehouse_id = stock_warehouse.id
        WHERE sales_invoices.date BETWEEN ? AND ?
        GROUP BY YEAR(sales_invoices.date), MONTH(sales_invoices.date), DATE(sales_invoices.date)
        ORDER BY date ASC
        ", [$dateRange[0], $dateRange[1]]);

        $formattedSalesData = collect($salesData)->map(function ($data) use ($filter) {
            if ($filter === 'today' || $filter === 'last_7_days' || $filter === 'last_30_days') {
                return [
                    'label' => date("d M Y", strtotime($data->date)),
                    'total_sales' => $data->total_sales,
                    'total_omset' => $data->total_omset,
                    'total_margin' => $data->total_margin,
                ];
            } else {
                return [
                    'label' => date("d M Y", strtotime($data->date)),
                    // 'label' => date("M Y", mktime(0, 0, 0, $data->month, 1, $data->year)) . ' (' . date("d M Y", strtotime($data->date)) . ')',
                    'total_sales' => $data->total_sales,
                    'total_omset' => $data->total_omset,
                    'total_margin' => $data->total_margin,
                ];
            }
        });


        // Ambil data pelanggan dengan pembelian terbanyak
        $topCustomers = DB::table('sales_invoices')
        ->join('customers', 'sales_invoices.customer_id', '=', 'customers.id')
        ->where('customers.name', '!=', 'Cash')
        ->select('customers.name', DB::raw('COUNT(sales_invoices.id) as total_orders'), DB::raw('SUM(sales_invoices.total) as total_spent'))
        ->groupBy('customers.id', 'customers.name')
        ->orderByDesc('total_orders')
        ->limit(10)
        ->get();

        // Ambil data produk yang paling banyak terjual
        $topProducts = DB::table('order_item')
        ->join('products', 'order_item.product_id', '=', 'products.id')
        ->select('products.description', DB::raw('SUM(order_item.quantity) as total_sold'))
        ->groupBy('products.id', 'products.description')
        ->orderByDesc('total_sold')
        ->limit(10)
        ->get();

        // Total Piutang Customer
        $piutangCustomer = DB::table('sales_invoices')
            ->whereIn('status', ['Ngutang', 'DP'])
            ->selectRaw('COUNT(*) as total_transaksi, SUM(total - IFNULL(total_paid_dp, 0)) as jumlah_uang')
            ->first();

        // Total Hutang Supplier
        $hutangSupplier = DB::table('purchase_invoices')
            ->join('order_item_supplier', 'order_item_supplier.purchase_invoice_number', '=', 'purchase_invoices.number_purchase_invoice')
            ->where('purchase_invoices.status', '=', 'Ngutang')
            ->selectRaw('COUNT(DISTINCT purchase_invoices.number_purchase_invoice) as total_transaksi, SUM(order_item_supplier.price * order_item_supplier.quantity) as jumlah_uang')
            ->first();

        $piutangCustomerOverdue = DB::table('sales_invoices')
            ->whereIn('status', ['Ngutang', 'DP'])
            ->whereNotNull('date')
            ->where('due_date', '>', 0)
            ->whereRaw("DATE_ADD(date, INTERVAL due_date DAY) < ?", [\Carbon\Carbon::now()->toDateString()])
            ->selectRaw('COUNT(*) as total_transaksi, SUM(total - IFNULL(total_paid_dp, 0)) as jumlah_uang')
            ->first();

        // Hutang supplier lewat due date
        $hutangSupplierOverdue = DB::table('purchase_invoices')
            ->join('order_item_supplier', 'order_item_supplier.purchase_invoice_number', '=', 'purchase_invoices.number_purchase_invoice')
            ->where('purchase_invoices.status', '=', 'Ngutang')
            ->whereRaw("DATE_ADD(purchase_invoices.date, INTERVAL purchase_invoices.due_date DAY) < ?", [Carbon::now()])
            ->selectRaw('COUNT(DISTINCT purchase_invoices.number_purchase_invoice) as total_transaksi, SUM(order_item_supplier.price * order_item_supplier.quantity) as jumlah_uang')
            ->first();


        return view('index', [
            'user' => $user,
            'products' => $products,
            'productBundles' => $productBundles,
            'formattedSalesData' => $formattedSalesData,
            'totalInvoices' => $totalInvoices,
            'totalProducts' => $totalProducts,
            'totalOmset' => $totalOmset,
            'totalMargin' => $totalMargin,
            'selectedFilter' => $filter,
            'topCustomers' => $topCustomers,
            'topProducts' => $topProducts,
            'startDate' => $startDate, 
            'product_count' => $product_count,
            'product_count_done' => $product_count_done,
            'product_is_low_modal' => $product_is_low_modal,
            'product_is_modal' => $product_is_modal,
            'product_stock_minus_count' => $product_stock_minus_count,
            'product_stock_minus_details' => $product_stock_minus_details,
            'piutangCustomer' => $piutangCustomer,
            'hutangSupplier' => $hutangSupplier,
            'piutangCustomerOverdue' => $piutangCustomerOverdue,
            'hutangSupplierOverdue' => $hutangSupplierOverdue,
        ]);

    }

    public function exportPDF(Request $request)
    {
        // Ambil filter dari request
        $filter = $request->query('filter', 'monthly');
        $dateRange = $this->getDateRange($filter, $request);

        // Ambil data penjualan
        $sales = OrderItem::join('sales_invoices', 'order_item.sales_invoice_number', '=', 'sales_invoices.number_invoice')
            ->join('products', 'order_item.product_id', '=', 'products.id')
            ->join('stock_warehouse', 'order_item.stock_warehouse_id', '=', 'stock_warehouse.id')
            ->join('payment_method', 'sales_invoices.payment_id', '=', 'payment_method.id')
            ->whereBetween('sales_invoices.date', [$dateRange[0], $dateRange[1]])
            ->select(
                'sales_invoices.date',
                'sales_invoices.number_invoice',
                'sales_invoices.other_expenses',
                'sales_invoices.other_expenses_status',
                'sales_invoices.other_expenses_price',
                'sales_invoices.other_expenses_description',
                'sales_invoices.other_expenses_percent_admin',
                'sales_invoices.status',
                'products.description as product_name',
                'products.sku as sku',
                'stock_warehouse.cost_price',
                'order_item.price as selling_price',
                'order_item.quantity',
                'order_item.total_price',
                'payment_method.name as payment_name',
            )
            ->orderBy('sales_invoices.number_invoice', 'asc')
            ->get();

        // **Hitung total keuntungan per Nota langsung dari `$sales`**
        $profitPerNota = $sales->groupBy('number_invoice')->map(function ($items) {
            $totalSelling = $items->sum('total_price');
            $totalCost = $items->sum(fn($item) => $item->cost_price * $item->quantity);

            $totalExpenses = $items->sum(function ($item) {
                return $item->other_expenses_status == 'plus'
                    ? $item->other_expenses_price
                    : ($item->other_expenses_status == 'minus' ? -$item->other_expenses_price : 0);
            });

            $adminPercent = $items->first()->other_expenses_percent_admin ?? 0;
            $adminCut = ($totalSelling * $adminPercent) / 100;

            $netSelling = $totalSelling - $adminCut;

            return [
                'total_sales' => $totalSelling,
                'total_cost' => $totalCost,
                'total_expenses' => $totalExpenses - $adminCut,
                'admin_cut' => $adminCut,
                'net_sales' => $netSelling,
                'profit' => ($netSelling - $totalCost) + $totalExpenses,
            ];
        });

        // Hitung total keseluruhan
        $totalInvoices = $sales->unique('number_invoice')->count();
        $totalProducts = $sales->sum('quantity');
        $totalCostPrice = $sales->sum(fn($sale) => $sale->cost_price * $sale->quantity);
        $totalSellingPrice = $sales->sum('total_price');

        // Hitung total Other Expenses
        $totalOtherExpenses = $sales->sum(function ($sale) {
            return $sale->other_expenses_status == 'plus'
                ? $sale->other_expenses_price
                : ($sale->other_expenses_status == 'minus' ? -$sale->other_expenses_price : 0);
        });

        // Hitung total keseluruhan dengan Other Expenses
        $totalAdminCut = $sales->groupBy('number_invoice')->sum(function ($items) {
            $adminPercent = $items->first()->other_expenses_percent_admin ?? 0;
            $totalSelling = $items->sum('total_price');
            return ($totalSelling * $adminPercent) / 100;
        });

        $totalProfit = ($totalSellingPrice - $totalAdminCut - $totalCostPrice) + $totalOtherExpenses;

        $paymentTotals = $sales->groupBy('payment_name')->map(function ($items, $paymentName) {
            // Cek apakah metode pembayaran termasuk online/e-commerce
            $isEcommerce = in_array(strtolower($paymentName), ['e-commerce', 'online', 'shopee', 'tokopedia']);

            return $items->sum(function ($item) use ($isEcommerce) {
                if ($isEcommerce) {
                    $adminCut = ($item->total_price * ($item->other_expenses_percent_admin ?? 0)) / 100;
                    return $item->total_price - $adminCut;
                }
                return $item->total_price;
            });
        });

        // Group by invoice
        $groupedByInvoice = $sales->groupBy('number_invoice');

        // Total Hutang: invoice yang status-nya "Ngutang"
        $totalHutang = $groupedByInvoice->filter(function ($items) {
            return $items->first()->status === 'Ngutang';
        })->sum(function ($items) {
            return $items->sum('total_price');
        });

        // Total Sudah Bayar: selain status "Ngutang"
        $totalSudahBayar = $groupedByInvoice->filter(function ($items) {
            return $items->first()->status !== 'Ngutang';
        })->sum(function ($items) {
            return $items->sum('total_price');
        });


        $totalSemuaPembayaran = $totalHutang + $totalSudahBayar + $totalOtherExpenses;
        // Format data untuk PDF
        $data = [
            'sales' => $sales,
            'profitPerNota' => $profitPerNota,
            'date_range' => $dateRange,
            'totalInvoices' => $totalInvoices,
            'totalProducts' => $totalProducts,
            'totalCostPrice' => $totalCostPrice,
            'totalSellingPrice' => $totalSellingPrice,
            'totalOtherExpenses' => $totalOtherExpenses,
            'totalProfit' => $totalProfit,
            'totalAdminCut' => $totalAdminCut,
            'paymentTotals' => $paymentTotals,
            'totalHutang' => $totalHutang,
            'totalSudahBayar' => $totalSudahBayar,
            'totalSemuaPembayaran' => $totalSemuaPembayaran,
        ];

        // Load tampilan PDF
        $pdf = PDF::loadView('exports.sales_pdf', $data)->setPaper('A4', 'portrait');

        // Download PDF
        return $pdf->download('Laporan_Penjualan.pdf');
    }

    private function getDateRange($filter, $request)
    {
        if ($filter === 'custom' && $request->start_date && $request->end_date) {
            return [Carbon::parse($request->start_date)->startOfDay(), Carbon::parse($request->end_date)->endOfDay()];
        }

        return match ($filter) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'all_time' => [now()->subYears(10), now()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }
}
