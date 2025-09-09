@section('title', 'Main page')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Rooms') }}
        </h2>
    </x-slot>

    <div class="py-12 flex flex-col items-center space-y-4">
        <!-- Create Room Form -->
        <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-md">
            <form method="POST" action="{{ route('rooms.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-700">Room Type</label>
                    <select name="type" class="w-full border p-2 rounded">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Max Players</label>
                    <input type="number" name="max_players" value="2" min="2" max="10" class="w-full border p-2 rounded" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">
                    Create Room
                </button>
            </form>
        </div>

        <!-- List of Rooms -->
        <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-2">Available Rooms</h3>
            <ul>
            @forelse($rooms as $room)
                <li id="room-{{ $room->id }}" class="mb-2 p-2 border rounded flex justify-between items-center">
                    <span>{{ $room->code }} ({{ ucfirst($room->type) }}) — {{ $room->players->count() }}/{{ $room->max_players }}</span>
                    <form method="POST" action="{{ route('rooms.join', $room) }}">
                        @csrf
                        <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600" 
                            @if($room->players->count() >= $room->max_players) disabled @endif>
                                Join
                        </button>
                    </form>
                </li>
            @empty
                <li class="text-gray-500">No active rooms right now.</li>
            @endforelse
            </ul>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const roomList = document.querySelector('ul');

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
                li.innerHTML = `
                    <span>${room.code} (${room.type.charAt(0).toUpperCase() + room.type.slice(1)}) — ${room.current_players}/${room.max_players}</span>
                    <form method="POST" action="/rooms/${room.id}/join">
                        @csrf
                        <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600"
                            ${room.current_players >= room.max_players ? 'disabled' : ''}>
                            Join
                        </button>
                    </form>
                `;
                roomList.appendChild(li);
            });
        }

        // Fetch rooms every 5 seconds
        fetchRooms();
        setInterval(fetchRooms, 5000);
    });
    </script>


</x-app-layout>
