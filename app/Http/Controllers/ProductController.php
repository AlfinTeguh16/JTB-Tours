<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * List products with optional search filter.
     */
    public function index(Request $request)
    {
        try {
            $q = Product::query();

            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function($w) use ($s) {
                    $w->where('name','like',"%{$s}%")
                      ->orWhere('description','like',"%{$s}%");
                });
            }

            $products = $q->orderBy('name')->paginate(20)->withQueryString();

            return view('products.index', compact('products'));
        } catch (\Throwable $e) {
            Log::error('Product.index error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString(), 'query'=>$request->all()]);
            return redirect()->back()->with('error','Gagal memuat daftar produk.');
        }
    }

    /**
     * Show create form.
     */
    public function create()
    {
        try {
            return view('products.create');
        } catch (\Throwable $e) {
            Log::error('Product.create error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form produk.');
        }
    }

    /**
     * Store new product.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            Product::create($data);
            DB::commit();
            return redirect()->route('products.index')->with('success','Product berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product.store error: '.$e->getMessage(), ['payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal membuat product.');
        }
    }

    /**
     * (No show page — use modal in index)
     * But keep method if needed for API/direct link.
     */
    public function show(Product $product)
    {
        try {
            return view('products.show', compact('product')); // optional; UI uses modal
        } catch (\Throwable $e) {
            Log::error('Product.show error: '.$e->getMessage(), ['product_id'=>$product->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka produk.');
        }
    }

    /**
     * Edit form.
     */
    public function edit(Product $product)
    {
        try {
            return view('products.edit', compact('product'));
        } catch (\Throwable $e) {
            Log::error('Product.edit error: '.$e->getMessage(), ['product_id'=>$product->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal membuka form edit produk.');
        }
    }

    /**
     * Update product.
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            $product->update($data);
            DB::commit();
            return redirect()->route('products.index')->with('success','Product diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product.update error: '.$e->getMessage(), ['product_id'=>$product->id, 'payload'=>$data, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error','Gagal memperbarui product.');
        }
    }

    /**
     * Destroy product — prevent deletion if product has orders.
     */
    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            if ($product->orders()->exists()) {
                return redirect()->back()->with('error','Tidak dapat menghapus product yang masih punya order.');
            }

            $product->delete();
            DB::commit();
            return redirect()->route('products.index')->with('success','Product dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product.destroy error: '.$e->getMessage(), ['product_id'=>$product->id, 'trace'=>$e->getTraceAsString()]);
            return redirect()->back()->with('error','Gagal menghapus product.');
        }
    }
}
