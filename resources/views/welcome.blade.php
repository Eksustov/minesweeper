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
                @foreach($rooms as $room)
                    <li class="mb-2 p-2 border rounded flex justify-between items-center">
                        <span>{{ $room->code }} ({{ ucfirst($room->type) }})</span>
                        <a href="#" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Join</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-app-layout>
