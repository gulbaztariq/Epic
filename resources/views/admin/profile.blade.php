@extends('layouts.admin')

@section('title', 'My profile')
@section('heading', 'My profile')
@section('crumb', 'Your name, email address and password')

@section('content')
    <form action="{{ route('admin.profile.update') }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-2">
            <div class="card">
                <div class="card-head"><h3>Account details</h3></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field col-12">
                            <label for="name">Full name</label>
                            <input class="control" id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="field col-12">
                            <label for="email">Email address</label>
                            <input class="control" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="field col-12">
                            <label for="designation">Designation</label>
                            <input class="control" id="designation" type="text" name="designation" value="{{ old('designation', $user->designation) }}">
                        </div>
                        <div class="field col-12">
                            <label for="avatar">Profile photo</label>
                            <div class="media-field">
                                @if ($user->avatar)
                                    <div class="media-preview">
                                        <img src="{{ uploaded_url($user->avatar) }}" alt="" data-preview-for="avatar">
                                        <span class="file-chip">{{ basename($user->avatar) }}</span>
                                    </div>
                                @else
                                    <div class="media-preview"><img src="" alt="" data-preview-for="avatar" style="display:none"></div>
                                @endif
                                <input type="file" id="avatar" name="avatar" accept="image/*" data-preview="avatar">
                                @if ($user->avatar)
                                    <label class="media-remove"><input type="checkbox" name="remove_avatar" value="1"> Remove photo</label>
                                @endif
                            </div>
                        </div>
                        <div class="field col-12">
                            <label>Role</label>
                            <input class="control" type="text" value="{{ $user->role_label }}" readonly>
                            <span class="hint">Roles are managed under Admin users.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>Change password</h3></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field col-12">
                            <label for="current_password">Current password</label>
                            <input class="control @error('current_password') is-invalid @enderror" id="current_password" type="password" name="current_password" autocomplete="current-password">
                            @error('current_password')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field col-12">
                            <label for="password">New password</label>
                            <input class="control @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password">
                            <span class="hint">Minimum 8 characters. Leave blank to keep your current password.</span>
                            @error('password')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field col-12">
                            <label for="password_confirmation">Confirm new password</label>
                            <input class="control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="card-foot">
                    <button class="btn btn-primary" type="submit">{!! icon('check') !!} Save changes</button>
                </div>
            </div>
        </div>
    </form>
@endsection
