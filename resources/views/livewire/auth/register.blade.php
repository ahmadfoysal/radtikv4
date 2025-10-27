{{-- resources/views/auth/register.blade.php --}}
<x-layouts.auth.mary :title="__('Create account')">
    <x-slot:header>{{ __('Create an account') }}</x-slot:header>
    <x-slot:subheader>{{ __('Enter your details below to create your account') }}</x-slot:subheader>

    {{-- Session status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    {{-- Register form (Fortify handles POST /register) --}}
    <form method="POST" action="{{ route('register') }}" class="grid gap-4" novalidate>
        @csrf

        {{-- Name --}}
        <x-mary-input name="name" label="{{ __('Name') }}" type="text" autocomplete="name" autofocus required
            placeholder="{{ __('Full name') }}" :value="old('name')" />
        @error('name')
            <p class="text-error text-sm mt-1">{{ $message }}</p>
        @enderror

        {{-- Email --}}
        <x-mary-input name="email" label="{{ __('Email address') }}" type="email" placeholder="email@example.com"
            autocomplete="email" required :value="old('email')" />
        @error('email')
            <p class="text-error text-sm mt-1">{{ $message }}</p>
        @enderror

        {{-- Password --}}
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Password') }}</span>
            </label>
            <div x-data="{ show: false }" class="relative">
                <input :type="show ? 'text' : 'password'" name="password" class="input input-bordered w-full pr-10"
                    placeholder="{{ __('Password') }}" autocomplete="new-password" required />
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 opacity-70 hover:opacity-100 transition">
                    <x-mary-icon name="o-eye" class="w-5 h-5" x-show="!show" />
                    <x-mary-icon name="o-eye-slash" class="w-5 h-5" x-show="show" />
                </button>
            </div>
            @error('password')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="form-control">
            <label class="label">
                <span class="label-text">{{ __('Confirm password') }}</span>
            </label>
            <div x-data="{ show: false }" class="relative">
                <input :type="show ? 'text' : 'password'" name="password_confirmation"
                    class="input input-bordered w-full pr-10" placeholder="{{ __('Confirm password') }}"
                    autocomplete="new-password" required />
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 opacity-70 hover:opacity-100 transition">
                    <x-mary-icon name="o-eye" class="w-5 h-5" x-show="!show" />
                    <x-mary-icon name="o-eye-slash" class="w-5 h-5" x-show="show" />
                </button>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-primary w-full">
            {{ __('Create account') }}
        </button>

        {{-- Optional global errors list --}}
        <x-mary-errors />
    </form>

    <x-slot:secondary>
        <div class="text-sm text-center opacity-80">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" class="link link-hover ml-1" wire:navigate>
                {{ __('Log in') }}
            </a>
        </div>
    </x-slot:secondary>
</x-layouts.auth.mary>
