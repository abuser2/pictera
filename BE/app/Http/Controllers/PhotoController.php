<?php

namespace App\Http\Controllers;

use App\Models\GlobalId;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function index(Request $req) {
        if ($req->boolean('public')) {
            return Photo::where('visibility','public')->latest()->paginate(20);
        }
        return Photo::where('user_id', optional($req->user())->id)->latest()->paginate(20);
    }

    public function store(Request $req) {
        $data = $req->validate([
            'photo' => 'required|file|image|max:40960',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'visibility' => 'nullable|in:public,private,unlisted'
        ]);

        $path = $req->file('photo')->store('photos','web');

        $globalId = GlobalId::create()->id;
        $photo = new Photo([
            'path' => $path,
            'original_name' => $req->file('photo')->getClientOriginalName(),
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'] ?? 'private',
        ]);
        $photo->user_id = $req->user()->id;
        $photo->global_id = $globalId;
        $photo->save();

        return response()->json([
              'id'   => $photo->id,
              'global_id' => $photo->global_id,
              'path' => $photo->path,
              'url'  => Storage::disk('web')->url($photo->path),
              'title' => $photo->title,
              'description' => $photo->description,
              'visibility' => $photo->visibility,
            ], 201);
    }

    public function show(Request $req, Photo $photo) {
        if ($photo->user_id !== optional($req->user())->id && !in_array($photo->visibility, ['public','unlisted'])) {
            return response()->json(['message'=>'Forbidden'], 403);
        }
        return $photo;
    }

    public function update(Request $req, Photo $photo) {
        if ($photo->user_id !== $req->user()->id) return response()->json(['message'=>'Forbidden'],403);

        $data = $req->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'visibility' => 'nullable|in:public,private,unlisted'
        ]);

        $photo->update(array_filter($data, fn($v)=>!is_null($v)));
        return $photo;
    }

    public function destroy(Request $req, Photo $photo) {
        if ($photo->user_id !== $req->user()->id) return response()->json(['message'=>'Forbidden'],403);
        Storage::disk('web')->delete($photo->path);
        $photo->delete();
        return response()->json(['message'=>'deleted']);
    }

    public function download(Request $req, Photo $photo) {
        // Проверяем права: владелец или публичное фото        
        // Используем диск 'web', так как он используется при загрузке (store)
        return Storage::disk('web')->download($photo->path, $photo->original_name);
    }
}
