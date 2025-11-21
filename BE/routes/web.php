<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', fn() => 'ok-'.app()->version());

Route::get('/hello', function () {
    return 'Hello!';
});

Route::get('/ping', fn() => response('pong', 200));


Route::get('/about', [PageController::class, 'about']);
Route::get('/_probe', fn() => response('OK '.app()->version(), 200));