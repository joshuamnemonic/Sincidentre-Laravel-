<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MyReportsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $search = trim((string) $request->input('search', ''));
        $range = $request->input('range');
        $from = $request->input('from');
        $to = $request->input('to');
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $selectedStatuses = collect((array) $request->input('status', []))
            ->map(fn ($status) => Report::normalizeStatus((string) $status))
            ->unique()
            ->values()
            ->all();

        $allowedSort = ['id', 'status', 'incident_date', 'submitted_at', 'updated_at'];
        $sort = in_array($request->input('sort'), $allowedSort, true)
            ? $request->input('sort')
            : 'submitted_at';
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Report::query()
            ->with('category')
            ->where('user_id', $userId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });

                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if (!empty($selectedStatuses)) {
            $query->whereIn('status', $selectedStatuses);
        }

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if ($range === '7d') {
            $query->whereDate('submitted_at', '>=', now()->subDays(7));
        } elseif ($range === '30d') {
            $query->whereDate('submitted_at', '>=', now()->subDays(30));
        } elseif ($range === 'custom') {
            if (!empty($from)) {
                try {
                    $query->whereDate('submitted_at', '>=', Carbon::parse($from)->startOfDay());
                } catch (\Exception $e) {
                    // Ignore invalid date input and keep results accessible.
                }
            }

            if (!empty($to)) {
                try {
                    $query->whereDate('submitted_at', '<=', Carbon::parse($to)->endOfDay());
                } catch (\Exception $e) {
                    // Ignore invalid date input and keep results accessible.
                }
            }
        }

        if ($sort === 'submitted_at') {
            $query->orderByRaw("COALESCE(submitted_at, created_at) {$direction}");
        } else {
            $query->orderBy($sort, $direction);
        }

        $myReports = $query->paginate(12)->withQueryString();
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('user.myreports', compact(
            'myReports',
            'categories',
            'search',
            'selectedStatuses',
            'categoryId',
            'range',
            'from',
            'to',
            'sort',
            'direction'
        ));
    }
}
