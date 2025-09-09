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
            <!-- Join button -->
            @if(! $room->players->contains(auth()->id()) && $room->players->count() < $room->max_players)
                <form method="POST" action="{{ route('rooms.join', $room) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600">
                        Join Room
                    </button>
                </form>
            @endif

            <!-- Leave button -->
            @if($room->players->contains(auth()->id()))
                <form method="POST" action="{{ route('rooms.leave', $room) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-600">
                        Leave Room
                    </button>
                </form>
            @endif

            <!-- Start button if creator -->
            @if($room->user_id === auth()->id())
                <form method="POST" action="{{ route('rooms.start', $room) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">
                        Start Game
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div id="room-meta"
        data-room-id="{{ $room->id }}">
    </div>

</x-app-layout>
