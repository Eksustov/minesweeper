{{-- resources/views/rooms/show.blade.php --}}
@section('title', $room->code)
<x-app-layout>
    @php
        $activeGame     = $room->games()->where('started', true)->latest()->first();
        $allColors      = config('colors.list');
        $myColor        = optional($room->players->firstWhere('id', auth()->id()))?->pivot?->color;
        $takenByOthers  = $room->players
            ->filter(fn($p) => $p->id !== auth()->id())
            ->pluck('pivot.color')->filter()->values()->all();
    @endphp

    {{-- Header --}}
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
            <div class="rounded-2xl bg-white/70 backdrop-blur shadow-xl ring-1 ring-black/5 overflow-visible">

                {{-- Capacity (live-updating) --}}
                <div class="p-6 border-b bg-gradient-to-r from-indigo-600 to-fuchsia-600 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm opacity-90">Capacity</div>
                            <div class="mt-1 flex items-end gap-2">
                                <div id="capacityCount" class="text-2xl font-bold">
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
                                <div id="capacityBar" class="h-full bg-white/90 transition-all duration-300" style="width: {{ $pct }}%"></div>
                            </div>
                            <div id="capacityPctText" class="mt-1 text-[10px] tracking-wide opacity-80">{{ $pct }}% full</div>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-6 grid gap-8 md:grid-cols-2">
                    {{-- Left: Actions --}}
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
                        @php $activeGameInline = $activeGame; @endphp
                        @if($activeGameInline)
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

                    {{-- Right: Players --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Players</h3>

                        <ul id="playersList" class="space-y-2">
                            @foreach ($room->players as $player)
                                @php
                                    $isMe = $player->id === auth()->id();
                                    $dotColor = $player->pivot->color ?? '#ccc';
                                @endphp

                                <li id="player-{{ $player->id }}"
                                    class="group relative flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 hover:shadow transition">

                                    @if($isMe && !$activeGame)
                                        {{-- Clickable dot for me (pre-game) --}}
                                        <button
                                            id="myColorDot"
                                            type="button"
                                            class="inline-block size-3 rounded-full ring-2 ring-offset-2 ring-offset-white outline-none focus:ring-indigo-500"
                                            style="background-color: {{ $dotColor }};"
                                            aria-haspopup="true" aria-expanded="false"
                                        ></button>

                                        {{-- Desktop popover --}}
                                        <div id="colorPopover"
                                            class="hidden absolute z-30 top-10 left-2 w-56 rounded-xl border border-gray-200 bg-white/95 shadow-lg backdrop-blur p-3">
                                            <div class="text-xs font-semibold text-gray-600 mb-2">Pick a color</div>
                                            <div class="grid grid-cols-6 gap-2" data-role="palette-desktop"></div>
                                            <div class="mt-2 text-[10px] text-gray-500">Colors taken by others are disabled.</div>
                                        </div>

                                        {{-- Mobile bottom sheet --}}
                                        <div id="colorSheet"
                                            class="hidden fixed inset-0 z-50 sm:hidden">
                                            <div class="absolute inset-0 bg-black/40" data-role="sheet-backdrop"></div>
                                            <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white shadow-xl p-4">
                                                <div class="flex items-center justify-between">
                                                    <div class="text-sm font-semibold text-gray-900">Pick a color</div>
                                                    <button type="button" class="px-2 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200" data-role="sheet-close">Close</button>
                                                </div>
                                                <div class="mt-3 grid grid-cols-6 gap-2" data-role="palette-mobile"></div>
                                                <div class="mt-2 text-[11px] text-gray-500">Colors taken by others are disabled.</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-block size-3 rounded-full ring-2 ring-offset-2 ring-offset-white"
                                            style="background-color: {{ $dotColor }};"></span>
                                    @endif
                                    <span class="font-medium text-gray-800">
                                        {{ $player->name }}
                                        @if ($player->id === $room->creator->id)
                                            <span class="ml-2 text-xs text-amber-600 font-semibold">host</span>
                                        @endif
                                    </span>

                                    @if ($room->user_id === auth()->id() && $player->id !== auth()->id())
                                        <button
                                            class="ml-auto inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 transition"
                                            onclick="openKickModal({{ $player->id }}, @js($player->name))"
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

    {{-- Hidden meta for JS --}}
    <div id="room-meta"
         data-room-id="{{ $room->id }}"
         data-room-max="{{ $room->max_players }}">
    </div>

    {{-- Scripts --}}
    <script>
        if (!window.__colorPicker) {
            window.__colorPicker = (function () {
                let myDot = null, pop = null, sheet = null;

                const hideDesktop = () => {
                if (pop) pop.classList.add('hidden');
                if (myDot) myDot.setAttribute('aria-expanded','false');
                };
                const hideMobile = () => { if (sheet) sheet.classList.add('hidden'); };

                const onDocClick = (e) => {
                // close only if clicking outside the popover and not on the dot
                if (pop && !pop.contains(e.target) && e.target !== myDot) hideDesktop();
                };
                const onEsc = (e) => {
                if (e.key === 'Escape') { hideDesktop(); hideMobile(); }
                };

                // Attach global listeners once
                if (!window.__cpGlobalBound) {
                document.addEventListener('click', onDocClick);
                document.addEventListener('keydown', onEsc);
                window.__cpGlobalBound = true;
                }

                // bind fresh elements after each rebuild
                function bind(nextDot, nextPop, nextSheet) {
                myDot  = nextDot || null;
                pop    = nextPop || null;
                sheet  = nextSheet || null;
                }

                return { bind, hideDesktop, hideMobile };
            })();
            }

            // ===============================
            // Shared helpers
            // ===============================
            function normalizeColor(input) {
            const ctx = document.createElement('canvas').getContext('2d');
            ctx.fillStyle = '#000';
            ctx.fillStyle = String(input || '');
            return ctx.fillStyle.toLowerCase(); // canonical hex-like string
            }

            // Capacity UI
            window.updateCapacity = function(current) {
            const meta = document.getElementById('room-meta');
            const maxSeats = parseInt(meta?.dataset.roomMax || '1', 10);
            const capacityCount   = document.getElementById('capacityCount');
            const capacityBar     = document.getElementById('capacityBar');
            const capacityPctText = document.getElementById('capacityPctText');

            const pct = Math.max(0, Math.min(100, Math.round((current / Math.max(1, maxSeats)) * 100)));
            if (capacityCount)   capacityCount.textContent   = `${current}/${maxSeats}`;
            if (capacityBar)     capacityBar.style.width     = `${pct}%`;
            if (capacityPctText) capacityPctText.textContent = `${pct}% full`;
            };

            // Rebuild players list from payload (authoritative)
            // Also re-wires the color picker for "me" if game not started.
            window.rebuildPlayersList = function(players) {
            const playersList = document.getElementById('playersList');
            if (!playersList) return;

            playersList.innerHTML = '';

            players.forEach(player => {
                const li = document.createElement('li');
                li.id = `player-${player.id}`;
                li.className = 'group relative flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 hover:shadow transition';

                const isHost  = !!player.isHost;
                const color   = player.color ?? '#ccc';
                const canKick = {{ $room->user_id === auth()->id() ? 'true' : 'false' }} && player.id !== {{ auth()->id() }};
                const isMe    = player.id === {{ auth()->id() }};
                const active  = {{ $activeGame ? 'true' : 'false' }};

                const dot = (!active && isMe)
                ? `<button id="myColorDot" type="button"
                            class="inline-block size-3 rounded-full ring-2 ring-offset-2 ring-offset-white outline-none focus:ring-indigo-500"
                            style="background-color:${color};" aria-haspopup="true" aria-expanded="false"></button>
                    <div id="colorPopover"
                        class="hidden absolute z-30 top-10 left-2 w-56 rounded-xl border border-gray-200 bg-white/95 shadow-lg backdrop-blur p-3">
                        <div class="text-xs font-semibold text-gray-600 mb-2">Pick a color</div>
                        <div class="grid grid-cols-6 gap-2" data-role="palette-desktop"></div>
                        <div class="mt-2 text-[10px] text-gray-500">Colors taken by others are disabled.</div>
                    </div>
                    <div id="colorSheet" class="hidden fixed inset-0 z-50 sm:hidden">
                    <div class="absolute inset-0 bg-black/40" data-role="sheet-backdrop"></div>
                    <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white shadow-xl p-4">
                        <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-gray-900">Pick a color</div>
                        <button type="button" class="px-2 py-1 text-sm rounded-md bg-gray-100 hover:bg-gray-200" data-role="sheet-close">Close</button>
                        </div>
                        <div class="mt-3 grid grid-cols-6 gap-2" data-role="palette-mobile"></div>
                        <div class="mt-2 text-[11px] text-gray-500">Colors taken by others are disabled.</div>
                    </div>
                    </div>`
                : `<span class="inline-block size-3 rounded-full ring-2 ring-offset-2 ring-offset-white" style="background-color:${color}"></span>`;

                li.innerHTML = `
                ${dot}
                <span class="font-medium text-gray-800">
                    ${player.name}
                    ${isHost ? '<span class="ml-2 text-xs text-amber-600 font-semibold">host</span>' : ''}
                </span>
                ${canKick
                    ? '<button class="ml-auto inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 transition">Kick</button>'
                    : ''
                }
                `;

                if (canKick) {
                li.querySelector('button:last-of-type')?.addEventListener('click', () => openKickModal(player.id, player.name));
                }

                playersList.appendChild(li);
            });

            window.updateCapacity(players.length);
            wireColorPicker(); // rebind picker if my row exists
            };

            // ===============================
            // Color Picker wiring
            // ===============================
            function wireColorPicker() {
            const myDot = document.getElementById('myColorDot');
            const pop   = document.getElementById('colorPopover');
            const sheet = document.getElementById('colorSheet');
            if (!myDot) return;

            // (re)bind controller to latest elements
            window.__colorPicker.bind(myDot, pop, sheet);

            const csrf   = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const isMobile = () => window.matchMedia('(max-width: 639px)').matches; // Tailwind sm breakpoint
            const showDesktop = () => { if (pop) { pop.classList.remove('hidden'); myDot.setAttribute('aria-expanded','true'); } };
            const hideDesktop = window.__colorPicker.hideDesktop;
            const showMobile  = () => { if (sheet){ sheet.classList.remove('hidden'); } };
            const hideMobile  = window.__colorPicker.hideMobile;

            const COLORS = @json(config('colors.list'));

            function takenColorsSet() {
                // collect all dots’ background colors (excluding my current)
                const dots = Array.from(document.querySelectorAll('#playersList .inline-block.size-3'));
                const mine = normalizeColor(myDot.style.backgroundColor);
                const set  = new Set(dots.map(el => normalizeColor(el.style.backgroundColor)).filter(Boolean));
                set.delete(mine);
                return set;
            }

            function paintPalette(container) {
                if (!container) return;
                container.innerHTML = '';
                const taken = takenColorsSet();
                const myCurrent = normalizeColor(myDot.style.backgroundColor);

                COLORS.forEach(hex => {
                const btn = document.createElement('button');
                btn.type  = 'button';
                btn.className = 'h-7 w-7 rounded-full ring-2 transition ring-transparent hover:scale-105';
                btn.style.backgroundColor = hex;

                const normHex = normalizeColor(hex);
                const isTaken = taken.has(normHex);
                const isMine  = normHex === myCurrent;

                if (isTaken && !isMine) {
                    btn.disabled = true;
                    btn.classList.add('opacity-40','cursor-not-allowed');
                }

                btn.onclick = () => applyColor(hex); // no stacked listeners
                container.appendChild(btn);
                });
            }

            async function applyColor(colorHex) {
                const prev = myDot.style.backgroundColor;
                // Optimistic local UI
                myDot.style.backgroundColor = colorHex;

                let ok = false;
                try {
                const res = await axios.post("{{ route('rooms.color', $room) }}", { color: colorHex }, { headers: { 'X-CSRF-TOKEN': csrf } });

                // Authoritative list → rebuild → rebind picker
                if (Array.isArray(res?.data?.players)) {
                    window.rebuildPlayersList(res.data.players);
                } else {
                    // fallback: repaint palettes
                    paintPalettes();
                }
                ok = true;
                } catch (err) {
                // rollback, keep UI open so they can choose again
                myDot.style.backgroundColor = prev;
                alert(err?.response?.data?.message || 'Failed to change color');
                paintPalettes();
                } finally {
                if (ok) { hideDesktop(); hideMobile(); }
                }
            }

            // open on click (no auto-open on load)
            myDot.onclick = (e) => {
                e.stopPropagation();
                if (isMobile()) showMobile();
                else pop?.classList.contains('hidden') ? showDesktop() : hideDesktop();
            };

            // mobile closers (avoid stacked handlers)
            const backdrop = sheet?.querySelector('[data-role="sheet-backdrop"]');
            const closer   = sheet?.querySelector('[data-role="sheet-close"]');
            if (backdrop) backdrop.onclick = hideMobile;
            if (closer)   closer.onclick   = hideMobile;

            function paintPalettes() {
                paintPalette(pop?.querySelector('[data-role="palette-desktop"]'));
                paintPalette(sheet?.querySelector('[data-role="palette-mobile"]'));
            }

            // initial paint + keep palettes fresh on resize
            paintPalettes();
            window.addEventListener('resize', paintPalettes, { passive: true });
            }

            // ===============================
            // Kick modal helpers
            // ===============================
            let _pendingKick = { id: null, name: '' };

            window.openKickModal = function(userId, userName) {
            _pendingKick.id   = userId;
            _pendingKick.name = userName || '';
            const txt = document.getElementById('kickConfirmText');
            if (txt) txt.textContent = `Are you sure you want to remove ${_pendingKick.name || 'this player'} from the room?`;
            document.getElementById('kickConfirmModal')?.classList.remove('hidden');
            };

            window.closeKickModal = function() {
            document.getElementById('kickConfirmModal')?.classList.add('hidden');
            _pendingKick = { id: null, name: '' };
            };

            async function confirmKick() {
            const meta = document.getElementById('room-meta');
            const roomId = meta?.dataset.roomId;
            if (!_pendingKick.id || !roomId) return;
            try {
                await axios.post(`/rooms/${roomId}/kick`, { user_id: _pendingKick.id });
            } catch (err) {
                console.error(err);
                alert(err?.response?.data?.message || 'Failed to kick player');
            } finally {
                window.closeKickModal();
            }
            }

            // ===============================
            // Page wiring
            // ===============================
            document.addEventListener('DOMContentLoaded', () => {
            // Copy room code
            const copyBtn = document.getElementById('copyCodeBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(copyBtn.dataset.code);
                    const old = copyBtn.innerHTML;
                    copyBtn.innerHTML = 'Copied!';
                    setTimeout(() => (copyBtn.innerHTML = old), 1200);
                } catch {}
                });
            }

            // Kick modal buttons
            document.getElementById('kickConfirmBtn')?.addEventListener('click', confirmKick);
            document.getElementById('kickedOkBtn')?.addEventListener('click', () => {
                window.location.href = "{{ route('welcome') }}";
            });

            const meta = document.getElementById('room-meta');
            if (!meta) return;

            // Echo channel listeners
            const roomId = meta.dataset.roomId;
            const ch = window.Echo.channel(`room.${roomId}`);

            // Start game -> redirect
            const gameUrl = "{{ route('games.show', $room) }}";
            ch.listen('.GameStarted', (e) => {
                if (!e || typeof e !== 'object') return;
                if (window.location.href !== gameUrl) window.location.href = gameUrl;
            });

            // RoomUpdated -> rebuild list
            ch.listen('.RoomUpdated', (e) => {
                if (Array.isArray(e.players)) {
                    window.rebuildPlayersList(e.players);
                }
            });

            // Join/Leave fallbacks
            ch.listen('.PlayerJoined', (e) => {
                if (Array.isArray(e.players)) window.rebuildPlayersList(e.players);
            });
            ch.listen('.PlayerLeft', (e) => {
                if (Array.isArray(e.players)) window.rebuildPlayersList(e.players);
            });

            // If kicked while here -> bounce home; else update list/capacity
            ch.listen('.PlayerKicked', (e) => {
                @auth
                if (e.playerId === {{ auth()->id() }}) {
                window.location.href = "{{ route('welcome') }}";
                return;
                }
                @endauth
                if (Array.isArray(e.players)) {
                window.rebuildPlayersList(e.players);
                } else {
                const current = document.querySelectorAll('#playersList > li').length;
                window.updateCapacity(current);
                }
            });

            // Initial capacity + color picker wire
            window.updateCapacity(document.querySelectorAll('#playersList > li').length);
            wireColorPicker();
            });
    </script>

    {{-- Kick confirm modal --}}
    <div id="kickConfirmModal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-sm rounded-xl bg-white shadow-xl">
                <div class="px-5 py-4 border-b">
                    <h3 class="text-sm font-semibold text-gray-900">Remove player?</h3>
                    <p id="kickConfirmText" class="mt-1 text-xs text-gray-500"></p>
                </div>
                <div class="px-5 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="px-3 py-2 text-sm rounded-lg bg-gray-100 hover:bg-gray-200"
                            onclick="closeKickModal()">Cancel</button>
                    <button type="button" class="px-3 py-2 text-sm rounded-lg bg-rose-600 text-white hover:bg-rose-700"
                            id="kickConfirmBtn">Kick</button>
                </div>
            </div>
        </div>
    </div>

    {{-- "You’ve been removed" modal (safety if you want to use it here) --}}
    <div id="kickedNoticeModal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-sm rounded-xl bg-white shadow-xl">
                <div class="px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">You’ve been removed from the room</h3>
                    <p class="mt-1 text-xs text-gray-500">You’ll be redirected to the home page.</p>
                </div>
                <div class="px-5 py-4 flex items-center justify-end">
                    <button type="button" class="px-3 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"
                            id="kickedOkBtn">OK</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
