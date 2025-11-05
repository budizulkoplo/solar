<section>
    <header>
        <h2 class="h5 text-dark">
            {{ __('Update Password') }}
        </h2>
        <p class="mt-1 text-muted small">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        {{-- Current Password --}}
        <div class="mb-3">
            <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
            <div class="position-relative">
                <input type="password"
                    class="form-control pe-5 @error('current_password', 'updatePassword') is-invalid @enderror"
                    id="update_password_current_password"
                    name="current_password"
                    autocomplete="current-password">
                <button type="button"
                    class="btn btn-sm btn-link position-absolute end-0 top-50 translate-middle-y text-secondary toggle-password"
                    data-target="update_password_current_password">
                    <i class="bi bi-eye"></i>
                </button>
                @error('current_password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- New Password --}}
        <div class="mb-3">
            <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
            <div class="position-relative">
                <input type="password"
                    class="form-control pe-5 @error('password', 'updatePassword') is-invalid @enderror"
                    id="update_password_password"
                    name="password"
                    autocomplete="new-password">
                <button type="button"
                    class="btn btn-sm btn-link position-absolute end-0 top-50 translate-middle-y text-secondary toggle-password"
                    data-target="update_password_password">
                    <i class="bi bi-eye"></i>
                </button>
                @error('password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Confirm Password --}}
        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <div class="position-relative">
                <input type="password"
                    class="form-control pe-5 @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password">
                <button type="button"
                    class="btn btn-sm btn-link position-absolute end-0 top-50 translate-middle-y text-secondary toggle-password"
                    data-target="update_password_password_confirmation">
                    <i class="bi bi-eye"></i>
                </button>
                @error('password_confirmation', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

            @if (session('status') === 'password-updated')
                <p 
                    x-data="{ show: true }" 
                    x-show="show" 
                    x-transition 
                    x-init="setTimeout(() => show = false, 2000)" 
                    class="text-muted small mb-0">
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>
</section>

