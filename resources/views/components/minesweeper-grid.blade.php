<div>
    <h1 class="text-2xl font-bold mb-4">Minesweeper</h1>

    <!-- Status -->
    <div class="flex justify-between items-center mb-4">
        <div id="mineCounter" class="text-lg font-bold text-gray-700">Mines: 0</div>
        <div id="statusMessage" class="text-lg font-bold text-green-600"></div>
    </div>

    <!-- Grid -->
    <div class="flex justify-center">
        <div id="game" class="grid gap-1"></div>
    </div>

    <!-- Buttons -->
    <div class="mt-4 flex space-x-2">
        @if(auth()->id() === $room->creator->id)
            <button id="restartBtn" 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Restart
            </button>
        @endif
        <a href="{{ route('rooms.show', $roomId) }}" 
           class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
            Back to Room
        </a>
    </div>

    <script type="module">
        import initMinesweeper from "/resources/js/minesweeper.js";

        console.log(@json($config));
        initMinesweeper(@json($config));
        initMinesweeper({
            rows: {{ $rows }},
            cols: {{ $cols }},
            mines: {{ $mines }},
            board: @json(json_decode($board)),
            flags: @json($flags),
            revealed: @json($revealed),
            roomId: {{ $roomId }},
            playerColor: "{{ $playerColor }}"
        });
    </script>
</div>
