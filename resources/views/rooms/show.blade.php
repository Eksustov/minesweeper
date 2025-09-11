@section('title', $room->code)
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Room: '.$room->code) }}
        </h2>
    </x-slot>

    <div class="py-12 flex flex-col items-center space-y-4">
        <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Room Details</h3>

            <p><strong>Creator:</strong> {{ $room->creator->name }}</p>

            <h4 class="mt-4 font-semibold">Players in this room:</h4>
            <ul id="playersList" class="list-disc list-inside">
                @foreach($room->players as $player)
                    <li>{{ $player->name }}</li>
                @endforeach
            </ul>

            <!-- Join room button if not in room -->
            @if(!$room->players->contains(auth()->id()) && $room->players->count() < $room->max_players)
                <form method="POST" action="{{ route('rooms.join', $room) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600">
                        Join Room
                    </button>
                </form>
            @endif

            <!-- Leave room button if in room -->
            @if($room->players->contains(auth()->id()))
                <form method="POST" action="{{ route('rooms.leave', $room) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-600">
                        Leave Room
                    </button>
                </form>
            @endif

            <!-- Show join game if active -->
            @if($activeGame)
                <form method="GET" action="{{ route('rooms.game', $room) }}" class="mt-4">
                    <button type="submit" class="w-full bg-purple-500 text-white py-2 rounded hover:bg-purple-600">
                        Join Game
                    </button>
                </form>

            <!-- Show start game options only to creator if no active game -->
            @elseif($room->user_id === auth()->id())
                <form method="POST" action="{{ route('rooms.start', $room) }}" class="mt-4">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold">Difficulty</label>
                        <select name="difficulty" id="difficulty" class="w-full border p-2 rounded">
                            <option value="easy">Easy (8x8, 10 mines)</option>
                            <option value="medium">Medium (12x12, 20 mines)</option>
                            <option value="hard">Hard (16x16, 40 mines)</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <div id="customSettings" class="hidden mb-4">
                        <label class="block text-gray-700">Rows</label>
                        <input type="number" name="rows" class="w-full border p-2 rounded" min="5" max="50" value="10">
                        <label class="block text-gray-700 mt-2">Columns</label>
                        <input type="number" name="cols" class="w-full border p-2 rounded" min="5" max="50" value="10">
                        <label class="block text-gray-700 mt-2">Mines</label>
                        <input type="number" name="mines" class="w-full border p-2 rounded" min="1" value="10">
                    </div>

                    <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">
                        Start Game
                    </button>
                </form>
            @endif

        </div>
    </div>

    <div id="room-meta" data-room-id="{{ $room->id }}"></div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const meta = document.getElementById("room-meta");
            if (!meta) return;

            const roomId = meta.dataset.roomId;

            // Listen for GameStarted event
            window.Echo.channel(`room.${roomId}`)
            .listen(".GameStarted", (e) => {
                window.location.href = `/rooms/${roomId}/game`;
            });

            const difficultySelect = document.getElementById('difficulty');
            const customSettings = document.getElementById('customSettings');

            function toggleCustom() {
                const isCustom = difficultySelect.value === 'custom';
                customSettings.classList.toggle('hidden', !isCustom);
                document.querySelectorAll('#customSettings input').forEach(input => {
                    input.disabled = !isCustom;
                });
            }

            if (difficultySelect) {
                difficultySelect.addEventListener('change', toggleCustom);
                toggleCustom();
            }
        });
    </script>
</x-app-layout>
