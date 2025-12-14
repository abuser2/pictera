<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LikeController;
use Illuminate\Http\Request;
// Авторизация

Route::get('/probe', fn () => response()->json([
    'ok' => true,
    'ver' => app()->version(),
]));


Route::post('/auth/register',[AuthController::class,'register']);
Route::post('/auth/login',[AuthController::class,'login']);


Route::middleware('auth:sanctum')->group(function() {
    // get token info, logout, update profile, delete account
    Route::get('/auth/me',[AuthController::class,'me']);
    Route::post('/auth/logout',[AuthController::class,'logout']);
    Route::patch('/auth',[AuthController::class,'update']);
    Route::delete('/auth',[AuthController::class,'destroy']);
});

// Публичные выборки
Route::get('/public/photos',[PhotoController::class,'index']);   // ?public=1
Route::get('/public/albums',[AlbumController::class,'index']);   // ?public=1
Route::get('/public/posts',[PostController::class,'index']);     // ?public=1

// Приватные ссылки
Route::get('/share/{token}', [ShareController::class,'open']);

// public user profiles
Route::get('/users/{id}', [UserController::class, 'show']);


// Авторизованные
Route::middleware('auth:sanctum')->group(function () {
    // управление фото
    Route::get('/photos/{photo}/download', [PhotoController::class, 'download']);
    Route::get('/photos',[PhotoController::class,'index']);
    Route::post('/photos',[PhotoController::class,'store']);
    Route::get('/photos/{photo}',[PhotoController::class,'show']);
    Route::patch('/photos/{photo}',[PhotoController::class,'update']);
    Route::delete('/photos/{photo}',[PhotoController::class,'destroy']);
    // бронирование фото
    Route::post('/bookings', [BookingController::class, 'store']);     // создать бронь
    Route::get('/bookings', [BookingController::class, 'index']);      // получить список броней
    
    // управление альбомами
    // Внутри группы с middleware 'auth:sanctum'
    Route::post('/albums/{album}/cover', [AlbumController::class, 'setCover']);

    Route::get('/albums',[AlbumController::class,'index']);
    Route::post('/albums',[AlbumController::class,'store']);
    Route::get('/albums/{album}',[AlbumController::class,'show']);
    Route::patch('/albums/{album}',[AlbumController::class,'update']);
    Route::delete('/albums/{album}',[AlbumController::class,'destroy']);
    Route::post('/albums/{album}/add-photo',[AlbumController::class,'addPhoto']);
    Route::post('/albums/{album}/add-photos',[AlbumController::class,'addPhotos']);
    Route::delete('/albums/{album}/remove-photo/{photo}',[AlbumController::class,'removePhoto']);
    Route::post('/albums/{album}/reorder-photos', [AlbumController::class, 'reorderPhotos']);
    
    // управление пользователем
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [AuthController::class, 'deleteAvatar']);

    Route::get('/posts',[PostController::class,'index']);
    Route::post('/posts',[PostController::class,'store']);
    Route::get('/posts/{post}',[PostController::class,'show']);
    Route::patch('/posts/{post}',[PostController::class,'update']);
    Route::delete('/posts/{post}',[PostController::class,'destroy']);
    Route::delete('/posts/{post}/photos/{photo}', [PostController::class, 'removePhoto']);

    Route::post('/shares',[ShareController::class,'create']);
    Route::delete('/shares',[ShareController::class,'revoke']);
    Route::post('/share/{token}/import', [ShareController::class, 'import']);

    // Подписки
    Route::post('/users/{id}/follow', [UserController::class, 'follow']);
    Route::delete('/users/{id}/follow', [UserController::class, 'unfollow']);

    // Лайки
    Route::post('/likes/toggle', [LikeController::class, 'toggle']);
});
