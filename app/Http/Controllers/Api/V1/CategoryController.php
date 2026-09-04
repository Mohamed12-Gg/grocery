<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //

    public function index(Request $request)
    {
          $categories = Category::query()->select('name','description')->filter($request);

        return response()->json($categories->get());

    }
}