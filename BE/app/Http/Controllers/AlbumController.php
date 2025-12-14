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
        
        $user = $req->user();
        $sharedIds = $user->sharedAlbums()->pluck('albums.id');

        return Album::with('coverPhoto')
            ->where(function($q) use ($user, $sharedIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('id', $sharedIds);
            })
            ->latest()
            ->paginate(20);
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
        $user = $req->user();
        $canAccess = ($user && ($album->user_id === $user->id || $user->sharedAlbums()->where('albums.id', $album->id)->exists()));

        if (!$canAccess && !in_array($album->visibility,['public','unlisted'])) {
            return response()->json(['message'=>'Forbidden'],403);
        }
        $album->load('photos');
        // Сортируем фото по description как по числу
        $album->setRelation('photos', $album->photos->sortBy(fn($p) => (int)$p->description)->values());
        return $album;
    }

    public function update(Request $req, Album $album) {
        if (!$this->canEdit($req->user(), $album)) return response()->json(['message'=>'Forbidden'],403);
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
        if (!$this->canEdit($req->user(), $album)) return response()->json(['message'=>'Forbidden'],403);
        $data = $req->validate(['photo_id'=>'required|integer|exists:photos,id']);
        $photo = Photo::where('id',$data['photo_id'])->where('user_id',$req->user()->id)->firstOrFail();
        $album->photos()->syncWithoutDetaching([$photo->id]);
        return $album->load('photos');
    }
    public function addPhotos(Request $req, Album $album) {
        if (!$this->canEdit($req->user(), $album)) return response()->json(['message'=>'Forbidden'],403);
        $data = $req->validate(['photo_ids'=>'required|array', 'photo_ids.*'=>'integer|exists:photos,id']);
        $photos = Photo::whereIn('id',$data['photo_ids'])->where('user_id',$req->user()->id)->pluck('id');
        $album->photos()->syncWithoutDetaching($photos);
        return $album->load('photos');
    }

    public function removePhoto(Request $req, Album $album, Photo $photo) {
        if (!$this->canEdit($req->user(), $album)) return response()->json(['message'=>'Forbidden'],403);
        
        $removedOrder = intval($photo->description);
        
        $album->photos()->detach($photo->id);
        
        $remainingPhotos = $album->photos()->get();
        foreach ($remainingPhotos as $p) {
            $val = intval($p->description);
            if ($val > $removedOrder) {
                $p->forceFill(['description' => (string)($val - 1)])->save();
            }
        }
        
        return $album->load('photos');
    }
    
    public function setCover(Request $request, Album $album)
    {
        // Проверка прав
        if (!$this->canEdit($request->user(), $album)) {
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
        if (!$this->canEdit($request->user(), $album)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'required|integer|exists:photos,id',
        ]);

        foreach ($validated['photo_ids'] as $index => $photoId) {
           // Обновляем description, используя индекс + 1
           // Разрешаем менять description у фото, если оно находится в этом альбоме
            $album->photos()->where('photos.id', $photoId)
                ->update(['description' => (string)($index + 1)]);
        }

        return response()->json(['message' => 'Photos reordered successfully']);
    }

    private function canEdit($user, $album) {
        return $album->user_id === $user->id || $user->sharedAlbums()->where('albums.id', $album->id)->exists();
    }
}
