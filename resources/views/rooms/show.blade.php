@section('title', $room->code)
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h2 class="font-semibold text-2xl text-white tracking-tight">
                    Room: {{ $room->code }}
                </h2>
                <p class="text-sm text-white/70">
                    Host: <span class="font-medium">{{ $room->creator->name }}</span>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold
                             {{ $room->type === 'private' ? 'bg-purple-600/20 text-purple-200 ring-1 ring-purple-500/40' : 'bg-emerald-600/20 text-emerald-200 ring-1 ring-emerald-500/40' }}">
                    {{ ucfirst($room->type) }}
                </span>

                <button
                    id="copyCodeBtn"
                    type="button"
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-xs font-semibold bg-white/10 text-white hover:bg-white/20 transition"
                    data-code="{{ $room->code }}"
                    title="Copy room code"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 7a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3h-1v-2h1a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-6a1 1 0 0 0-1 1v1H8V7Z"/><path d="M5 9a3 3 0 0 0-3 3v6a3 3 0 0 0 3 3h6a3 3 0 0 0 3-3v-6a3 3 0 0 0-3-3H5Zm0 2h6a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1Z"/></svg>
                    Copy code
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-10 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <div class="rounded-2xl bg-white/70 backdrop-blur shadow-xl ring-1 ring-black/5 overflow-hidden">

                <!-- Top stats -->
                <div class="p-6 border-b bg-gradient-to-r from-indigo-600 to-fuchsia-600 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm opacity-90">Capacity</div>
                            <div class="mt-1 flex items-end gap-2">
                                <div class="text-2xl font-bold">
                                    {{ $room->players->count() }}/{{ $room->max_players }}
                                </div>
                                <div class="text-xs opacity-80">players</div>
                            </div>
                        </div>
                        <div class="w-40">
                            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                @php
                                    $pct = max(0, min(100, round(($room->players->count() / max(1,$room->max_players)) * 100)));
                                @endphp
                                <div class="h-full bg-white/90" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="mt-1 text-[10px] tracking-wide opacity-80">{{ $pct }}% full</div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 grid gap-8 md:grid-cols-2">
                    <!-- Left: Actions -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Actions</h3>

                        {{-- Join room (if not in) --}}
                        @if(!$room->players->contains(auth()->id()) && $room->players->count() < $room->max_players)
                            <form method="POST" action="{{ route('rooms.join', $room) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-emerald-600 text-white font-semibold shadow hover:bg-emerald-700 transition">
                                    Join Room
                                </button>
                            </form>
                        @endif

                        {{-- Leave room (if in) --}}
                        @if($room->players->contains(auth()->id()))
                            <form method="POST" action="{{ route('rooms.leave', $room) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-rose-600 text-white font-semibold shadow hover:bg-rose-700 transition">
                                    Leave Room
                                </button>
                            </form>
                        @endif

                        {{-- Game actions --}}
                        @if($activeGame)
                            <form method="GET" action="{{ route('games.show', $room) }}">
                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold shadow hover:bg-purple-700 transition">
                                    Join Game
                                </button>
                            </form>
                        @elseif($room->user_id === auth()->id())
                            <form x-data="{ d: 'easy' }" method="POST" action="{{ route('games.start', $room) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Difficulty</label>
                                    <select x-model="d" name="difficulty" id="difficulty"
                                            class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="easy">Easy (8x8, 10 mines)</option>
                                        <option value="medium">Medium (12x12, 20 mines)</option>
                                        <option value="hard">Hard (16x16, 40 mines)</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>

                                <div id="customSettings" x-show="d === 'custom'" x-transition.opacity class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Rows</label>
                                        <input type="number" name="rows" min="5" max="50" value="10"
                                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Columns</label>
                                        <input type="number" name="cols" min="5" max="50" value="10"
                                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Mines</label>
                                        <input type="number" name="mines" min="1" value="10"
                                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700 transition">
                                    Start Game
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Right: Players -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Players</h3>

                        <ul id="playersList" class="space-y-2">
                            @foreach ($room->players as $player)
                                <li id="player-{{ $player->id }}"
                                    class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 hover:shadow transition">
                                    <span class="inline-block size-3 rounded-full ring-2 ring-offset-2 ring-offset-white"
                                          style="background-color: {{ $player->pivot->color }};"></span>

                                    <span class="font-medium text-gray-800">
                                        {{ $player->name }}
                                        @if ($player->id === $room->creator->id)
                                            <span class="ml-2 text-xs text-amber-600 font-semibold">host</span>
                                        @endif
                                    </span>

                                    @if ($room->user_id === auth()->id() && $player->id !== auth()->id())
                                        <button
                                            class="ml-auto inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 transition"
                                            onclick="kickPlayer({{ $player->id }})"
                                            title="Kick {{ $player->name }}"
                                        >
                                            Kick
                                        </button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-white/70">
                Room ID: <span class="font-mono">{{ $room->id }}</span>
            </p>
        </div>
    </div>

    <div id="room-meta" data-room-id="{{ $room->id }}"></div>

    <script>
        // Copy room code
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('copyCodeBtn');
            if (btn) {
                btn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(btn.dataset.code);
                        btn.textContent = 'Copied!';
                        setTimeout(() => btn.textContent = 'Copy code', 1200);
                    } catch {}
                });
            }
        });

        document.addEventListener("DOMContentLoaded", () => {
            const roomMeta = document.getElementById("room-meta");
            if (!roomMeta) return;

            const roomId = roomMeta.dataset.roomId;
            const playersList = document.getElementById('playersList');

            function rebuildPlayersList(players) {
                playersList.innerHTML = '';
                players.forEach(player => {
                    const li = document.createElement('li');
                    li.id = `player-${player.id}`;
                    li.className = 'group flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 hover:shadow transition';
                    li.innerHTML = `
                        <span class="inline-block size-3 rounded-full ring-2 ring-offset-2 ring-offset-white" style="background-color: ${player.color ?? '#ccc'}"></span>
                        <span class="font-medium text-gray-800">${player.name}</span>
                        ${
                          @json($room->user_id === auth()->id())
                          ? ` ${player.id !== {{ auth()->id() }} ? '<button class="ml-auto inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 transition" onclick="kickPlayer('+player.id+')">Kick</button>' : ''}`
                          : ''
                        }
                    `;
                    playersList.appendChild(li);
                });
            }

            window.kickPlayer = function(playerId) {
                if (!confirm("Are you sure you want to kick this player?")) return;
                axios.post(`/rooms/${roomId}/kick`, { user_id: playerId })
                    .then(res => console.log(res.data.message))
                    .catch(err => alert(err.response?.data?.message || 'Failed to kick player'));
            };

            window.Echo.channel(`room.${roomId}`)
                .listen('.PlayerJoined', (e) => { rebuildPlayersList(e.players); })
                .listen('.PlayerLeft',   (e) => { rebuildPlayersList(e.players); });

            window.Echo.channel(`room.${roomId}`)
                .listen('.PlayerKicked', e => {
                    if (e.playerId === {{ auth()->id() }}) {
                        alert("You have been kicked from the room!");
                        window.location.href = "{{ route('welcome') }}";
                    } else {
                        const li = document.getElementById(`player-${e.playerId}`);
                        if (li) li.remove();
                    }
                });
        });
    </script>
</x-app-layout>
