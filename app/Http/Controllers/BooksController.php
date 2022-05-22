<?php

namespace App\Http\Controllers;

use App\Book;
use App\Http\Requests\PostBookRequest;
use App\Http\Resources\BookResource;
use Illuminate\Http\Request;
use DB;
use Validator;
use Rule;

class BooksController extends Controller
{
    public function __construct()
    {
        
    }

    public function index(Request $request)
    {
        $sortableColumns = [
            'title',
            'avg_review',
        ];

        $validator = Validator::make($request->all(), [
            'filter_title' => 'nullable|string',
            'filter_author' => 'nullable|string',
            'order_by' => 'nullable|in:' . implode(',', $sortableColumns),
            'order_dir' => 'nullable|in:asc,desc',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
            ], 400);
        }

        $books = Book::withCount(['reviews as avg_review' => function($query) {
            $query->select(DB::raw('coalesce(avg(review),0)'));
            }])->when($request->order_by, function ($query, $orderBy) use ($request) {
                return $query->orderBy($orderBy, ($request->order_dir != null) ? $request->order_dir : 'asc');
            })->when($request->filter_title, function ($query,$filterTitle) {
                return $query->where('title', 'like', '%' . $filterTitle . '%');
            })->when($request->filter_author, function ($query,$filterAuthor) {
                $query->whereHas("authors", function ($query) use ($filterAuthor) {
                    return $query->whereIn("id", explode(",", $filterAuthor));
                });
            });

        $books = $books->paginate(10, ['*'], 'page', $request->get('page',1));
        
        return response()->json([
            'data' => BookResource::collection($books),
            'links' => [
                'first' => $books->url(1),
                'last' => $books->url($books->lastPage()),
                'prev' => $books->previousPageUrl(),
                'next' => $books->nextPageUrl(),
            ],
            'meta' => [
                "current_page" => $books->currentPage(),
                "from" => $books->firstItem(),
                "last_page" => $books->lastPage(),
                "path" => $books->url(1),
                "per_page" => $books->perPage(),
                "to" => $books->lastItem(),
                "total" => $books->total(),
            ],
        ], 200);
    }

    public function store(PostBookRequest $request)
    {
        DB::beginTransaction();
        try {
            $book = Book::create($request->all());
            $book->authors()->sync($request->authors);
            DB::commit();
            return (new BookResource($book))
                ->response()
                ->setStatusCode(202);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
        
        
    }
}
