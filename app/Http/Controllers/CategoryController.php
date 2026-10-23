<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'items' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ]),
        ], 200);
    }
}
