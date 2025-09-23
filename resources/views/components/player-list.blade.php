<div>
    <h2 class="text-lg font-bold mb-2">Players</h2>
    <ul id="playerList">
        @foreach ($room->players as $player)
            <li id="player-{{ $player->id }}" class="flex items-center space-x-2 mb-1">
                <span class="inline-block w-4 h-4 rounded" style="background-color: {{ $player->pivot->color }}"></span>
                <span>
                    {{ $player->name }}
                    @if ($player->id === $room->creator->id)
                        <span class="text-sm text-gray-500">(creator)</span>
                    @endif
                </span>
            </li>
        @endforeach
    </ul>
</div>
