<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Helpers\ActivityLogger;

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

    $category = Category::create([
    'name' => $request->categoryName
]);

ActivityLogger::log(
    'Created Category',
    null,
    null,
    null,
    'Category name: ' . $category->name
);


    ActivityLogger::log(
        'Created Category',
        null,
        null,
        null,
        'Category name: ' . $category->name
    );

    return redirect()
        ->route('admin.categories.index')
        ->with('success', 'Category added successfully!');
}

    // Delete category
    public function destroy($id)
{
    $category = Category::findOrFail($id);
    $categoryName = $category->name;

    $category->delete();

    ActivityLogger::log(
        'Deleted Category',
        null,
        null,
        null,
        'Category name: ' . $categoryName
    );

    return redirect()
        ->route('admin.categories.index')
        ->with('success', 'Category deleted successfully!');
}

}
