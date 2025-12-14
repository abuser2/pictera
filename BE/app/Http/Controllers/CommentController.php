<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Photo;
use App\Models\Album;
use App\Models\Post;

class CommentController extends Controller
{
    // 
    public function index(Request $request, $globalId)
    {
        $model = $this->resolveModel($globalId);
        if (!$model) return response()->json(['message' => 'Entity not found'], 404);

        $comments = Comment::where('commentable_type', get_class($model))
            ->where('commentable_id', $model->id)
            ->with('user') // 
            ->latest()
            ->get();

        return response()->json($comments);
    }

    // 
    public function store(Request $request)
    {
        $validated = $request->validate([
            'global_id' => 'required|integer',
            'content' => 'required|string|max:1000',
        ]);

        $model = $this->resolveModel($validated['global_id']);
        if (!$model) return response()->json(['message' => 'Entity not found'], 404);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_id' => $model->id,
            'commentable_type' => get_class($model),
            'content' => $validated['content'],
        ]);

        $model->increment('comments_count');

        return response()->json($comment->load('user'), 201);
    }

    public function destroy(Request $request, Comment $comment)
    {
        if ($request->user()->id !== $comment->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->commentable->decrement('comments_count');
        $comment->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function resolveModel($globalId) {
        if ($found = Photo::where('global_id', $globalId)->first()) return $found;
        if ($found = Album::where('global_id', $globalId)->first()) return $found;
        if ($found = Post::where('global_id', $globalId)->first()) return $found;
        return null;
    }
}
