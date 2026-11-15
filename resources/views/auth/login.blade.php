<x-layouts.guest title="Sign in">
    <div class="auth-card">
        <div class="auth-card__brand brand">
            <span class="brand__name">Atelier Noir</span>
            <span class="brand__tagline">Fragrance House</span>
        </div>

        <h1 class="auth-card__title font-display">Sign in</h1>
        <p class="auth-card__intro">Use your administrator account to open the dashboard.</p>

        <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
            @csrf

            <div class="field">
                <label class="field__label" for="email">Email address</label>
                <input
                    id="email"
                    class="field__input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    autofocus
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                >
                @error('email')
                    <p id="email-error" class="field__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="field__label" for="password">Password</label>
                {{-- The toggle is revealed by resources/js/ui/password-toggle.js; without JavaScript it stays hidden. --}}
                <div class="password-field">
                    <input
                        id="password"
                        class="field__input password-field__input"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    <button type="button" class="icon-button password-field__toggle" aria-label="Show password" aria-controls="password" hidden data-password-toggle>
                        <x-icon name="eye" data-password-toggle-show />
                        <x-icon name="eye-off" hidden data-password-toggle-hide />
                    </button>
                </div>
                @error('password')
                    <p id="password-error" class="field__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="button button--primary auth-form__submit">Sign in</button>
        </form>
    </div>
</x-layouts.guest>
