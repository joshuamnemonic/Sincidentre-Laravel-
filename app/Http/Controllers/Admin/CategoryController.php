<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Show all categories
    public function index()
    {
        $categories = Category::orderBy('created_at', 'desc')->get();
        return view('admin.categories', compact('categories'));
    }

    // Add new category
    public function store(Request $request)
    {
        $request->validate([
            'categoryName' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create(['name' => $request->categoryName]);

        return redirect()->route('admin.categories.index')->with('success', 'Category added successfully!');
    }

    // Delete category
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully!');
    }
}
