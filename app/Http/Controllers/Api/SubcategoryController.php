<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function index()
    {
        return Subcategory::with('category')->get();
    }

    public function store(Request $request)
    {
        return Subcategory::create($request->all());
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        $subcategory->update($request->all());
        return $subcategory;
    }

    public function destroy(Subcategory $subcategory)
    {
        $subcategory->delete();
        return response()->noContent();
    }
}
