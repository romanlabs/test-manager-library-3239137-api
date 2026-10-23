<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::where('deleted_at', null);

        if ($request->has('q')) {
            $q = $request->input('q');
            $query->where(function ($q_builder) use ($q) {
                $q_builder->where('title', 'ilike', "%$q%")
                         ->orWhere('author', 'ilike', "%$q%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $page = max(1, $request->input('page', 1));
        $size = min(50, max(1, $request->input('size', 20)));
        $total = $query->count();

        $books = $query->orderBy('title')
                      ->forPage($page, $size)
                      ->get();

        return response()->json([
            'items' => $books->map(fn($b) => $this->formatBook($b)),
            'page' => $page,
            'size' => $size,
            'total' => $total,
        ], 200);
    }

    public function show($id)
    {
        $book = Book::where('id', $id)->where('deleted_at', null)->first();

        if (!$book) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'El libro no existe.',
                'details' => []
            ], 404);
        }

        return response()->json($this->formatBook($book), 200);
    }

    private function formatBook($book)
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'category' => [
                'id' => $book->category->id,
                'name' => $book->category->name,
            ],
            'total_copies' => $book->total_copies,
            'available_copies' => $book->available_copies,
            'cover_key' => $book->cover_key,
        ];
    }
}
