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
    public function index(Request $request)
    {
        $mainCode = strtoupper(trim((string) $request->query('main_code', '')));
        $classification = trim((string) $request->query('classification', ''));
        $search = trim((string) $request->query('search', ''));

        $categoriesQuery = Category::query();

        if ($mainCode !== '') {
            $categoriesQuery->where('main_category_code', $mainCode);
        }

        if ($classification !== '') {
            $categoriesQuery->where('classification', $classification);
        }

        if ($search !== '') {
            $categoriesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('main_category_name', 'like', '%' . $search . '%')
                    ->orWhere('main_category_code', 'like', '%' . $search . '%');
            });
        }

        $categories = $categoriesQuery
            ->orderBy('main_category_code')
            ->orderBy('classification')
            ->orderBy('name')
            ->get();

        $mainCodes = Category::query()
            ->select('main_category_code', 'main_category_name')
            ->orderBy('main_category_code')
            ->get()
            ->groupBy('main_category_code')
            ->map(function ($items) {
                return $items->first()->main_category_name;
            });

        return view('admin.categories', compact('categories', 'mainCodes', 'mainCode', 'classification', 'search'));
    }

    // Add new category
    public function store(Request $request)
    {
        $selectedMainCode = strtoupper((string) $request->input('mainCategoryCode', ''));
        $resolvedMainCode = $selectedMainCode === 'CUSTOM'
            ? strtoupper((string) $request->input('customMainCategoryCode', ''))
            : $selectedMainCode;

        $validated = $request->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($request) {
                    $selectedMainCode = strtoupper((string) $request->input('mainCategoryCode', ''));
                    $resolvedMainCode = $selectedMainCode === 'CUSTOM'
                        ? strtoupper((string) $request->input('customMainCategoryCode', ''))
                        : $selectedMainCode;

                    return $query->where('main_category_code', $resolvedMainCode);
                }),
            ],
            'mainCategoryCode' => 'required|string',
            'customMainCategoryCode' => 'nullable|required_if:mainCategoryCode,custom|string|max:1',
            'mainCategoryName' => 'required|string|max:255',
            'classification' => ['required', Rule::in(['Minor', 'Major', 'Grave'])],
        ]);

        $category = Category::create([
            'name' => $validated['categoryName'],
            'main_category_code' => $resolvedMainCode,
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
