<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index(Request $req) {
        if ($req->boolean('public')) {
            return Album::where('visibility','public')->latest()->paginate(20);
        }
        return Album::where('user_id', optional($req->user())->id)->latest()->paginate(20);
    }

    public function store(Request $req) {
        $data = $req->validate([
            'name'=>'required|string|max:255',
            'description'=>'nullable|string|max:2000',
            'visibility'=>'nullable|in:public,private,unlisted'
        ]);
        $album = Album::create([
            'user_id'=>$req->user()->id,
            'name'=>$data['name'],
            'description'=>$data['description'] ?? null,
            'visibility'=>$data['visibility'] ?? 'private'
        ]);
        return response()->json($album, 201);
    }

    public function show(Request $req, Album $album) {
        if ($album->user_id !== optional($req->user())->id && !in_array($album->visibility,['public','unlisted'])) {
            return response()->json(['message'=>'Forbidden'],403);
        }
        return $album->load('photos');
    }

    public function update(Request $req, Album $album) {
        if ($album->user_id !== $req->user()->id) return response()->json(['message'=>'Forbidden'],403);
        $data = $req->validate([
            'name'=>'nullable|string|max:255',
            'description'=>'nullable|string|max:2000',
            'visibility'=>'nullable|in:public,private,unlisted'
        ]);
        $album->update(array_filter($data, fn($v)=>!is_null($v)));
        return $album;
    }

    public function destroy(Request $req, Album $album) {
        if ($album->user_id !== $req->user()->id) return response()->json(['message'=>'Forbidden'],403);
        $album->photos()->detach();
        $album->delete();
        return response()->json(['message'=>'deleted']);
    }

    public function addPhoto(Request $req, Album $album) {
        if ($album->user_id !== $req->user()->id) return response()->json(['message'=>'Forbidden'],403);
        $data = $req->validate(['photo_id'=>'required|integer|exists:photos,id']);
        $photo = Photo::where('id',$data['photo_id'])->where('user_id',$req->user()->id)->firstOrFail();
        $album->photos()->syncWithoutDetaching([$photo->id]);
        return $album->load('photos');
    }

    public function removePhoto(Request $req, Album $album, Photo $photo) {
        if ($album->user_id !== $req->user()->id || $photo->user_id !== $req->user()->id) return response()->json(['message'=>'Forbidden'],403);
        $album->photos()->detach($photo->id);
        return $album->load('photos');
    }
}
