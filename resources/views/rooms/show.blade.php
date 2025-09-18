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
            @foreach ($room->players as $player)
                <li class="flex items-center space-x-2 mb-1">
                    <span class="inline-block w-4 h-4 rounded" style="background-color: {{ $player->pivot->color }}"></span>
                    <span>{{ $player->name }}</span>

                    @if ($room->user_id === auth()->id() && $player->id !== auth()->id())
                        <button
                            class="ml-auto px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600"
                            onclick="kickPlayer({{ $player->id }})"
                        >
                            Kick
                        </button>
                    @endif
                </li>
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
            .listen('.GameStarted', (e) => {
                console.log("✅ Received new game board", e);

                // Reset state
                board = [];
                gameOver = false;
                statusMessage.textContent = '';

                // Reset globals from payload
                rows = e.rows;
                cols = e.cols;
                mines = e.mines;
                savedFlags = {};
                savedRevealed = {};

                const boardEl = document.getElementById('board');
                boardEl.innerHTML = '';

                // Build new board
                e.board.forEach((row, r) => {
                    const rowEl = document.createElement('div');
                    rowEl.classList.add('row');
                    board[r] = [];

                    row.forEach((cell, c) => {
                        const btn = document.createElement('button');
                        btn.classList.add('cell');
                        btn.disabled = false;
                        btn.textContent = '';

                        btn.addEventListener('click', () => reveal(r, c));
                        btn.addEventListener('contextmenu', (ev) => {
                            ev.preventDefault();
                            toggleFlag(r, c);
                        });

                        rowEl.appendChild(btn);

                        board[r][c] = {
                            row: r,
                            col: c,
                            mine: cell.mine,
                            count: cell.count ?? 0,
                            flagged: false,
                            revealed: false,
                            element: btn,
                        };
                    });

                    boardEl.appendChild(rowEl);
                });
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

        function kickPlayer(playerId) {
                if (!confirm("Are you sure you want to kick this player?")) return;

                axios.post('/rooms/{{ $room->id }}/kick', { user_id: playerId })
                    .then(res => {
                        console.log(res.data.message);
                    })
                    .catch(err => {
                        alert(err.response?.data?.message || 'Failed to kick player');
                    });
            }

            if (window.Echo && roomId) {
                window.Echo.channel(`room.${roomId}`)
                    .listen('.PlayerKicked', e => {
                        if (e.playerId === {{ auth()->id() }}) {
                            alert("You have been kicked from the room!");
                            window.location.href = "{{ route('welcome') }}";
                        } else {
                            // Optional: remove player from DOM if someone else was kicked
                            const li = document.querySelector(`#player-${e.playerId}`);
                            if (li) li.remove();
                        }
                    });
            }
    </script>
</x-app-layout>
