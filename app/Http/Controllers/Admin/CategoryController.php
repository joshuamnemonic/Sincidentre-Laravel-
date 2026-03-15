<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Helpers\ActivityLogger;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // Show all categories
    public function index()
    {
        $categories = Category::orderBy('main_category_code')
            ->orderBy('classification')
            ->orderBy('name')
            ->get();

        return view('admin.categories', compact('categories'));
    }

    // Add new category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($request) {
                    return $query->where('main_category_code', strtoupper($request->mainCategoryCode));
                }),
            ],
            'mainCategoryCode' => 'required|string|max:1',
            'mainCategoryName' => 'required|string|max:255',
            'classification' => ['required', Rule::in(['Minor', 'Major', 'Grave'])],
        ]);

        $category = Category::create([
            'name' => $validated['categoryName'],
            'main_category_code' => strtoupper($validated['mainCategoryCode']),
            'main_category_name' => $validated['mainCategoryName'],
            'classification' => $validated['classification'],
        ]);

        ActivityLogger::log(
            'Created Category',
            null,
            null,
            null,
            'Category: ' . $category->main_category_code . ' - ' . $category->name . ' (' . $category->classification . ')'
        );

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category added successfully!');
    }

    

}
