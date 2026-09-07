<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaticPageResource;
use App\Http\Resources\StaticPageCollection;
use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaticPageController extends Controller
{
    use \App\Traits\V1\ApiResponse;
    /**
     * Display a listing of static pages.
     */
    public function index(Request $request)
    {
        $query = StaticPage::query();

        // Show only published pages for non-admin users
        if (!$request->user() || !$request->user()->is_admin) {
            $query->published();
        }

        // Filter by published status
        if ($request->has('published')) {
            $query->where('is_published', $request->boolean('published'));
        }

        // Search in title and content
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        $query->ordered();

        $perPage = $request->get('per_page', 20);
        $pages = $query->paginate($perPage);

        return successResponse('Pages retrieved successfully', new StaticPageCollection($pages));
    }

    /**
     * Store a newly created static page.
     */
    public function store(Request $request)
    {
        $validator = $request->validate([
            'slug' => 'required|string|unique:static_pages,slug|max:100',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|array',
            'is_published' => 'boolean',
            'order' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return self::errorResponse('Validation failed', $validator->errors(), 422);
        }

        $page = StaticPage::create($validator->validated());

        return self::successResponse('Page created successfully', new StaticPageResource($page), 201);
    }

    /**
     * Display the specified static page by slug.
     */
    public function showBySlug($slug)
    {
        $page = StaticPage::bySlug($slug)->first();

        if (!$page) {
            return self::errorResponse('Page not found', null, 404);
        }

        // Check if page is published for non-admin users
        if (!$page->is_published && (!request()->user() || !request()->user()->is_admin)) {
            return self::errorResponse('Page not found', null, 404);
        }

        return new StaticPageResource($page);
    }

    /**
     * Display the specified static page by ID.
     */
    public function show(StaticPage $staticPage)
    {
        return new StaticPageResource($staticPage);
    }

    /**
     * Update the specified static page.
     */
    public function update(Request $request, StaticPage $staticPage)
    {
        $validator = $request->validate([
            'slug' => 'sometimes|required|string|max:100|unique:static_pages,slug,' . $staticPage->id,
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|array',
            'is_published' => 'sometimes|boolean',
            'order' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return self::errorResponse('Validation failed', $validator->errors(), 422);
        }

        $staticPage->update($validator->validated());

        return self::successResponse('Page updated successfully', new StaticPageResource($staticPage));
    }

    /**
     * Remove the specified static page.
     */
    public function destroy(StaticPage $staticPage)
    {
        $staticPage->delete();

        return self::successResponse('Page deleted successfully', null);
    }

    /**
     * Get important pages (for footer/menu).
     */
    public function importantPages()
    {
        $pages = StaticPage::published()
            ->whereIn('slug', ['terms-and-conditions', 'policies', 'about-us', 'contact-us'])
            ->ordered()
            ->get(['slug', 'title']);

        return self::successResponse('Important pages retrieved successfully', $pages);
    }
}