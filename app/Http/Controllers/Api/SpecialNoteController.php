<?php

namespace App\Http\Controllers\Api;

use App\Models\SpecialNote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SpecialNoteResource;

class SpecialNoteController extends Controller
{
    use \App\Traits\V1\ApiResponse;
    public function index()
    {
        $specialNotes = SpecialNote::all();
        return self::successResponse('Special notes retrieved successfully', SpecialNoteResource::collection($specialNotes));
    }
}
