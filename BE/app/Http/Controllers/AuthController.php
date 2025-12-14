<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $req) {
        $data = $req->validate([
            'name' => 'required|string|max:255',
            'email'=> 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'is_photographer' => 'sometimes|boolean'
        ]);
        $user = User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=>Hash::make($data['password']),
            'is_photographer' => $req->boolean('is_photographer', false)
            
        ]);
        $token = $user->createToken('api')->plainTextToken;
        return response()->json(['token'=>$token, 'user'=>$user], 201);
    }

    public function destroy(Request $req)
    {
        $user = $req->user();

        // odstranit tokeny sanctum
        $user->tokens()->delete();

        //  cascade/soft deletes
        $user->delete();

        return response()->json(['message' => 'Account deleted'], 200);
    }

    public function update(Request $req){
        $user = $req->user();

        $data = $req->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => ['sometimes', 'confirmed', Password::min(8)],
            'is_photographer' => 'sometimes|boolean',
        ]);

        $user->fill($data);
        $user->save();

        return response()->json($user);
    }


    public function login(Request $req) {
        $data = $req->validate([
            'email'=>'required|email',
            'password'=>'required'
        ]);
        $user = User::where('email',$data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message'=>'Invalid credentials'], 401);
        }
        $token = $user->createToken('api')->plainTextToken;
        return response()->json(['token'=>$token, 'user'=>$user]);
    }

    public function me(Request $req) { return $req->user(); }

    public function logout(Request $req) {
        $req->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'ok']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image',
        ]);

        $user = $request->user();

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        return response()->json(['user' => $user, 'message' => 'Avatar updated successfully']);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            if (Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->forceFill(['avatar_path' => null])->save();
        }

        return response()->json(['user' => $user, 'message' => 'Avatar deleted successfully']);
    }
}
