@section('title', 'Register')

<x-guest-layout wide>
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
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Create your account</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">It only takes a minute.</p>

                {{-- Error summary --}}
                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
                        <strong class="font-medium">Heads up:</strong> Please fix the highlighted fields.
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="mt-6">
                    @csrf

                    {{-- Name --}}
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- Email --}}
                    <div class="mt-4">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
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
                                autocomplete="new-password" />
                            <button
                                type="button"
                                @click="show = !show"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-sm text-gray-500 hover:text-gray-700 focus:outline-none"
                                aria-label="Toggle password visibility">
                                <span x-show="!show">Show</span>
                                <span x-show="show">Hide</span>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Use at least 8 characters with a number & symbol.</p>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mt-4" x-data="{ show2: false }">
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <div class="relative mt-1">
                            <x-text-input
                                id="password_confirmation"
                                class="block w-full pr-12"
                                type="password"
                                x-bind:type="show2 ? 'text' : 'password'"
                                name="password_confirmation"
                                required
                                autocomplete="new-password" />
                            <button
                                type="button"
                                @click="show2 = !show2"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-sm text-gray-500 hover:text-gray-700 focus:outline-none"
                                aria-label="Toggle password visibility">
                                <span x-show="!show2">Show</span>
                                <span x-show="show2">Hide</span>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    {{-- Terms (optional) --}}
                    <div class="mt-4">
                        <label class="flex items-start gap-3">
                            <input type="checkbox" id="checkbox" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" required>
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                I agree to the <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Terms</a> and
                                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Privacy Policy</a>.
                            </span>
                        </label>
                    </div>

                    {{-- Submit --}}
                    <div class="mt-6">
                        <x-primary-button class="w-full justify-center">
                            {{ __('Register') }}
                        </x-primary-button>
                    </div>

                    {{-- Link to Login --}}
                    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-300">
                        Already have an account?
                        <a class="font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
                           href="{{ route('login') }}">
                            {{ __('Log in') }}
                        </a>
                    </p>
                </form>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-white/80">© {{ now()->year }} Minesweeper Live</p>
        </div>
    </div>
</x-guest-layout>
