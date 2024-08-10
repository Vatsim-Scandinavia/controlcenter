<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display all positions
     */
    public function index()
    {
        $positions = Position::select(['callsign', 'name', 'frequency'])->get();
        return response()->json(['data' => $positions], 200);
    }
}
