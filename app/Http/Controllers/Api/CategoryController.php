<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\MealResource;
use App\Models\Category;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    use ApiResponse;
public function index(Request $request): JsonResponse
{
    $categories = Category::active()
        ->ordered()
        ->withCount('meals')
        ->get();

    return self::successResponse('Categories retrieved successfully',CategoryResource::collection($categories));
}
    /**
     * Get single category with meals
     */
    public function show(string $id): JsonResponse
    {
            $category = Category::with(['meals' => function ($query) {
                $query->available()->orderBy('created_at', 'desc');
            }])->findOrFail($id);
            return self::successResponse('Category retrieved successfully',new CategoryResource($category));
        
    }

    /**
     * Get meals by category (paginated)
     */
    public function meals(string $id, Request $request,Category $category): JsonResponse
    {

            $query = $category->meals()->with(['subcategory'])->available();

            // Filter by featured if provided (featured=1/true → featured only, featured=0/false → non-featured only)
            if ($request->has('featured')) {
                $request->boolean('featured') ? $query->featured() : $query->where('is_featured', false);
            }

            // Filter by subcategory if provided
            if ($request->has('subcategory_id')) {
                $query->where('subcategory_id', $request->input('subcategory_id'));
            }

            // Filter by in stock (in_stock=1/true → in stock only, in_stock=0/false → out of stock only)
            if ($request->has('in_stock')) {
                $request->boolean('in_stock') ? $query->inStock() : $query->outOfStock();
            }

            // Sorting (sort_by: created_at|price|rating|title|sold_count, sort_order: asc|desc; "newest" = created_at desc)
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            if ($sortBy === 'newest') {
                $sortBy = 'created_at';
                $sortOrder = 'desc';
            }
            $allowedSortFields = ['created_at', 'price', 'rating', 'title', 'sold_count'];
            if (in_array($sortBy, $allowedSortFields)) {
                if ($sortBy === 'price') {
                    $query->orderByRaw('COALESCE(discount_price, price) ' . $sortOrder);
                } else {
                    $query->orderBy($sortBy, $sortOrder);
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
            $paginator = $query->paginate($perPage);


            $total = $paginator->total();
return self::successResponse(
    $total === 0
        ? 'No products match your filters.'
        : 'Meals retrieved successfully',

    [
        'category' => new CategoryResource($category),

        'meals' => MealResource::collection(
            $paginator->items()
        ),

        'pagination' => [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $total,
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ],

        'empty_message' => $total === 0
            ? 'No products match the applied filters. Try adjusting your filters.'
            : null,
    ]
);
    }
}
