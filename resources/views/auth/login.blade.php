@section('title', 'Login')

<x-guest-layout>
    {{-- Animated background + glass styles --}}
    <style>
        @keyframes floaty {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .auth-bg {
            background: linear-gradient(135deg, #4f46e5, #8b5cf6, #ec4899, #06b6d4);
            background-size: 300% 300%;
            animation: floaty 16s ease infinite;
        }
        .glass {
            backdrop-filter: blur(10px);
            background-color: rgb(255 255 255 / 0.75);
        }
        .dark .glass {
            background-color: rgb(17 24 39 / 0.6);
        }
    </style>

    <div class="min-h-screen auth-bg flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            {{-- Brand --}}
            <div class="text-center mb-6">
                <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2">
                    <x-application-logo class="size-9" />
                    <span class="text-xl font-semibold text-white drop-shadow">Minesweeper Live</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="glass rounded-2xl shadow-2xl p-6 sm:p-7 ring-1 ring-black/5 dark:ring-white/10">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Welcome back</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Log in to continue your game.</p>

                {{-- Session Status --}}
                <x-auth-session-status class="mt-4" :status="session('status')" />

                {{-- Error summary --}}
                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
                        <strong class="font-medium">Heads up:</strong> Please fix the highlighted fields.
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <div class="relative mt-1">
                            <x-text-input id="email" class="peer block w-full pr-10" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 hidden peer-focus:block size-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/></svg>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Password --}}
                    <div class="mt-4" x-data="{ show: false }">
                        <x-input-label for="password" :value="__('Password')" />
                        <div class="relative mt-1">
                            <x-text-input
                                id="password"
                                class="block w-full pr-12"
                                type="password"
                                x-bind:type="show ? 'text' : 'password'"
                                name="password"
                                required
                                autocomplete="current-password" />
                            <button
                                type="button"
                                @click="show = !show"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-sm text-gray-500 hover:text-gray-700 focus:outline-none"
                                aria-label="Toggle password visibility">
                                <span x-show="!show">Show</span>
                                <span x-show="show">Hide</span>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Remember + forgot --}}
                    <div class="mt-4 flex items-center justify-between">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">{{ __('Remember me') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                               href="{{ route('password.request') }}">
                                {{ __('Forgot password?') }}
                            </a>
                        @endif
                    </div>

                    {{-- Submit --}}
                    <div class="mt-6">
                        <x-primary-button class="w-full justify-center">
                            {{ __('Log in') }}
                        </x-primary-button>
                    </div>

                    {{-- Divider --}}
                    <div class="my-6 flex items-center gap-4 text-xs text-gray-500">
                        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        <span>or</span>
                        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                    </div>

                    {{-- Link to Register --}}
                    <p class="text-center text-sm text-gray-600 dark:text-gray-300">
                        New here?
                        <a class="font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                           href="{{ route('register') }}">
                            Create an account
                        </a>
                    </p>
                </form>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-white/80">© {{ now()->year }} Minesweeper Live</p>
        </div>
    </div>
</x-guest-layout>
