<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;

class ReviewController extends Controller {
    public function index() {
        return Review::all();
    }

    public function store(Request $request) {
        return Review::create($request->all());
    }
}
