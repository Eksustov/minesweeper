@section('title', $room->code)
<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Minesweeper') }}
        </h2>
    </x-slot>

    <div class="py-12 flex flex-col items-center space-y-4">
        <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-[95vw] flex">
            
            <!-- Main game section -->
            <div class="flex-1">
                <h1 class="text-2xl font-bold mb-4">Minesweeper</h1>

                <!-- Mine counter and status -->
                <div class="flex justify-between items-center mb-4">
                    <div id="mineCounter" class="text-lg font-bold text-gray-700">Mines: 0</div>
                    <div id="statusMessage" class="text-lg font-bold text-green-600"></div>
                </div>

                <!-- Grid container -->
                <div class="flex justify-center">
                    <div id="game" class="grid gap-1"></div>
                </div>

                <!-- Buttons -->
                <div class="mt-4 flex space-x-2">
                    <button id="restartBtn" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Restart
                    </button>

                    <!-- Back to Room button -->
                    <a href="{{ route('rooms.show', $room->id) }}" 
                       class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Back to Room
                    </a>
                </div>
            </div>

            <!-- Player list -->
            <div class="w-48 ml-6">
                <h2 class="text-lg font-bold mb-2">Players</h2>
                <ul>
                @foreach ($room->players as $player)
                    <li class="flex items-center space-x-2 mb-1">
                        <span class="inline-block w-4 h-4 rounded" style="background-color: {{ $player->pivot->color }}"></span>
                        <span>{{ $player->name }}</span>
                    </li>
                @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Room meta for live updates -->
    <div id="room-meta" data-room-id="{{ $room->id }}"></div>

    <script>
        const playerColor = "{{ $room->players->find(auth()->id())?->pivot->color ?? '#000000' }}";
        const rows = {{ $rows }};
        const cols = {{ $cols }};
        const mines = {{ $mines }};
        const initialBoard = @json(json_decode($board)); // array of { mine: bool, count: int }
        const savedFlags = @json($flags);     // map "r-c" => color (string) or absent
        const savedRevealed = @json($revealed); // map "r-c" => true
        const roomId = {{ $room->id }};
        const updateUrl = "{{ route('games.update') }}";

        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

        // DOM refs
        const gameContainer = document.getElementById('game');
        const mineCounter = document.getElementById('mineCounter');
        const statusMessage = document.getElementById('statusMessage');
        const restartBtn = document.getElementById('restartBtn');

        let board = []; // board[r][c] => { mine, count, row, col, element, revealed, flagged, flagColor }
        let flagsPlaced = 0;
        let gameOver = false;

        function initGame() {
            gameContainer.style.gridTemplateColumns = `repeat(${cols}, 3rem)`;
            gameContainer.innerHTML = '';
            board = [];
            flagsPlaced = 0;
            gameOver = false;
            statusMessage.textContent = '';

            // build board objects
            for (let r = 0; r < rows; r++) {
                board[r] = [];
                for (let c = 0; c < cols; c++) {
                    const sourceCell = initialBoard[r][c] ?? { mine: false, count: 0 };
                    board[r][c] = {
                        mine: sourceCell.mine,
                        count: sourceCell.count,
                        row: r,
                        col: c,
                        element: null,
                        revealed: false,
                        flagged: false,
                        flagColor: null
                    };
                }
            }

            // create DOM buttons then restore saved state
            for (let r = 0; r < rows; r++) {
                for (let c = 0; c < cols; c++) {
                    const btn = document.createElement('button');
                    btn.className = 'w-12 h-12 bg-gray-300 rounded flex items-center justify-center text-sm font-bold';
                    btn.dataset.row = r;
                    btn.dataset.col = c;

                    btn.addEventListener('click', () => reveal(r, c));
                    btn.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        toggleFlag(r, c);
                    });

                    gameContainer.appendChild(btn);
                    board[r][c].element = btn;

                    // restore flag if present in savedFlags (value is color string)
                    const key = `${r}-${c}`;
                    if (savedFlags && savedFlags[key]) {
                        board[r][c].flagged = true;
                        board[r][c].flagColor = savedFlags[key];
                        flagsPlaced++;
                        btn.textContent = '🚩';
                        btn.style.backgroundColor = board[r][c].flagColor;
                    }

                    // restore revealed if present
                    if (savedRevealed && savedRevealed[key]) {
                        revealCell(r, c, true); // true = restoring (don't broadcast)
                    }
                }
            }

            updateMineCounter();

            // If the game was already finished server-side (started=false) you might want to set gameOver true; 
            // that requires the controller to pass that info — optional.
        }

        function updateMineCounter() {
            mineCounter.textContent = `Mines: ${mines - flagsPlaced}`;
        }

        function toggleFlag(r, c) {
            if (gameOver) return;
            const cell = board[r][c];
            if (!cell || cell.revealed) return;

            cell.flagged = !cell.flagged;
            if (cell.flagged) {
                flagsPlaced++;
                cell.flagColor = playerColor;
                cell.element.textContent = '🚩';
                cell.element.style.backgroundColor = cell.flagColor;
            } else {
                flagsPlaced--;
                cell.flagColor = null;
                cell.element.textContent = '';
                cell.element.style.backgroundColor = ''; // reset (Tailwind gray will be applied by class)
            }

            updateMineCounter();

            // send flag toggle to server. server decides playerColor and persists it.
            axios.post(updateUrl, {
                roomId: roomId,
                row: r,
                col: c,
                action: 'flag',
                value: cell.flagged
            }).catch(err => console.error('flag update failed', err));
        }

        function reveal(r, c) {
            if (gameOver) return;
            const toReveal = floodRevealCollect(r, c);
            if (!toReveal.length) return;

            // reveal locally
            toReveal.forEach(({ row, col }) => revealCell(row, col));

            // if any mine in this reveal -> game over for everyone
            if (toReveal.some(x => board[x.row][x.col].mine)) {
                gameOver = true;
                statusMessage.textContent = 'Game Over!';
                // reveal all mines locally
                board.flat().forEach(cell => {
                    if (cell.mine && !cell.revealed) {
                        revealCell(cell.row, cell.col);
                    }
                    if (cell.element) cell.element.disabled = true;
                });

                // prepare mines array to send
                const minesToSend = board.flat()
                    .filter(c => c.mine)
                    .map(c => ({ row: c.row, col: c.col, mine: true }));

                axios.post(updateUrl, {
                    roomId: roomId,
                    action: 'reveal',
                    value: minesToSend,
                    gameOver: true
                }).catch(err => console.error('reveal (mines) update failed', err));

                return;
            }

            // safe reveal(s) -> send batch
            axios.post(updateUrl, {
                roomId: roomId,
                action: 'reveal',
                value: toReveal
            }).catch(err => console.error('reveal update failed', err));

            checkWin();
        }

        // collects cells to reveal with flood fill (no side effects)
        function floodRevealCollect(sr, sc) {
            const queue = [{ r: sr, c: sc }];
            const revealed = [];
            const visited = Array.from({ length: rows }, () => Array(cols).fill(false));

            while (queue.length) {
                const { r, c } = queue.shift();
                if (r < 0 || c < 0 || r >= rows || c >= cols) continue;
                const cell = board[r][c];
                if (!cell || visited[r][c] || cell.revealed || cell.flagged) continue;

                visited[r][c] = true;
                revealed.push({ row: r, col: c, mine: cell.mine, count: cell.count });

                // flood neighbors only if count is 0 and not a mine
                if (cell.count === 0 && !cell.mine) {
                    for (let dr = -1; dr <= 1; dr++) {
                        for (let dc = -1; dc <= 1; dc++) {
                            const nr = r + dr, nc = c + dc;
                            if (nr >= 0 && nc >= 0 && nr < rows && nc < cols && !visited[nr][nc]) {
                                queue.push({ r: nr, c: nc });
                            }
                        }
                    }
                }
            }

            return revealed;
        }

        function revealCell(r, c, restoring = false) {
            const cell = board[r][c];
            if (!cell || cell.revealed) return;
            cell.revealed = true;

            const btn = cell.element;
            if (cell.mine) {
                btn.textContent = '💣';
                btn.className = 'w-12 h-12 bg-red-500 rounded flex items-center justify-center text-sm font-bold text-white';
                if (!restoring) {
                    // optional: can set gameOver here if called directly, but main reveal() handles gameOver logic
                }
            } else {
                btn.textContent = cell.count > 0 ? cell.count : '';
                btn.className = 'w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-sm font-bold text-gray-800';
            }
        }

        function checkWin() {
            if (gameOver) return;
            const safeCells = board.flat().filter(c => !c.mine && c.revealed).length;
            if (safeCells === rows * cols - mines) {
                gameOver = true;
                statusMessage.textContent = 'You Win! 🎉';
                board.flat().forEach(c => {
                    if (c.mine) {
                        c.element.className = 'w-12 h-12 bg-green-500 rounded flex items-center justify-center text-sm font-bold text-white';
                        c.element.textContent = '💣';
                    }
                    if (c.element) c.element.disabled = true;
                });
            }
        }

        // Listen for TileUpdated broadcasts and apply them
        if (window.Echo && roomId) {
            window.Echo.channel(`room.${roomId}`)
                .listen('.TileUpdated', (e) => {
                    console.log('TileUpdated', e);

                    if (e.action === 'flag') {
                        const r = e.row, c = e.col;
                        const cell = board[r]?.[c];
                        if (!cell) return;
                        if (gameOver) return; // ignore flags after game end locally
                        // server sends value = true/false, and playerColor (string) for who flagged
                        cell.flagged = !!e.value;
                        if (cell.flagged) {
                            cell.flagColor = e.playerColor ?? '#000';
                            cell.element.textContent = '🚩';
                            cell.element.style.backgroundColor = cell.flagColor;
                        } else {
                            cell.flagColor = null;
                            cell.element.textContent = '';
                            cell.element.style.backgroundColor = '';
                        }

                        // update local flagsPlaced count (recount robustly)
                        flagsPlaced = board.flat().filter(x => x.flagged).length;
                        updateMineCounter();
                    }

                    if (e.action === 'reveal') {
                        // e.value is an array of {row,col,mine,count}
                        (e.value || []).forEach(({ row, col, mine, count }) => {
                            // apply reveal only if not already revealed
                            const cell = board[row]?.[col];
                            if (!cell || cell.revealed) return;
                            // ensure the cell has count/mine from payload if provided
                            if (typeof mine !== 'undefined') cell.mine = !!mine;
                            if (typeof count !== 'undefined') cell.count = count;
                            revealCell(row, col, true);
                        });

                        // if the broadcast said gameOver (someone hit a mine), lock UI
                        if (e.gameOver) {
                            gameOver = true;
                            statusMessage.textContent = 'Game Over!';
                            board.flat().forEach(c => { if (c.element) c.element.disabled = true; });
                        }
                    }
                });
        }

        // initialize
        initGame();

        // local restart only (does not sync to others)
        restartBtn.addEventListener('click', () => {
            initGame();
        });
        </script>

</x-app-layout>
