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
            'global_id' => 'required|integer',
        ]);

        $globalId = $validated['global_id'];
        $model = null;

        if ($found = Photo::where('global_id', $globalId)->first()) {
            $model = $found;
        } elseif ($found = Album::where('global_id', $globalId)->first()) {
            $model = $found;
        } elseif ($found = Post::where('global_id', $globalId)->first()) {
            $model = $found;
        }

        if (!$model) return response()->json(['message' => 'Entity not found'], 404);

        $user = $request->user();
        $modelClass = get_class($model);

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
