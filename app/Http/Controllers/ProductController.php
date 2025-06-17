<?php

namespace App\Http\Controllers;

use App\Imports\ProductImport;
use App\Imports\ProductModalImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Classification;
use App\Models\StockWarehouse;
use App\Models\Customer;
use App\Models\Colour;
use App\Models\Material;
use App\Models\Product;
use App\Models\Shape;
use App\Models\Type;
use App\Models\User;
use Brands;
use Classifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

use function view;

class ProductController extends Controller
{
    public function create()
    {
        $user = User::with('role_user')->find(Auth::id());
        $brands = Brand::all();
        $categories = Category::all();
        $types = Type::all();
        $colours = Colour::all();
        $materials = Material::all();
        $shapes = Shape::all();
        $classifications = Classification::with('categories')->get();

        $lastCategory = Category::orderBy('code', 'desc')->first();
        $newCategoryCode = $lastCategory ? intval($lastCategory->code) + 1 : 1;

        return view('product.create', [
            'user' => $user,
            'brands' => $brands,
            'categories' => $categories,
            'types' => $types,
            'colours' => $colours,
            'materials' => $materials,
            'shapes' => $shapes,
            'newCategoryCode' => $newCategoryCode,
            'classifications' => $classifications,
        ]);
    }

    public function view($id)
    {
        $product = Product::with(['brand', 'category', 'classification', 'type', 'shape', 'material', 'colour', 'stockWarehouse'])
        ->findOrFail($id);
        

        return view('product.view', [
            'product' => $product,
        ]);
    }

