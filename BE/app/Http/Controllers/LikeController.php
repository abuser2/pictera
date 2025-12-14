<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use App\Models\Photo;
use App\Models\Album;
use App\Models\Post;

class LikeController extends Controller
{
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:photo,album,post',
            'id' => 'required|integer',
        ]);

        $modelClass = match($validated['type']) {
            'photo' => Photo::class,
            'album' => Album::class,
            'post'  => Post::class,
        };

        $model = $modelClass::findOrFail($validated['id']);
        $user = $request->user();

        // Ищем существующий лайк
        $like = $user->likes()->where('likeable_id', $model->id)->where('likeable_type', $modelClass)->first();

        if ($like) {
            $like->delete();
            $model->decrement('likes_count');
            $liked = false;
        } else {
            $user->likes()->create([
                'likeable_id' => $model->id,
                'likeable_type' => $modelClass,
            ]);
            $model->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $model->refresh()->likes_count,
        ]);
    }
}
