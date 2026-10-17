@extends('layouts.app')

@section('title', 'Profile Settings - ' . config('app.name'))

@section('content')
<div class="profile-wrap">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-account-cog"></i>
            </span> Profile Settings
        </h3>
       
    </div>

    <div class="card profile-card mb-4">
        <div class="card-body">
            <h4 class="card-title">Personal Information</h4>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="d-flex align-items-center gap-3 profile-photo-row">
                    <div class="avatar-wrap">
                        <div class="profile-avatar" id="profile-avatar">
                            @if ($user->profile_photo_url)
                                <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                            @else
                                <span>{{ $user->initials }}</span>
                            @endif
                        </div>
                        @if ($user->profile_photo_url)
                            <button type="button" class="avatar-edit" id="avatar-edit" title="Photo options" aria-haspopup="true" aria-expanded="false">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                            <div class="avatar-menu d-none" id="avatar-menu">
                                <label for="photo" class="avatar-menu-item mb-0">
                                    <i class="mdi mdi-image-outline"></i> Select image
                                </label>
                                <button type="submit" name="remove_photo" value="1" class="avatar-menu-item avatar-menu-danger">
                                    <i class="mdi mdi-delete-outline"></i> Remove image
                                </button>
                            </div>
                        @else
                            <label for="photo" class="avatar-edit" title="Upload photo">
                                <i class="mdi mdi-pencil"></i>
                            </label>
                        @endif
                    </div>
                    <div>
                        <div class="profile-photo-title">Profile Photo</div>
                        <small class="text-muted">Accepts JPG, PNG . Max 2MB.</small>
                        <input type="file" id="photo" name="photo" accept="image/*" class="d-none">
                        @error('photo')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            class="form-control @error('name') is-invalid @enderror"
                            id="name"
                            autocomplete="name"
                        >
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            autocomplete="email"
                        >
                        @error('email')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="d-flex justify-content-end align-items-center gap-2">
                    @if (session('status'))
                        <span class="save-status">{{ session('status') }}</span>
                    @endif
                    <button type="submit" class="btn btn-gradient-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card profile-card">
        <div class="card-body">
            <h4 class="card-title">Change Password</h4>
            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <div class="password-field">
                        <input
                            type="password"
                            name="current_password"
                            value="{{ old('current_password') }}"
                            class="form-control @error('current_password') is-invalid @enderror"
                            id="current_password"
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" data-target="current_password" aria-label="Show password">
                            <i class="mdi mdi-eye-outline"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="password-field">
                        <input
                            type="password"
                            name="password"
                            value="{{ old('password') }}"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                            <i class="mdi mdi-eye-outline"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <small id="password-hint" class="field-error d-none">At least 8 characters with upper and lower case letters and a number.</small>
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <div class="password-field">
                        <input
                            type="password"
                            name="password_confirmation"
                            value="{{ old('password_confirmation') }}"
                            class="form-control"
                            id="password_confirmation"
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" data-target="password_confirmation" aria-label="Show password">
                            <i class="mdi mdi-eye-outline"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-end align-items-center gap-2">
                    @if (session('password_status'))
                        <span class="save-status">{{ session('password_status') }}</span>
                    @endif
                    <button type="submit" class="btn btn-gradient-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var photoInput = document.getElementById('photo');
    var avatar = document.getElementById('profile-avatar');
    var avatarEdit = document.getElementById('avatar-edit');
    var avatarMenu = document.getElementById('avatar-menu');
    var newPasswordInput = document.getElementById('password');
    var passwordHint = document.getElementById('password-hint');

    if (newPasswordInput && passwordHint) {
        newPasswordInput.addEventListener('input', function () {
            var show = newPasswordInput.value.length > 0
                && !newPasswordInput.classList.contains('is-invalid');
            passwordHint.classList.toggle('d-none', !show);
        });
    }

    function closeAvatarMenu() {
        if (avatarMenu && !avatarMenu.classList.contains('d-none')) {
            avatarMenu.classList.add('d-none');
        }
        if (avatarEdit) {
            avatarEdit.setAttribute('aria-expanded', 'false');
        }
    }

    if (avatarEdit && avatarMenu) {
        avatarEdit.addEventListener('click', function (event) {
            event.stopPropagation();
            var hidden = avatarMenu.classList.toggle('d-none');
            avatarEdit.setAttribute('aria-expanded', String(!hidden));
        });

        document.addEventListener('click', function (event) {
            if (!avatarMenu.classList.contains('d-none') && !avatarMenu.contains(event.target)) {
                closeAvatarMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAvatarMenu();
            }
        });
    }

    if (photoInput && avatar) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files[0];
            closeAvatarMenu();
            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                var img = avatar.querySelector('img');
                if (!img) {
                    avatar.innerHTML = '';
                    img = document.createElement('img');
                    img.alt = 'Profile photo preview';
                    avatar.appendChild(img);
                }
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-target'));
            if (!input) {
                return;
            }

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';

            var icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('mdi-eye-outline', !show);
                icon.classList.toggle('mdi-eye-off-outline', show);
            }

            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });
})();
</script>
@endpush
