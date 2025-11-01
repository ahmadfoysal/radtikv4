<x-layouts.auth.mary :title="__('Log in')">
    <x-slot:header>{{ __('Log in to your account') }}</x-slot:header>
    <x-slot:subheader>{{ __('Enter your email and password below to log in') }}</x-slot:subheader>

    {{-- Session status (Fortify flash message) --}}
    {{-- <x-auth-session-status class="text-center" :status="session('status')" /> --}}

    {{-- Login form (Fortify handles POST /login) --}}
    <form method="POST" action="{{ route('login') }}" class="grid gap-4" novalidate>
        @csrf

        {{-- Email --}}
        <x-mary-input name="email" label="{{ __('Email address') }}" type="email" placeholder="email@example.com"
            autocomplete="email" autofocus required :value="old('email')" />
        @error('email')
            <p class="text-error text-sm mt-1">{{ $message }}</p>
        @enderror
        {{-- Password --}}
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Password') }}</span>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="link link-hover text-sm" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </label>

            {{-- Password input with MaryUI icons for visibility toggle --}}
            <div x-data="{ show: false }" class="relative">
                <input :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    placeholder="{{ __('Password') }}" class="input input-bordered w-full pr-10" />
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 opacity-70 hover:opacity-100 transition">
                    <x-mary-icon name="o-eye" class="w-5 h-5" x-show="!show" />
                    <x-mary-icon name="o-eye-slash" class="w-5 h-5" x-show="show" />
                </button>
            </div>
        </div>
        @error('password')
            <p class="text-error text-sm mt-1">{{ $message }}</p>
        @enderror
        {{-- Remember me --}}
        <label class="label cursor-pointer gap-2">
            <input type="checkbox" name="remember" class="checkbox checkbox-sm"
                {{ old('remember') ? 'checked' : '' }} />
            <span class="label-text">{{ __('Remember me') }}</span>
        </label>

        {{-- Submit --}}
        <button type="submit" class="btn btn-primary w-full" data-test="login-button">
            {{ __('Log in') }}
        </button>


    </form>

    {{-- Secondary footer --}}
    <x-slot:secondary>
        @if (Route::has('register'))
            <div class="text-sm text-center opacity-80">
                <span>{{ __("Don't have an account?") }}</span>
                <a href="{{ route('register') }}" class="link link-hover ml-1" wire:navigate>
                    {{ __('Sign up') }}
                </a>
            </div>
        @endif
    </x-slot:secondary>
</x-layouts.auth.mary>
