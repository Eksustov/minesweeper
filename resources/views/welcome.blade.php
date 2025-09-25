@section('title', 'Main page')
<x-app-layout>
<x-slot name="header">
    <h2 class="font-bold text-2xl text-gray-800">
        {{ __('Rooms') }}
    </h2>
</x-slot>

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

    <!-- Available Rooms Card -->
    <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-2xl">
        <h3 class="text-2xl font-bold mb-4 text-gray-800">Available Rooms</h3>
        <ul id="roomList" class="space-y-4">
        @forelse($rooms as $room)
            <li id="room-{{ $room->id }}" class="p-4 border border-gray-200 rounded-xl flex justify-between items-center hover:shadow-lg transition-shadow duration-300">
                <div>
                    <span class="font-semibold text-gray-700">{{ $room->code }}</span>
                    <span class="text-gray-500 ml-2">({{ ucfirst($room->type) }})</span>
                    <span class="text-gray-400 ml-2">{{ $room->players->count() }}/{{ $room->max_players }}</span>
                </div>

                <div class="flex space-x-2">
                    @if($room->players->contains(auth()->id()))
                        <form method="GET" action="{{ route('rooms.show', $room) }}">
                            <button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition-colors">
                                Enter Room
                            </button>
                        </form>
                    @elseif($room->players->count() < $room->max_players)
                        <form method="POST" action="{{ route('rooms.join', $room) }}">
                            @csrf
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                                Join Room
                            </button>
                        </form>
                    @else
                        <span class="text-red-500 font-semibold">Room is full</span>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-gray-400 text-center py-4">No active rooms right now.</li>
        @endforelse
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const roomList = document.getElementById('roomList');
    const card = document.getElementById('createRoomCard');
    const select = document.getElementById('roomTypeSelect');

    const publicGradient = 'linear-gradient(to right, #6366f1, #8b5cf6)';
    const privateGradient = 'linear-gradient(to right, #6b21a8, #4c1d95)';
    const hoverGradient = 'linear-gradient(to right, #7c3aed, #9333ea)';

    function updateCardColor() {
        if (select.value === 'private') {
            card.style.background = privateGradient;
        } else {
            card.style.background = publicGradient;
        }
    }

    // Initial update
    updateCardColor();

    // Change background when select changes
    select.addEventListener('change', updateCardColor);

    // Add hover effect
    card.addEventListener('mouseenter', () => {
        card.style.background = hoverGradient;
    });
    card.addEventListener('mouseleave', () => {
        updateCardColor();
    });

    // Fetch rooms
    async function fetchRooms() {
        const res = await fetch('/rooms/json');
        const rooms = await res.json();

        roomList.innerHTML = '';

        if (rooms.length === 0) {
            roomList.innerHTML = '<li class="text-gray-500">No active rooms right now.</li>';
            return;
        }

        rooms.forEach(room => {
            const li = document.createElement('li');
            li.id = `room-${room.id}`;
            li.classList.add('mb-2', 'p-2', 'border', 'rounded', 'flex', 'justify-between', 'items-center');

            let buttonHTML = '';

            if (room.isInRoom) {
                buttonHTML = `
                    <form method="GET" action="/rooms/${room.id}">
                        <button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                            Enter Room
                        </button>
                    </form>`;
            } else if (room.current_players < room.max_players) {
                buttonHTML = `
                    <form method="POST" action="/rooms/${room.id}/join">
                        @csrf
                        <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">
                            Join Room
                        </button>
                    </form>`;
            } else {
                buttonHTML = `<span class="text-red-500">Room is full</span>`;
            }

            li.innerHTML = `<span>${room.code} (${room.type.charAt(0).toUpperCase() + room.type.slice(1)}) — ${room.current_players}/${room.max_players}</span>${buttonHTML}`;
            roomList.appendChild(li);
        });
    }

    fetchRooms();
    setInterval(fetchRooms, 5000);
});
</script>
</x-app-layout>
