<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with(['category', 'subcategory'])->get();
    }

    public function store(Request $request)
    {
        return Product::create($request->all());
    }

    public function update(Request $request, Product $product)
    {
        $product->update($request->all());
        return $product;
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }
}
