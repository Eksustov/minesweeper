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
                                    <button type="submit"
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
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const roomList = document.getElementById('roomList');
            const card = document.getElementById('createRoomCard');
            const select = document.getElementById('roomTypeSelect');

            const publicGradient = 'linear-gradient(to right, #6366f1, #8b5cf6)';
            const privateGradient = 'linear-gradient(to right, #6b21a8, #4c1d95)';

            function updateCardColor() {
                card.style.background = (select.value === 'private') ? privateGradient : publicGradient;
            }
            updateCardColor();
            select.addEventListener('change', updateCardColor);
            card.addEventListener('mouseleave', updateCardColor);

            // --- helpers ---
            const cap = s => s.charAt(0).toUpperCase() + s.slice(1);

            function renderRoomItem(room) {
                const isPrivate = room.type === 'private';
                const badgeCls = isPrivate
                    ? 'bg-purple-100 text-purple-700'
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

                // Match the original fancy card structure (gradient hover glow, etc.)
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

            // Simple diff: only replace an li's content if it actually changed
            function upsertRoom(room) {
                const id = `room-${room.id}`;
                const html = renderRoomItem(room);
                const existing = document.getElementById(id);
                if (!existing) {
                    roomList.insertAdjacentHTML('beforeend', html);
                    return;
                }
                // Compare by a lightweight fingerprint
                const fingerprint = `${room.code}|${room.type}|${room.current_players}|${room.max_players}|${room.isInRoom}`;
                if (existing.dataset.fingerprint !== fingerprint) {
                    // Replace while keeping position
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    const next = wrapper.firstElementChild;
                    next.dataset.fingerprint = fingerprint;
                    existing.replaceWith(next);
                }
            }

            function rebuildAll(rooms) {
                // Build a fragment to avoid layout thrash
                const frag = document.createDocumentFragment();
                rooms.forEach(room => {
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = renderRoomItem(room);
                    const li = wrapper.firstElementChild;
                    li.dataset.fingerprint = `${room.code}|${room.type}|${room.current_players}|${room.max_players}|${room.isInRoom}`;
                    frag.appendChild(li);
                });
                roomList.innerHTML = '';
                roomList.appendChild(frag);
            }

            // Fetch rooms (keeps your polling, but now pretty)
            async function fetchRooms() {
                try {
                    const res = await fetch('/rooms/json', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) {
                        console.error('Failed to fetch rooms:', res.status);
                        return;
                    }
                    const rooms = await res.json();

                    if (!Array.isArray(rooms) || rooms.length === 0) {
                        roomList.innerHTML = '<li class="text-gray-400 text-center py-6 italic">No active rooms right now.</li>';
                        return;
                    }

                    // If counts differ, rebuild; else upsert per-room
                    const currentIds = Array.from(roomList.children).map(li => li.id).filter(Boolean);
                    const newIds = rooms.map(r => `room-${r.id}`);

                    const structureChanged = currentIds.length !== newIds.length ||
                        currentIds.some((id, i) => id !== newIds[i]);

                    if (structureChanged || roomList.children.length === 0) {
                        rebuildAll(rooms);
                    } else {
                        rooms.forEach(upsertRoom);
                    }
                } catch (error) {
                    console.error('Error fetching rooms:', error);
                }
            }

            fetchRooms();
            setInterval(fetchRooms, 5000);
        });
        </script>
</x-app-layout>