    public function productStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'generated_sku' => 'required',
            'brand_id' => 'required',
            'category_id' => 'required',
            'classification' => 'required',
            // 'type' => 'required',
            // 'material' => 'required',
            // 'colour' => 'required',
            // 'cost_price' => 'required',
            'unit' => 'required',
            // 'shape' => 'required',
            'description' => 'required',
        ], [
            // Custom error messages
            'generated_sku.required' => 'Kolom Generated SKU wajib diisi.',
            'brand_id.required' => 'Kolom Brand ID wajib diisi.',
            'category_id.required' => 'Kolom Category ID wajib diisi.',
            'classification.required' => 'Kolom Classification wajib diisi.',
            // 'type.required' => 'Kolom Type wajib diisi.',
            // 'cost_price.required' => 'Kolom Cost Price wajib diisi.',
            'unit.required' => 'Kolom Unit wajib diisi.',
            'description.required' => 'Kolom Description wajib diisi.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $check_sku = Product::where('sku', $request->generated_sku)->first();
        if ($check_sku) {
            return redirect()->route('product.create')->withInput()
                ->with('error', 'SKU sudah pernah dibuat, coba cari dahulu!')
                ->with('existingSku', $check_sku);
        }

        if ($request->new_name != null || $request->new_code) {
            $new_type = new Type;
            $new_type->name = $request->new_name;
            $new_type->code = $request->new_code;
            $new_type->save();

            $new_type_id = $new_type->id;
        } else {
            $new_type_id = $request->type;
        }

        $product = new Product;
        $product->sku = $request->generated_sku;
        $product->brand_id = $request->brand_id;
        $product->categories_id = $request->category_id;
        $product->classification_id = $request->classification;
        $product->type_id = $new_type_id;
        $product->material_id = $request->material;
        $product->colour_id = $request->colour;
        // $product->cost_price = $request->cost_price;
        $product->unit = $request->unit;
        $product->shape_id = $request->shape;
        $product->description = $request->description;
        $product->save();

        return redirect()->route('product.create')->with('success', 'Product Berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $product = Product::find($id);
        $user = User::with('role_user')->find(Auth::id());
        $brands = Brand::all();
        $customers = Customer::all();
        $categories = Category::all();
        $types = Type::all();
        $colours = Colour::all();
        $materials = Material::all();
        $shapes = Shape::all();
        $classifications = Classification::with('categories')->get();

        $lastCategory = Category::orderBy('code', 'desc')->first();
        $newCategoryCode = $lastCategory ? intval($lastCategory->code) + 1 : 1;

        return view('product.edit', [
            'product' => $product,
            'user' => $user,
            'brands' => $brands,
            'customers' => $customers,
            'categories' => $categories,
            'types' => $types,
            'colours' => $colours,
            'materials' => $materials,
            'shapes' => $shapes,
            'newCategoryCode' => $newCategoryCode,
            'classifications' => $classifications,
        ]);
    }

    public function productUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'generated_sku' => 'required',
            'brand_id' => 'required',
            'category_id' => 'required',
            'classification' => 'required',
            // 'cost_price' => 'required',
            'unit' => 'required',
            'type' => 'required',
            'description' => 'required',
        ]);
  
        $product = Product::findOrFail($id);

        if ($request->new_name != null || $request->new_code) {
            $new_type = new Type;
            $new_type->name = $request->new_name;
            $new_type->code = $request->new_code;
            $new_type->save();

            $new_type_id = $new_type->id;
        } else {
            $new_type_id = $request->type;
        }

        $product->sku = $request->generated_sku;
        $product->brand_id = $request->brand_id;
        $product->categories_id = $request->category_id;
        $product->classification_id = $request->classification;
        $product->type_id = $new_type_id;
        $product->material_id = $request->material;
        $product->colour_id = $request->colour;
        // $product->cost_price = $request->cost_price;
        $product->unit = $request->unit;
        $product->shape_id = $request->shape;
        $product->description = $request->description;
        $product->save();

        return redirect()->route('product.edit', $id)->with('success', 'Product Berhasil diperbarui!');
    }

    public function brandStore(Request $request)
    {
        $request->validate([
            'brand_name' => 'required',
            'brand_code' => 'required',
        ]);

        $check_brand = Brand::where('name', $request->brand_name)
            ->orWhere('code', $request->brand_code)
            ->first();

        if ($check_brand) {
            return redirect()->route('product.create')
                ->with('error', 'Brand sudah pernah dibuat, coba cari dahulu!');
        }

        $brand = new Brand;
        $brand->name = $request->brand_name;
        $brand->code = $request->brand_code;

        $brand->save();

        return redirect()->route('product.create')->with('success', 'Brand Berhasil ditambahkan!');
    }

    public function typeStore(Request $request)
    {
        $request->validate([
            'type_name' => 'required',
            'type_code' => 'required',
        ]);

        $check_type = Type::where('name', $request->type_name)
            ->orWhere('code', $request->type_code)
            ->first();

        if ($check_type) {
            return redirect()->route('product.create')
                ->with('error', 'type sudah pernah dibuat, coba cari dahulu!');
        }

        $type = new Type;
        $type->name = $request->type_name;
        $type->code = $request->type_code;

        $type->save();

        return redirect()->route('product.create')->with('success', 'Type Berhasil ditambahkan!');
    }

    public function colourStore(Request $request)
    {
        $request->validate([
            'colour_name' => 'required',
            'colour_code' => 'required',
        ]);

        $check_colour = Colour::where('name', $request->colour_name)
            ->orWhere('code', $request->colour_code)
            ->first();

        if ($check_colour) {
            return redirect()->route('product.create')
                ->with('error', 'colour sudah pernah dibuat, coba cari dahulu!');
        }

        $colour = new Colour;
        $colour->name = $request->colour_name;
        $colour->code = $request->colour_code;

        $colour->save();

        return redirect()->route('product.create')->with('success', 'colour Berhasil ditambahkan!');
    }

    public function materialStore(Request $request)
    {
        $request->validate([
            'material_name' => 'required',
            'material_code' => 'required',
        ]);

        $check_material = Material::where('name', $request->material_name)
            ->orWhere('code', $request->material_code)
            ->first();

        if ($check_material) {
            return redirect()->route('product.create')
                ->with('error', 'material sudah pernah dibuat, coba cari dahulu!');
        }

        $material = new Material;
        $material->name = $request->material_name;
        $material->code = $request->material_code;

        $material->save();

        return redirect()->route('product.create')->with('success', 'material Berhasil ditambahkan!');
    }

    public function shapeStore(Request $request)
    {
        $request->validate([
            'shape_name' => 'required',
            'shape_code' => 'required',
        ]);

        $check_shape = Shape::where('name', $request->shape_name)
            ->orWhere('code', $request->shape_code)
            ->first();

        if ($check_shape) {
            return redirect()->route('product.create')
                ->with('error', 'shape sudah pernah dibuat, coba cari dahulu!');
        }

        $shape = new Shape;
        $shape->name = $request->shape_name;
        $shape->code = $request->shape_code;

        $shape->save();

        return redirect()->route('product.create')->with('success', 'shape Berhasil ditambahkan!');
    }

    public function list()
    {
        $products = Product::with('category', 'brand', 'stockWarehouse', 'shape')->get();
        $user = User::with('role_user')->find(Auth::id());

        return view('product.list', [
            'products' => $products,
            'user' => $user,
        ]);
    }

    public function getBrandCode($id)
    {
        $brand = Brand::find($id);
        if ($brand) {
            return response()->json(['code' => $brand->code]);
        }
        return response()->json(['code' => null]);
    }

    public function getClassificationCode($id)
    {
        $classification = Classification::find($id);
        if ($classification) {
            return response()->json(['code' => $classification->code]);
        }
        return response()->json(['code' => null]);
    }

    public function categoryStore(Request $request)
    {
        $request->validate([
            'category_name' => 'required',
        ]);

        $check_code_category_database = Category::orderBy('code', 'desc')->first();
        $check_code_category = $check_code_category_database ? $check_code_category_database->code : 0;

        $code_category = $check_code_category + 1;

        $check_category = Category::where('name', $request->category_name)
            ->orWhere('code', $code_category)
            ->first();

        if ($check_category) {
            return redirect()->route('product.create')
                ->with('error', 'Category sudah pernah dibuat, coba cari dahulu!');
        }

        $category = new Category;
        $category->name = $request->category_name;
        $category->code = $code_category;
        $category->save();

        return redirect()->route('product.create')->with('success', 'Category Berhasil ditambahkan!');
    }

    public function classificationStore(Request $request)
    {
        $request->validate([
            'classification_name' => 'required',
            'classification_code' => 'required',
            'classification_category' => 'required',
        ]);

        $check_classification = Brand::where('name', $request->classification_name)
            ->orWhere('code', $request->classification_code)
            ->first();
        if ($check_classification) {
            return redirect()->route('product.create')
                ->with('error', 'Classification sudah pernah dibuat, coba cari dahulu!');
        }

        $classification = new classification;
        $classification->name = $request->classification_name;
        $classification->code = $request->classification_code;
        $classification->categories_id = $request->classification_category;

        $classification->save();

        return redirect()->route('product.create')->with('success', 'Classification Berhasil ditambahkan!');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new ProductImport, $request->file('file'));

        return back()->with('success', 'Data Product berhasil diimport!');
    }

    public function importModal(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $import = new ProductModalImport();
        Excel::import($import, $request->file('file'));

        $notFound = $import->getNotFoundProducts();

        if (!empty($notFound)) {
            dd('Produk Tidak Ditemukan:', $notFound);
        }

        return back()->with('success', 'Data Modal Product berhasil diimport!');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $product = Product::findOrFail($id);

            StockWarehouse::where('product_id', $id)->delete();

            // Hapus produk
            $product->delete();
        });
        return redirect()->route('product.list')->with('success', 'product berhasil dihapus.');
    }
    
}
