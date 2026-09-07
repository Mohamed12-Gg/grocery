<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Http\Resources\FaqCollection;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display a listing of the FAQs.
     */
    public function index(Request $request)
    {
        $query = Faq::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                    ->orWhere('answer', 'LIKE', "%{$search}%");
            });
        }

        $query->ordered();

        $perPage = $request->get('per_page', 15);
        $faqs = $query->paginate($perPage);

        $response = [
            'data' => new FaqCollection($faqs),
        ];

        if ($request->boolean('with_categories', false)) {
            $response['categories'] = Faq::activeCategoriesList();
        }

        return response()->json($response);
    }

    /**
     * Store a newly created FAQ.
     */
    public function store(StoreFaqRequest $request)
    {
        $faq = Faq::create($request->validated());

        return $this->success(
            data: new FaqResource($faq),
            message: 'FAQ created successfully',
            code: 201,
        );
    }

    /**
     * Display the specified FAQ.
     */
    public function show(Faq $faq)
    {
        return new FaqResource($faq);
    }

    /**
     * Update the specified FAQ.
     */
    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faq->update($request->validated());

        return $this->success(
            data: new FaqResource($faq),
            message: 'FAQ updated successfully',
        );
    }

    /**
     * Remove the specified FAQ.
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        return $this->success(message: 'FAQ deleted successfully');
    }

    /**
     * Get all FAQ categories.
     */
    public function categories()
    {
        return $this->success(data: Faq::activeCategoriesList());
    }

    /**
     * Get FAQs by category.
     */
    public function byCategory(string $category)
    {
        $faqs = Faq::active()
            ->category($category)
            ->ordered()
            ->get();

        return FaqResource::collection($faqs);
    }
}