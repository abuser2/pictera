<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Share;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ShareController extends Controller
{
    public function create(Request $req) {
        $data = $req->validate([
            'type'=>'required|in:photo,album,post',
            'id'=>'required|integer',
            'ttl_hours'=>'nullable|integer|min:1|max:720'
        ]);

        $model = match($data['type']) {
            'photo' => Photo::class,
            'album' => Album::class,
            'post'  => Post::class,
        };

        $item = $model::where('id',$data['id'])->where('user_id',$req->user()->id)->firstOrFail();

        $share = new Share();
        $share->user_id = $req->user()->id;
        $share->token = Str::random(40);
        $share->expires_at = isset($data['ttl_hours']) ? Carbon::now()->addHours($data['ttl_hours']) : null;

        $item->shares()->save($share);

        if ($item instanceof Album) {
            $link = url("/share/{$share->token}"); // Ссылка для фронтенда (предполагаем, что роутинг настроен)
            $item->description = $item->description ? $item->description . "\n" . $link : $link;
            $item->save();
        }

        return response()->json([
            'token' => $share->token,
            'url' => url("/api/share/{$share->token}"),
            'expires_at' => $share->expires_at
        ], 201);
    }

    public function open(Request $req, string $token) {
        $share = Share::where('token', $token)->first();
        if (!$share || !$share->isValid()) {
            return response()->json(['message' => 'Link expired or not found'], 404);
        }

        $item = $share->shareable;
        if (!$item) {
            return response()->json(['message' => 'Shared item not found'], 404);
        }

        $disk = Storage::disk('public'); // изменить диск при необходимости

        // PHOTO
        if ($item instanceof Photo) {
            // собрать поля фото и url
            $photo = $item;
            $data = [
                'id' => $photo->id,
                'user_id' => $photo->user_id,
                'path' => $photo->path,
                'original_name' => $photo->original_name ?? null,
                'title' => $photo->title ?? null,
                'description' => $photo->description ?? null,
                'visibility' => $photo->visibility ?? null,
                'created_at' => $photo->created_at,
                'updated_at' => $photo->updated_at,
                'url' => $disk->url($photo->path),
            ];

            return response()->json([
                'type' => 'photo',
                'data' => $data,
                'share' => [
                    'token' => $share->token,
                    'expires_at' => $share->expires_at,
                ],
            ], 200);
        }

        // ALBUM
        if ($item instanceof Album) {
            $item->loadMissing('photos');

            $photos = $item->photos->map(function($p) use ($disk) {
                return [
                    'id' => $p->id,
                    'original_name' => $p->original_name ?? null,
                    'title' => $p->title ?? null,
                    'description' => $p->description ?? null,
                    'path' => $p->path,
                    'url' => $disk->url($p->path),
                    'created_at' => $p->created_at,
                ];
            })->values();

            $albumData = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'name' => $item->name,
                'description' => $item->description ?? null,
                'visibility' => $item->visibility ?? null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'photos' => $photos,
            ];

            return response()->json([
                'type' => 'album',
                'data' => $albumData,
                'share' => [
                    'token' => $share->token,
                    'expires_at' => $share->expires_at,
                ],
            ], 200);
        }

        // POST
        if ($item instanceof Post) {
            $item->loadMissing('photos', 'author');

            $photos = $item->photos->map(function($p) use ($disk) {
                return [
                    'id' => $p->id,
                    'original_name' => $p->original_name ?? null,
                    'title' => $p->title ?? null,
                    'path' => $p->path,
                    'url' => $disk->url($p->path),
                ];
            })->values();

            $postData = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'title' => $item->title ?? null,
                'body' => $item->body ?? null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'photos' => $photos,
                'author' => $item->author ? [
                    'id' => $item->author->id,
                    'name' => $item->author->name,
                ] : null,
            ];

            return response()->json([
                'type' => 'post',
                'data' => $postData,
                'share' => [
                    'token' => $share->token,
                    'expires_at' => $share->expires_at,
                ],
            ], 200);
        }

        // fallback
        return response()->json(['message' => 'Unsupported shared type'], 400);
    }

    public function revoke(Request $req) {
        $data = $req->validate(['token'=>'required|string']);
        $share = Share::where('token',$data['token'])->firstOrFail();
        if ($share->user_id !== $req->user()->id) return response()->json(['message'=>'Forbidden'],403);
        $share->delete();
        return response()->json(['message'=>'revoked']);
    }

    public function import(Request $req, string $token) {
        $share = Share::where('token', $token)->first();
        if (!$share || !$share->isValid()) {
            return response()->json(['message' => 'Link expired or not found'], 404);
        }

        $item = $share->shareable;

        if ($item instanceof Album) {
            // Если пользователь уже владелец
            if ($item->user_id === $req->user()->id) {
                return response()->json($item->load('photos'), 200);
            }
            
            // Добавляем пользователя в список участников альбома (если еще нет)
            $req->user()->sharedAlbums()->syncWithoutDetaching([$item->id]);

            return response()->json($item->load('photos'), 200);
        }

        return response()->json(['message' => 'Import not supported for this item type'], 400);
    }
}
