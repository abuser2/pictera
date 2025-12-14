<?php

namespace App\Http\Controllers;

use App\Models\GlobalId;
use App\Models\Post;
use App\Models\Photo;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // получить все посты
    public function index()
    {
        return Post::with('user', 'photos')->latest()->get();
    }

    // создать пост
    public function store(Request $request)
    {
        $validated = $request->validate([
            'caption' => 'nullable|string|max:2000',
            'visibility' => 'in:public,private,unlisted',
            'photo_ids' => 'required|array|min:1',
            'photo_ids.*' => 'integer|exists:photos,id',
        ]);

        $globalId = GlobalId::create()->id;

        // автор текущий пользователь
        $post = new Post([
            'user_id' => $request->user()->id,
            'caption' => $validated['caption'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
        ]);
        $post->global_id = $globalId;
        $post->save();

        // привязать фотографии
        $post->photos()->sync($validated['photo_ids']);

        return response()->json([
            'message' => 'Пост успешно создан',
            'post' => $post->load('photos'),
        ], 201);
    }

    // показать конкретный пост
    public function show(Post $post)
    {
        return $post->load('user', 'photos');
    }
    public function update(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'caption' => 'nullable|string|max:2000',
            'visibility' => 'in:public,private,unlisted',
            'photo_ids' => 'sometimes|array|min:1',
            'photo_ids.*' => 'integer|exists:photos,id',
        ]);

        if (isset($validated['photo_ids'])) {
            // убедиться, что пользователь владеет всеми фото
            $validIds = Photo::whereIn('id', $validated['photo_ids'])
                ->where('user_id', $request->user()->id)
                ->pluck('id')
                ->toArray();

            if (count($validIds) !== count($validated['photo_ids'])) {
                return response()->json(['message' => 'One or more photos not found or not yours'], 422);
            }

            $post->photos()->sync($validIds);
        }

        $post->caption = $validated['caption'] ?? $post->caption;
        $post->visibility = $validated['visibility'] ?? $post->visibility;
        $post->save();

        return response()->json(['message' => 'Updated', 'post' => $post->load('user','photos')], 200);
    }

    // удалить пост
    public function destroy(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $post->photos()->detach();
        $post->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }
    public function removePhoto(Request $request, Post $post, Photo $photo)
    {
        // только владелец поста может удалять фото
        if ($request->user()->id !== $post->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // проверить, прикреплено ли фото к посту
        if (! $post->photos()->where('photos.id', $photo->id)->exists()) {
            return response()->json(['message' => 'Photo not attached to post'], 404);
        }

        $post->photos()->detach($photo->id);

        return response()->json([
            'message' => 'Photo removed',
            'post' => $post->load('user','photos')
        ], 200);
    }
}
