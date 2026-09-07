<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\SubCategoryResource;
use App\Http\Resources\Api\MealResource;
use App\Models\Subcategory;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    /**
     * Get all subcategories
     */
    use ApiResponse;
    public function index(Request $request): JsonResponse
    {
            $query = Subcategory::with('category')->active();

            // Filter by category if provided
            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            $subcategories = $query->inRandomOrder()->get();

            return self::successResponse('Subcategories retrieved successfully',SubcategoryResource::collection($subcategories));
    }

    /**
     * Get single subcategory
     */
    public function show(string $id): JsonResponse
    {
            $subcategory = Subcategory::with(['category', 'meals' => function ($query) {
                $query->available()->limit(10);
            }])->findOrFail($id);

            return self::successResponse('Subcategory retrieved successfully',new SubCategoryResource($subcategory));      
    }

    /**
     * Get meals by subcategory (paginated)
     */
public function meals(
    Subcategory $subcategory,
    Request $request
): JsonResponse {
    
    $query = $subcategory->meals()
        ->with('category')
        ->available();

    // Filter by featured
    if ($request->has('featured')) {
        $request->boolean('featured')
            ? $query->featured()
            : $query->where('is_featured', false);
    }

    // Filter by stock
    if ($request->has('in_stock')) {
        $request->boolean('in_stock')
            ? $query->inStock()
            : $query->outOfStock();
    }

    // Sorting
    $sortBy = $request->input('sort_by', 'created_at');

    $sortOrder = strtolower(
        $request->input('sort_order', 'desc')
    ) === 'asc'
        ? 'asc'
        : 'desc';

    if ($sortBy === 'newest') {
        $sortBy = 'created_at';
        $sortOrder = 'desc';
    }

    $allowedSortFields = [
        'created_at',
        'price',
        'rating',
        'title',
        'sold_count',
    ];

    if (in_array($sortBy, $allowedSortFields)) {

        if ($sortBy === 'price') {
            $query->orderByRaw(
                'COALESCE(discount_price, price) ' . $sortOrder
            );
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

    } else {
        $query->orderBy('created_at', 'desc');
    }

    // Pagination
    $perPage = min(
        max((int) $request->input('per_page', 15), 1),
        50
    );

    $paginator = $query
        ->paginate($perPage)
        ->withQueryString();

    $total = $paginator->total();

    return self::successResponse(
        $total === 0
            ? 'No products match your filters.'
            : 'Meals retrieved successfully',

        [
            'subcategory' => [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'slug' => $subcategory->slug,
            ],

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
