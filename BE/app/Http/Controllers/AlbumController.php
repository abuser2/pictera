<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index(Request $req) {
        
        if ($req->boolean('public')) {
            return Album::with('coverPhoto')->where('visibility','public')->latest()->paginate(20);
        }
        return Album::with('coverPhoto')->where('user_id', optional($req->user())->id)->latest()->paginate(20);
    }


    public function store(Request $req) {
        $data = $req->validate([
            'name'=>'required|string|max:255',
            'description'=>'nullable|string|max:2000',
            'visibility'=>'nullable|in:public,private,unlisted',
            'cover_file' => 'nullable|image',
        ]);

        $coverPhotoId = null;
        if ($req->hasFile('cover_file')) {
            $path = $req->file('cover_file')->store('photos', 'public');
            $photo = Photo::create([
                'user_id' => $req->user()->id,
                'path' => $path,
                'original_name' => $req->file('cover_file')->getClientOriginalName(),
            ]);
            $coverPhotoId = $photo->id;
        }

        $album = Album::create([
            'user_id'=>$req->user()->id,
            'name'=>$data['name'],
            'description'=>$data['description'] ?? null,
            'visibility'=>$data['visibility'] ?? 'private',
            'cover_photo_id' => $coverPhotoId,
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
    
    public function setCover(Request $request, Album $album)
    {
        // Проверка прав
        if ($album->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            // Либо ID существующего фото...
            'photo_id' => 'sometimes|required|integer|exists:photos,id',
            // ...либо новый файл обложки
            'cover_file' => 'sometimes|required|image', // max 10MB
        ]);

        if ($request->has('photo_id')) {
            // Устанавливаем существующее фото как обложку
            $photo = Photo::where('id', $validated['photo_id'])
                          ->where('user_id', $request->user()->id)
                          ->firstOrFail();
            $album->cover_photo_id = $photo->id;
        } elseif ($request->hasFile('cover_file')) {
            // Загружаем новый файл, создаем запись Photo и устанавливаем как обложку
            $path = $request->file('cover_file')->store('photos', 'public');
            $newPhoto = Photo::create([
                'user_id' => $request->user()->id,
                'path' => $path,
                'original_name' => $request->file('cover_file')->getClientOriginalName(),
                // Можно добавить флаг, что это фото только для обложки
            ]);
            $album->cover_photo_id = $newPhoto->id;
        }

        $album->save();

        // Возвращаем обновленный альбом с информацией об обложке
        return response()->json($album->load('coverPhoto'));
    }

    public function reorderPhotos(Request $request, Album $album)
    {
        if ($album->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'required|integer|exists:photos,id',
        ]);

        $syncData = [];
        foreach ($validated['photo_ids'] as $index => $photoId) {
            // Готовим данные для pivot-таблицы
            $syncData[$photoId] = ['order_column' => $index];
        }

        $album->photos()->sync($syncData);

        return response()->json(['message' => 'Photos reordered successfully']);
    }
}
