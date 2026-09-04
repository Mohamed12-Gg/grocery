<?php
namespace App\Http\Controllers\Api\v1\mealController {

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\Meal;


    class mealController extends Controller{
        public function index(Request $request){

            $meals = Meal::query()->filter($request);
            return response()->json($meals->get());
        }
    }
}


