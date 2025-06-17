<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BundleProduct;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Product;
use App\Models\User;
use Brands;
use Classifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

use function view;

class ProductBundleController extends Controller
{
    public function create()
    {
        $user = User::with('role_user')->find(Auth::id());

        $products = Product::with('category', 'brand')->get();

        return view('product_bundle.create', [
            'user' => $user,
            'products' => $products,
        ]);
    }

    public function productBundleStore(Request $request)
    {
        $validatedData = $request->validate([
            'bundle_name' => 'required',
            'bundle_price' => 'required',
            'products' => 'required',
            'products.*.product_id' => 'required',
        ]);

        $bundleProductIds = collect($validatedData['products'])->pluck('product_id')->implode(',');

        $productBundle = new BundleProduct;
        $productBundle->name = $validatedData['bundle_name'];
        $productBundle->total_price = $validatedData['bundle_price'];
        $productBundle->product_id = $bundleProductIds;

        $productBundle->save();

        return redirect()->route('product.bundle.create')->with('success', 'Product Bundle berhasil ditambahkan!');
    }


    public function destroy($id)
    {
        $product_bundle = BundleProduct::findOrFail($id);
        $product_bundle->delete();

        return redirect()->route('dashboard')->with('success', 'Product Bundle berhasil dihapus.');
    }
}
