<!-- Primary Navigation Menu -->
<nav x-data="{ open: false }"
     class="relative z-40 bg-gradient-to-r from-indigo-700 via-indigo-600 to-fuchsia-600 text-white shadow-xl">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <!-- Left: Brand + Links -->
            <div class="flex items-center gap-8">
                <!-- Brand -->
                <a href="{{ route('welcome') }}" class="flex items-center gap-2 group">
                <x-application-logo />
                    <span class="text-lg font-semibold tracking-tight">
                        Minesweeper Live
                    </span>
                </a>

                <!-- Desktop links -->
                <div class="hidden sm:flex items-center gap-6">
                    <a href="{{ route('welcome') }}"
                       class="relative text-sm font-medium transition
                              {{ request()->routeIs('welcome') ? 'text-white' : 'text-white/80 hover:text-white/90' }}">
                        Main Page
                        <span class="{{ request()->routeIs('welcome') ? 'absolute -bottom-2 left-0 h-0.5 w-full bg-white/90 rounded-full' : 'hidden' }}"></span>
                    </a>

                    <a href="{{ route('about') }}"
                       class="relative text-sm font-medium transition
                              {{ request()->routeIs('about') ? 'text-white' : 'text-white/80 hover:text-white/90' }}">
                        About Us
                        <span class="{{ request()->routeIs('about') ? 'absolute -bottom-2 left-0 h-0.5 w-full bg-white/90 rounded-full' : 'hidden' }}"></span>
                    </a>

                    <a href="{{ route('contacts') }}"
                       class="relative text-sm font-medium transition
                              {{ request()->routeIs('contacts') ? 'text-white' : 'text-white/80 hover:text-white/90' }}">
                        Contacts
                        <span class="{{ request()->routeIs('contacts') ? 'absolute -bottom-2 left-0 h-0.5 w-full bg-white/90 rounded-full' : 'hidden' }}"></span>
                    </a>
                </div>
            </div>

            <!-- Right: Auth / Actions -->
            <div class="flex items-center gap-3">
                @guest
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 transition">
                        Login
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white text-indigo-700 px-4 py-2 text-sm font-semibold hover:bg-indigo-50 transition">
                        Register
                    </a>
                @endguest

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm font-medium text-white ring-1 ring-white/20 hover:bg-white/15 transition">
                                <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                                <span class="sm:hidden">Menu</span>
                                <svg class="ml-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 011.08 1.04l-4.25 4.25a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2 text-sm text-gray-700">
                                Signed in as <span class="font-semibold">{{ Auth::user()->name }}</span>
                            </div>
                            <x-dropdown-link :href="route('profile.edit')">
                                Profile
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Log Out
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth

                <!-- Mobile hamburger -->
                <button @click="open = !open"
                        class="sm:hidden inline-flex items-center justify-center rounded-lg p-2 text-white/90 hover:bg-white/10 ring-1 ring-white/10 transition"
                        aria-label="Open menu">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                        <path x-show="!open" d="M4 6h16v2H4zM4 11h16v2H4zM4 16h16v2H4z"/>
                        <path x-show="open"  d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom glass edge -->
    <div class="h-px w-full bg-white/20"></div>

    <!-- Mobile drawer -->
    <div x-show="open" x-transition
         class="sm:hidden border-t border-white/10 bg-white/10 backdrop-blur">
        <div class="px-4 py-3 space-y-1">
            <a href="{{ route('welcome') }}"
               class="block rounded-lg px-3 py-2 text-sm font-medium
                      {{ request()->routeIs('welcome') ? 'bg-white/10 text-white' : 'text-white/90 hover:bg-white/10' }}">
                Main Page
            </a>
            <a href="{{ route('about') }}"
               class="block rounded-lg px-3 py-2 text-sm font-medium
                      {{ request()->routeIs('about') ? 'bg-white/10 text-white' : 'text-white/90 hover:bg-white/10' }}">
                About Us
            </a>
            <a href="{{ route('contacts') }}"
               class="block rounded-lg px-3 py-2 text-sm font-medium
                      {{ request()->routeIs('contacts') ? 'bg-white/10 text-white' : 'text-white/90 hover:bg-white/10' }}">
                Contacts
            </a>
        </div>

        @auth
            <div class="mt-2 border-t border-white/10 px-4 py-3">
                <div class="text-white/90 text-sm">
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                    <div class="text-white/70">{{ Auth::user()->email }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <a href="{{ route('profile.edit') }}"
                       class="block rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-white/10">
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left rounded-lg px-3 py-2 text-sm font-medium text-white/90 hover:bg-white/10">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
