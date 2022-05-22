<?php

namespace App\Http\Controllers;

use App\BookReview;
use App\Http\Requests\PostBookReviewRequest;
use App\Http\Resources\BookReviewResource;
use Illuminate\Http\Request;
use App\Book;

class BooksReviewController extends Controller
{
    public function __construct()
    {
        
    }

    public function store(int $bookId, PostBookReviewRequest $request)
    {

        $book = Book::findOrFail($bookId);
        if(!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }
        $bookReview = new BookReview();
        $bookReview->book_id = $bookId;
        $bookReview->review = $request->get('review');
        $bookReview->comment = $request->get('comment');
        $bookReview->user_id = auth()->user()->id;
        $bookReview->save();
        return response()->json(['data' => new BookReviewResource($bookReview)], 201);
    }

    public function destroy(int $bookId, int $reviewId, Request $request)
    {
       $book = Book::findOrFail($bookId);
         if(!$book) {
              return response()->json(['message' => 'Book not found'], 404);
         }
         $review = BookReview::findOrFail($reviewId);
         $review->delete();

         return response()->json(['message' => 'Review deleted'], 204);
    }
}
