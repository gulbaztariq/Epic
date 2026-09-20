<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile', ['user' => Auth::user()]);
    }

    public function update(Request $request, MediaService $media)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'designation' => ['nullable', 'string', 'max:190'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (filled($data['password'] ?? null)) {
            if (! Hash::check((string) $request->input('current_password'), $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Your current password is incorrect.',
                ]);
            }

            $user->password = $data['password'];
        }

        if ($request->hasFile('avatar')) {
            $media->delete($user->avatar);
            $user->avatar = $media->store($request->file('avatar'), 'avatars');
        } elseif ($request->boolean('remove_avatar')) {
            $media->delete($user->avatar);
            $user->avatar = null;
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->designation = $data['designation'] ?? null;
        $user->save();

        return back()->with('success', 'Your profile has been updated.');
    }
}
