<!-- Primary Navigation Menu -->
<nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            <!-- Left: Navigation Links -->
            <div class="flex space-x-8">
                <a href="{{ route('welcome') }}" 
                   class="text-gray-700 font-medium hover:text-indigo-600 transition">
                   Main Page
                </a>
                <a href="{{ route('about') }}" 
                   class="text-gray-700 font-medium hover:text-indigo-600 transition">
                   About Us
                </a>
                <a href="{{ route('contacts') }}" 
                   class="text-gray-700 font-medium hover:text-indigo-600 transition">
                   Contacts
                </a>
            </div>

            <!-- Right: Auth Links -->
            <div class="flex items-center space-x-4">
                @guest
                    <a href="{{ route('login') }}" 
                       class="px-4 py-2 rounded-lg bg-gray-500 text-white font-medium hover:bg-indigo-600 transition">
                       Login
                    </a>
                    <a href="{{ route('register') }}" 
                       class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition">
                       Register
                    </a>
                @endguest

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 transition">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
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
            </div>

            <!-- Hamburger Menu for Mobile -->
            <div class="-mr-2 flex sm:hidden">
                <button @click="open = !open" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- Responsive Menu -->
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ route('welcome') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Main Page</a>
            <a href="{{ route('about') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">About Us</a>
            <a href="{{ route('contacts') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Contacts</a>
        </div>
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">Log Out</button>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>
