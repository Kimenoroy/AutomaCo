<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)->get();
        return response()->json(['plans' => $plans]);
    }   
}
