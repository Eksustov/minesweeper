@section('title', 'Welcome')

<x-app-layout>
    <div class="flex flex-col items-center justify-center text-center space-y-10 py-16">

        <!-- Hero Section -->
        <div class="mx-auto max-w-3xl text-center bg-white rounded-2xl shadow-xl ring-1 ring-black/5 px-8 py-10 space-y-6">
    
            <h1 class="text-3xl sm:text-5xl font-extrabold text-indigo-600 tracking-tight">
                Minesweeper Live
            </h1>

            <p class="text-base sm:text-lg text-gray-600 leading-relaxed">
                Play Minesweeper with others in real-time 
                and clear the board together without making mistakes.
            </p>

            <!-- CTA Buttons -->
            <div class="pt-2 flex justify-center">
                <a href="/welcome"
                class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-indigo-600 text-white font-semibold shadow-md hover:bg-indigo-500 hover:shadow-lg transition">
                    Start Playing
                </a>
            </div>

        

            <h1 class="text-black">Here's a little guide to help you understand a bit about how minesweeper works</h1>
            <p class="text-black">When you click on a square on the board it looks in a 3x3 area and checks if there is
                a mine nearby, if its empty there are no mines nearby and it shows the nearby squares, if there is a 
                1, then that means there is one mine nearby.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-8">
                <!-- Example 1 -->
                <div class="text-center space-y-2">
                    <div class="grid grid-cols-3 gap-1 justify-center">
                        <div class="ms-cell">3</div>
                        <div class="ms-cell">2</div>
                        <div class="ms-cell">1</div>

                        <div class="ms-cell">1</div>
                        <div class="ms-cell highlight"></div>
                        <div class="ms-cell">1</div>

                        <div class="ms-cell">2</div>
                        <div class="ms-cell">1</div>
                        <div class="ms-cell">3</div>
                    </div>
                    <p class="text-xs text-black">Safe tile</p>
                </div>

                <!-- Example 2 -->
                <div class="text-center space-y-2">
                    <div class="grid grid-cols-3 gap-1 justify-center">
                        <div class="ms-cell">💣</div>
                        <div class="ms-cell">1</div>
                        <div class="ms-cell"></div>

                        <div class="ms-cell">1</div>
                        <div class="ms-cell highlight">1</div>
                        <div class="ms-cell"></div>

                        <div class="ms-cell">2</div>
                        <div class="ms-cell">1</div>
                        <div class="ms-cell">1</div>
                    </div>
                    <p class="text-xs text-black">1 mine nearby</p>
                </div>

                <!-- Example 3 -->
                <div class="text-center space-y-2">
                    <div class="bg-white/5 grid grid-cols-3 gap-1 justify-center">
                        <div class="ms-cell">💣</div>
                        <div class="ms-cell">3</div>
                        <div class="ms-cell">💣</div>

                        <div class="ms-cell">2</div>
                        <div class="ms-cell highlight">3</div>
                        <div class="ms-cell"></div>

                        <div class="ms-cell">💣</div>
                        <div class="ms-cell">1</div>
                        <div class="ms-cell"></div>
                    </div>
                    <p class="text-xs text-black">3 mines nearby</p>
                </div>

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
                <h3 class="text-white font-semibold mb-2">Solo or Multiplayer</h3>
                <p class="text-gray-400 text-sm">
                    Clear the board on your own or together with a team.
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
                    One mine will end the board.
                </p>
            </div>
        </div>

    </div>
</x-app-layout>