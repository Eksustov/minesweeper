@section('title', 'Welcome')

<x-app-layout>
    <div class="flex flex-col items-center justify-center text-center space-y-10 py-16">

        <!-- Hero Section -->
        <div class="space-y-7 max-w-4xl bg-white text-indigo-600 rounded-2xl">
            <h1 class="text-2xl sm:text-5xl font-extrabold leading-tight">
                Minesweeper Live
            </h1>

            <p class="text-lg text-gray-300">
                Play Minesweeper like never before — collaborate with others in real-time 
                and clear the board together without making mistakes.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/"
                   class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-semibold shadow-lg hover:bg-indigo-500 transition">
                    Start Playing
                </a>
            </div>
        </div>

        <!-- Preview / Image -->
        <div class="w-full max-w-3xl">
            <div class="bg-white/5 backdrop-blur rounded-2xl shadow-2xl p-4 border border-white/10">
                <img src="/minesweeper-1.gif" 
                     class="rounded-xl w-full object-cover"
                     alt="Minesweeper gameplay preview">
            </div>
        </div>

        <!-- Features Section -->
        <div class="grid sm:grid-cols-3 gap-6 max-w-4xl w-full text-left">
            <div class="bg-white/5 p-5 rounded-xl border border-white/10">
                <h3 class="text-white font-semibold mb-2">Multiplayer</h3>
                <p class="text-gray-400 text-sm">
                    Play together with friends and solve the board as a team.
                </p>
            </div>

            <div class="bg-white/5 p-5 rounded-xl border border-white/10">
                <h3 class="text-white font-semibold mb-2">Real-time</h3>
                <p class="text-gray-400 text-sm">
                    See moves instantly and coordinate your strategy live.
                </p>
            </div>

            <div class="bg-white/5 p-5 rounded-xl border border-white/10">
                <h3 class="text-white font-semibold mb-2">Challenging</h3>
                <p class="text-gray-400 text-sm">
                    One mistake can cost the game — stay sharp.
                </p>
            </div>
        </div>

    </div>
</x-app-layout>