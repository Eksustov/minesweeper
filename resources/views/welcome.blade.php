@section('title', 'Main page')
<x-app-layout>

    @php
        $selectedType = old('type') ?? 'public';
    @endphp

    <div class="py-12 flex flex-col items-center space-y-8">

        <!-- Create Room Card -->
        <div id="createRoomCard"
            class="shadow-xl rounded-2xl p-6 w-full max-w-md transform transition-all duration-500 hover:scale-105"
            style="background: linear-gradient(to right, {{ $selectedType === 'private' ? '#6b21a8, #4c1d95' : '#6366f1, #8b5cf6' }}); transition: background 0.5s ease-in-out;">
            <h3 class="text-white text-xl font-semibold mb-4">Create a Room</h3>
            <form method="POST" action="{{ route('rooms.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-white font-medium mb-1">Room Type</label>
                    <select name="type"
                            id="roomTypeSelect"
                            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:outline-none
                                focus:ring-indigo-300 bg-white text-gray-700 hover:bg-gray-100 transition-colors">
                        <option value="public" {{ $selectedType === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="private" {{ $selectedType === 'private' ? 'selected' : '' }}>Private</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-white font-medium mb-1">Max Players</label>
                    <input type="number" name="max_players" value="2" min="2" max="10"
                        class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                        required>
                </div>
                <button type="submit"
                        class="w-full bg-white text-indigo-600 font-semibold py-2 rounded-lg hover:bg-gray-100 transition-colors">
                    Create Room
                </button>
            </form>
        </div>

          <!-- Join by Code Card (only when logged in) -->
        @auth
        <div class="bg-white shadow-xl rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">Join a Room by Code</h3>
            @if (session('error'))
                <div class="mb-3 text-sm text-red-600">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="mb-3 text-sm text-green-600">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('rooms.joinByCode') }}" class="flex space-x-2">
                @csrf
                <input
                    type="text"
                    name="code"
                    maxlength="6"  {{-- adjust if your code length differs --}}
                    placeholder="Enter 6-char code"
                    class="flex-1 border border-gray-300 rounded-lg p-2 uppercase tracking-widest focus:ring-2 focus:ring-indigo-300 focus:outline-none"
                    required
                />
                <button
                    type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Join
                </button>
            </form>

            <p class="mt-2 text-xs text-gray-500">Ask a friend to share their room code.</p>
        </div>
        @endauth

        <!-- Available Rooms Card -->
        <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-2xl">
            <h3 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Available Rooms</h3>

            <ul id="roomList" class="space-y-4">
                @forelse($rooms as $room)
                    <li id="room-{{ $room->id }}"
                        class="relative group p-5 border border-gray-200 rounded-xl flex justify-between items-center
                            hover:shadow-xl transition duration-300 ease-in-out hover:-translate-y-1">

                        <!-- Gradient border hover glow -->
                        <span class="absolute inset-0 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 opacity-0 group-hover:opacity-100 blur-md transition duration-500"></span>
                        <span class="absolute inset-0 rounded-xl bg-white"></span>

                        <!-- Content -->
                        <div class="relative flex flex-col">
                            <span class="font-semibold text-gray-800 text-lg">{{ $room->code }}</span>
                            <div class="flex items-center mt-1 space-x-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $room->type === 'private' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                                    {{ ucfirst($room->type) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">
                                    {{ $room->players->count() }}/{{ $room->max_players }} players
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="relative flex space-x-2">
                            @if($room->players->contains(auth()->id()))
                                <form method="GET" action="{{ route('rooms.show', $room) }}">
                                    <button type="submit"
                                        class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition-colors shadow-md hover:shadow-lg">
                                        Enter
                                    </button>
                                </form>
                            @elseif($room->players->count() < $room->max_players)
                                <form method="POST" action="{{ route('rooms.join', $room) }}">
                                    @csrf
                                    <button id="join_room_from_list" 
                                        type="submit"
                                        class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors shadow-md hover:shadow-lg">
                                        Join
                                    </button>
                                </form>
                            @else
                                <span class="text-red-500 font-semibold">Room is full</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-gray-400 text-center py-6 italic">No active rooms right now.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <script>
            document.addEventListener('DOMContentLoaded', () => {
            // --- Create card gradient (optional, keep if you have that card) ---
            const card  = document.getElementById('createRoomCard');
            const select = document.getElementById('roomTypeSelect');
            if (card && select) {
                const publicGradient  = 'linear-gradient(to right, #6366f1, #8b5cf6)';
                const privateGradient = 'linear-gradient(to right, #6b21a8, #4c1d95)';
                const updateCardColor = () => {
                card.style.background = (select.value === 'private') ? privateGradient : publicGradient;
                };
                updateCardColor();
                select.addEventListener('change', updateCardColor);
                card.addEventListener('mouseleave', updateCardColor);
            }

            // --- Rooms list + pagination ---
            const roomList = document.getElementById('roomList');
            // We'll append a pager footer after the card that contains the list
            let pagerEl = null;

            // Keep a stable CSRF value if present (for Join forms)
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // Client-side pagination state
            let currentPage = 1;
            const perPage   = 8; // tweak to taste
            let pollTimer   = null;

            // Helpers
            const cap = s => s ? s.charAt(0).toUpperCase() + s.slice(1) : s;

            function renderRoomItem(room) {
                const isPrivate = room.type === 'private';
                const badgeCls  = isPrivate ? 'bg-purple-100 text-purple-700'
                                            : 'bg-green-100 text-green-700';

                // Actions (Enter / Join / Full)
                let actionsHTML = '';
                if (room.isInRoom) {
                actionsHTML = `
                    <form method="GET" action="/rooms/${room.id}">
                    <button type="submit"
                        class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition-colors shadow-md hover:shadow-lg">
                        Enter
                    </button>
                    </form>`;
                } else if (room.current_players < room.max_players) {
                actionsHTML = `
                    <form method="POST" action="/rooms/${room.id}/join">
                    <input type="hidden" name="_token" value="${csrf}">
                    <button type="submit"
                        class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors shadow-md hover:shadow-lg">
                        Join
                    </button>
                    </form>`;
                } else {
                actionsHTML = `<span class="text-red-500 font-semibold">Room is full</span>`;
                }

                return `
                <li id="room-${room.id}"
                    class="relative group p-5 border border-gray-200 rounded-xl flex justify-between items-center
                            hover:shadow-xl transition duration-300 ease-in-out hover:-translate-y-1">

                    <!-- Gradient border hover glow -->
                    <span class="absolute inset-0 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 opacity-0 group-hover:opacity-100 blur-md transition duration-500"></span>
                    <span class="absolute inset-0 rounded-xl bg-white"></span>

                    <!-- Content -->
                    <div class="relative flex flex-col">
                    <span class="font-semibold text-gray-800 text-lg">${room.code}</span>
                    <div class="flex items-center mt-1 space-x-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium ${badgeCls}">
                        ${cap(room.type)}
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">
                        ${room.current_players}/${room.max_players} players
                        </span>
                    </div>
                    </div>

                    <!-- Actions -->
                    <div class="relative flex space-x-2">
                    ${actionsHTML}
                    </div>
                </li>`;
            }

            function rebuildAll(rooms) {
                if (!roomList) return;
                if (!rooms.length) {
                roomList.innerHTML = '<li class="text-gray-400 text-center py-6 italic">No active rooms right now.</li>';
                return;
                }
                const frag = document.createDocumentFragment();
                rooms.forEach(room => {
                const wrap = document.createElement('div');
                wrap.innerHTML = renderRoomItem(room);
                frag.appendChild(wrap.firstElementChild);
                });
                roomList.innerHTML = '';
                roomList.appendChild(frag);
            }

            function ensurePagerContainer() {
                if (pagerEl) return pagerEl;
                // append pager right after the list's card container
                // roomList.parentElement is the card body; its parent is the card
                const cardContainer = roomList?.parentElement;
                pagerEl = document.createElement('div');
                pagerEl.id = 'roomsPager';
                pagerEl.className = 'flex items-center justify-between mt-4';
                cardContainer?.appendChild(pagerEl);
                return pagerEl;
            }

            function renderPager(meta) {
                // If the API returns plain array (no pagination), remove pager
                if (!meta) {
                if (pagerEl) pagerEl.remove();
                pagerEl = null;
                return;
                }
                const el = ensurePagerContainer();
                el.innerHTML = `
                <button id="roomsPrev"
                        class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50"
                        ${meta.current_page <= 1 ? 'disabled' : ''}>
                    Previous
                </button>
                <span class="text-sm text-gray-600">Page ${meta.current_page} of ${meta.last_page}</span>
                <button id="roomsNext"
                        class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50"
                        ${meta.current_page >= meta.last_page ? 'disabled' : ''}>
                    Next
                </button>
                `;

                el.querySelector('#roomsPrev')?.addEventListener('click', () => {
                currentPage = Math.max(1, meta.current_page - 1);
                fetchRooms(currentPage);
                });
                el.querySelector('#roomsNext')?.addEventListener('click', () => {
                currentPage = Math.min(meta.last_page, meta.current_page + 1);
                fetchRooms(currentPage);
                });
            }

            async function fetchRooms(page = 1) {
                try {
                const res = await fetch(`/rooms/json?per_page=${perPage}&page=${page}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    console.error('Failed to fetch rooms:', res.status);
                    return;
                }

                const payload = await res.json();
                // Support both shapes: array (legacy) or { data, meta } (paginated)
                const rooms = Array.isArray(payload) ? payload : (payload.data || []);
                const meta  = Array.isArray(payload) ? null    : (payload.meta || null);

                rebuildAll(rooms);
                renderPager(meta);

                if (meta) currentPage = meta.current_page || 1;
                } catch (err) {
                console.error('Error fetching rooms:', err);
                }
            }

            // Initial load + polling on the current page
            fetchRooms(currentPage);
            // Polling keeps the list fresh without navigating pages
            pollTimer = setInterval(() => fetchRooms(currentPage), 5000);
            });
        </script>
</x-app-layout>
