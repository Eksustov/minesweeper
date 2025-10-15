@section('title', $room->code)
<x-app-layout>
    <div class="py-12 flex flex-col items-center space-y-4">
        <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-[95vw] flex">
            
            <!-- Main game section -->
            <div class="flex-1">
                <h1 class="text-2xl font-bold mb-4">Minesweeper</h1>

                <div class="flex justify-between items-center mb-4">
                    <div id="mineCounter" class="text-lg font-bold text-gray-700">Mines: 0</div>
                    <div id="statusMessage" class="text-lg font-bold text-green-600"></div>
                </div>

                <div class="flex justify-center">
                    <div id="board" class="grid gap-1 bg-slate-100 p-1 rounded-lg"></div>
                </div>

                <div class="mt-4 flex space-x-2">
                    @if(auth()->id() === $room->creator->id)
                    <button
                        id="restartBtn"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
                    >
                        Restart Game
                    </button>
                    @endif
                    <a href="{{ route('rooms.show', $room->id) }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Back to Room
                    </a>
                </div>
            </div>

            <!-- Player list -->
            <div class="w-48 ml-6">
                <h2 class="text-lg font-bold mb-2">Players</h2>
                <ul id="playerList">
                    @foreach ($room->players as $player)
                        <li id="player-{{ $player->id }}" class="flex items-center space-x-2 mb-1">
                            <span class="inline-block w-4 h-4 rounded" style="background-color: {{ $player->pivot->color }}"></span>
                            <span>
                                {{ $player->name }}
                                @if ($player->id === $room->creator->id)
                                    <span class="text-sm text-gray-500">(creator)</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Game configuration -->
    <script>
        window.config = {
            userId: {{ auth()->id() }},
            roomId: {{ $room->id }},
            playerColor: "{{ $room->players->find(auth()->id())?->pivot->color ?? '#000000' }}",
            rows: {{ $rows }},
            cols: {{ $cols }},
            mines: {{ $mines }},
            initialBoard: @json($board),       // ✅ array, not double-encoded
            savedFlags: @json($flags ?? []),
            savedRevealed: @json($revealed ?? []),
            updateUrl: "{{ route('games.update', $room->id) }}",
            restartUrl: "{{ route('games.restart', $room->id) }}"
        };
    </script>
</x-app-layout>
