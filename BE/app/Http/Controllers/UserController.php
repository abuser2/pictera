<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function index()
    {
        $users = User::all(); // or with pagination
        return response()->json($users);
    }

    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Возвращаем данные пользователя + списки ID подписчиков и подписок
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_photographer' => $user->is_photographer,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at,
            'followers_ids' => $user->followers()->pluck('follower_id'),
            'following_ids' => $user->following()->pluck('following_id'),
            'tag' => $user->tag,
            'price' => $user->price
        ]);
    }

    public function follow(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $me = $request->user();

        if ($me->id === $targetUser->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }

        // syncWithoutDetaching предотвращает дублирование записей
        $me->following()->syncWithoutDetaching([$targetUser->id]);

        return response()->json(['message' => 'Followed successfully', 'following_ids' => $me->following()->pluck('following_id')]);
    }

    public function unfollow(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $me = $request->user();

        $me->following()->detach($targetUser->id);

        return response()->json(['message' => 'Unfollowed successfully', 'following_ids' => $me->following()->pluck('following_id')]);
    }
    public function following($id)
    {
        $user = User::findOrFail($id);

        // Users that $user follows
        $following = $user->following()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        return response()->json([
            'data' => $following
        ]);
    }
}
