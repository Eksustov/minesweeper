import axios from "axios";

/**
 * Initialize Minesweeper
 * @param {Object} config - Game configuration
 */
export default function initMinesweeper(config) {
    // Extract config values
    let {
        userId,            // 👈 make sure you pass this from blade: userId: {{ auth()->id() }}
        roomId,
        playerColor,
        rows,
        cols,
        mines,
        initialBoard,
        savedFlags,
        savedRevealed,
        updateUrl
    } = config;

    // DOM references
    const gameContainer = document.getElementById("board");
    const mineCounter = document.getElementById("mineCounter");
    const statusMessage = document.getElementById("statusMessage");
    const restartBtn = document.getElementById("restartBtn");

    // Game state
    let board = [];
    let flagsPlaced = 0;
    let gameOver = false;
    let isAllowed = true; // 👈 gets flipped if you’re kicked

    axios.defaults.headers.common["X-CSRF-TOKEN"] =
        document.querySelector('meta[name="csrf-token"]').content;
    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

    // 🔒 Redirect on unauthorized/forbidden (kicked or not in room)
    axios.interceptors.response.use(
        (r) => r,
        (err) => {
            const status = err?.response?.status;
            if (status === 401 || status === 403) {
                // block UI, show message, and bounce home
                try {
                    isAllowed = false;
                    disableBoard("You were removed from the room.");
                } finally {
                    window.location.href = "/";
                }
                return;
            }
            return Promise.reject(err);
        }
    );

    // ---- Local cooldown config ----
    const CELL_COOLDOWN_MS = 800;         // per-cell: 1 action per 0.8s
    const BURST_WINDOW_MS  = 1000;        // rolling window size
    const BURST_MAX        = 4;           // max actions in the window

    // Track last action time per cell and per user
    const lastCellActionAt = new Map();   // key: "r-c" -> timestamp
    const recentActions = [];             // queue of timestamps

    function isRateLimitedLocal(r, c) {
        const now = performance.now();
        const key = `${r}-${c}`;

        // Per-cell cooldown
        const last = lastCellActionAt.get(key) || 0;
        if (now - last < CELL_COOLDOWN_MS) return { ok: false, reason: 'cell' };

        // Global burst guard
        while (recentActions.length && now - recentActions[0] > BURST_WINDOW_MS) {
            recentActions.shift();
        }
        if (recentActions.length >= BURST_MAX) return { ok: false, reason: 'burst' };

        return { ok: true };
    }

    function markAction(r, c) {
        const now = performance.now();
        lastCellActionAt.set(`${r}-${c}`, now);
        recentActions.push(now);
    }

    function blip(msg) {
        if (!statusMessage) return;
        const old = statusMessage.textContent;
        const oldColor = statusMessage.style.color;
        statusMessage.textContent = msg;
        statusMessage.style.color = '#374151'; // gray-700
        clearTimeout(statusMessage._t);
        statusMessage._t = setTimeout(() => {
            statusMessage.textContent = (old && (old.includes('Win') || old.includes('Over'))) ? old : '';
            statusMessage.style.color = oldColor || '';
        }, 800);
    }

    function disableBoard(message) {
        try {
            if (message && statusMessage) statusMessage.textContent = message;
            const buttons = gameContainer?.querySelectorAll("button");
            buttons?.forEach((b) => (b.disabled = true));
        } catch {}
    }

    /**
     * Initialize/reset the game board
     */
    function initGame() {
        // Setup grid
        gameContainer.style.display = "grid";
        gameContainer.style.gridTemplateColumns = `repeat(${cols}, 3rem)`;
        gameContainer.style.gridTemplateRows = `repeat(${rows}, 3rem)`;
        gameContainer.innerHTML = "";
        board = [];
        flagsPlaced = 0;
        gameOver = false;
        if (isAllowed && statusMessage) statusMessage.textContent = "";

        // ✅ 1. Build board structure first from initialBoard
        for (let r = 0; r < rows; r++) {
            board[r] = [];
            for (let c = 0; c < cols; c++) {
                const sourceCell =
                    initialBoard?.[r]?.[c] || { mine: false, count: 0, revealed: false };
                board[r][c] = {
                    row: r,
                    col: c,
                    mine: !!sourceCell.mine,
                    count: sourceCell.count ?? 0,
                    revealed: !!sourceCell.revealed,
                    flagged: !!sourceCell.flagged,
                    flagColor: sourceCell.flagColor || null,
                    element: null,
                };
            }
        }

        // ✅ 2. Now build buttons and attach them to board[r][c]
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const btn = document.createElement("button");
                btn.className = "ms-cell";
                btn.dataset.row = r;
                btn.dataset.col = c;

                btn.addEventListener("click", () => {
                    if (!isAllowed) return;
                    reveal(r, c);
                });
                btn.addEventListener("contextmenu", (e) => {
                    if (!isAllowed) return;
                    e.preventDefault();
                    toggleFlag(r, c);
                });

                gameContainer.appendChild(btn);
                board[r][c].element = btn;

                const key = `${r}-${c}`;

                // Restore flags
                if (board[r][c].flagged) {
                    flagsPlaced++;
                    btn.textContent = "🚩";
                    if (board[r][c].flagColor)
                        btn.style.backgroundColor = board[r][c].flagColor;
                } else if (savedFlags && savedFlags[key]) {
                    board[r][c].flagged = true;
                    board[r][c].flagColor = savedFlags[key];
                    flagsPlaced++;
                    btn.textContent = "🚩";
                    btn.style.backgroundColor = board[r][c].flagColor;
                }

                // Restore revealed
                if (!board[r][c].flagged) {
                    if (board[r][c].revealed) {
                        revealCell(r, c, true);
                    } else if (savedRevealed && savedRevealed[key]) {
                        board[r][c].revealed = true;
                        revealCell(r, c, true);
                    }
                }
            }
        }

        updateMineCounter();
    }

    function updateMineCounter() {
        mineCounter.textContent = `Mines: ${mines - flagsPlaced}`;
    }

    function toggleFlag(r, c) {
        if (!isAllowed || gameOver) return;
        const cell = board[r][c];
        if (!cell || cell.revealed) return;

        const gate = isRateLimitedLocal(r, c);
        if (!gate.ok) {
            blip(gate.reason === 'cell' ? 'Hold up…' : 'Too fast…');
            return;
        }
        markAction(r, c);

        cell.flagged = !cell.flagged;
        if (cell.flagged) {
            flagsPlaced++;
            cell.flagColor = playerColor;
            cell.element.textContent = "🚩";
            cell.element.style.backgroundColor = cell.flagColor;
        } else {
            flagsPlaced--;
            cell.flagColor = null;
            cell.element.textContent = "";
            cell.element.style.backgroundColor = "";
        }

        updateMineCounter();

        axios.post(updateUrl, {
            roomId: roomId,
            row: r,
            col: c,
            action: "flag",
            value: cell.flagged
        }).catch(err => console.error("flag update failed", err));
    }

    function reveal(r, c) {
        if (!isAllowed || gameOver) return;
        const cell = board[r]?.[c];
        if (!cell) return;

        const gate = isRateLimitedLocal(r, c);
        if (!gate.ok) {
            blip(gate.reason === 'cell' ? 'Hold up…' : 'Too fast…');
            return;
        }
        markAction(r, c);

        // ✅ local hard guards
        if (cell.flagged) return;   // <- don't reveal flagged
        if (cell.revealed) return;  // <- already revealed, skip

        const toReveal = floodRevealCollect(r, c);
        if (!toReveal.length) return;

        toReveal.forEach(({ row, col }) => revealCell(row, col));

        if (toReveal.some(x => board[x.row][x.col].mine)) {
            gameOver = true;
            statusMessage.textContent = "Game Over!";
            board.flat().forEach(cell => {
                if (cell.mine && !cell.revealed) revealCell(cell.row, cell.col);
                if (cell.element) cell.element.disabled = true;
            });
        }

        const firstTile = toReveal[0];
        axios.post(updateUrl, {
            roomId: roomId,
            row: firstTile.row,
            col: firstTile.col,
            action: "reveal"
        }).catch(err => console.error("reveal update failed", err));

        checkWin();
    }

    function floodRevealCollect(sr, sc) {
        const queue = [{ r: sr, c: sc }];
        const revealed = [];
        const visited = Array.from({ length: rows }, () =>
            Array(cols).fill(false)
        );

        while (queue.length) {
            const { r, c } = queue.shift();
            if (r < 0 || c < 0 || r >= rows || c >= cols) continue;
            const cell = board[r][c];
            if (!cell || visited[r][c] || cell.revealed || cell.flagged) continue;

            visited[r][c] = true;
            revealed.push({ row: r, col: c, mine: cell.mine, count: cell.count });

            if (cell.count === 0 && !cell.mine) {
                for (let dr = -1; dr <= 1; dr++) {
                    for (let dc = -1; dc <= 1; dc++) {
                        const nr = r + dr, nc = c + dc;
                        if (
                            nr >= 0 &&
                            nc >= 0 &&
                            nr < rows &&
                            nc < cols &&
                            !visited[nr][nc]
                        ) {
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
        if (!cell || (cell.revealed && !restoring)) return;
        cell.revealed = true;

        const btn = cell.element;
        if (cell.mine) {
            btn.textContent = "💣";
            btn.className = "ms-cell ms-cell-mine";
        } else {
            btn.textContent = cell.count > 0 ? cell.count : "";
            btn.className = "ms-cell ms-cell-revealed";
        }
    }

    function checkWin() {
        if (gameOver) return;
        const safeCells = board.flat().filter(c => !c.mine && c.revealed).length;
        if (safeCells === rows * cols - mines) {
            gameOver = true;
            statusMessage.textContent = "You Win! 🎉";
            board.flat().forEach(c => {
                if (c.mine) {
                    c.element.className =
                        "w-12 h-12 bg-green-500 rounded flex items-center justify-center text-sm font-bold text-white";
                    c.element.textContent = "💣";
                }
                if (c.element) c.element.disabled = true;
            });
        }
    }

    /**
     * Multiplayer updates with Echo
     */
    function setupEcho() {
        if (!window.Echo || !roomId) return;

        const channel = window.Echo.channel(`room.${roomId}`);
        channel.listen(".TileUpdated", (e) => {
            if (!isAllowed) return;

            if (e.action === "flag") {
                const cell = board[e.row]?.[e.col];
                if (!cell) return;
                cell.flagged = !!e.value;
                if (cell.flagged) {
                    cell.flagColor = e.playerColor ?? "#000";
                    cell.element.textContent = "🚩";
                    cell.element.style.backgroundColor = cell.flagColor;
                } else {
                    cell.flagColor = null;
                    cell.element.textContent = "";
                    cell.element.style.backgroundColor = "";
                }
                flagsPlaced = board.flat().filter(x => x.flagged).length;
                updateMineCounter();
                return;
            }

            if (e.action === "reveal") {
                // Reveal cells from the server
                (e.value || []).forEach(({ row, col, mine, count }) => {
                    const cell = board[row]?.[col];
                    if (!cell) return;

                    // If this is a final (gameOver) frame and locally flagged, clear visuals
                    if (e.gameOver && cell.flagged) {
                        cell.flagged = false;
                        cell.flagColor = null;
                        if (cell.element) {
                            cell.element.textContent = "";
                            cell.element.style.backgroundColor = "";
                            cell.element.style.boxShadow = "";
                        }
                        flagsPlaced = Math.max(0, flagsPlaced - 1);
                    }

                    if (typeof mine !== "undefined")  cell.mine  = !!mine;
                    if (typeof count !== "undefined") cell.count = count;

                    // Respect local flags only for non-final updates
                    if (!e.gameOver && (cell.revealed || cell.flagged)) return;

                    revealCell(row, col, true);
                });

                if (e.gameOver) {
                    // Determine outcome: loss if any revealed cell is a mine, else win
                    const anyMine = Array.isArray(e.value) && e.value.some(v => v && v.mine);
                    gameOver = true;

                    if (anyMine) {
                        statusMessage.textContent = "Game Over!";
                    } else {
                        statusMessage.textContent = "You Win! 🎉";
                        // make mines look 'safe/celebratory' if you like
                        board.flat().forEach(c => {
                            if (c.mine && c.element) {
                                c.element.className =
                                    "w-12 h-12 bg-green-500 rounded flex items-center justify-center text-sm font-bold text-white";
                                c.element.textContent = "💣";
                            }
                        });
                    }

                    updateMineCounter();
                    board.flat().forEach(c => { if (c.element) c.element.disabled = true; });
                }
            }
        });

        channel.listen(".GameStarted", (e) => {
            if (!isAllowed) return;
            initialBoard = Array.isArray(e.board) ? e.board : JSON.parse(e.board);
            rows = e.rows;
            cols = e.cols;
            mines = e.mines;

            savedFlags = {};
            savedRevealed = {};
            gameOver = false;

            initGame();
        });

        // 🚨 If *you* get kicked while in-game, disable and redirect
        channel.listen(".PlayerKicked", (e) => {
            if (Number(e?.playerId) === Number(userId)) {
                isAllowed = false;
                disableBoard("You were removed from the room.");
                setTimeout(() => {
                    window.location.href = "/";
                }, 200);
            }
        });
    }

    /**
     * Restart handler
     */
    function setupRestartButton() {
        if (!restartBtn) return; // ✅ Skip if player is not the creator

        restartBtn.addEventListener("click", () => {
            if (!isAllowed) return;
            axios.post(config.restartUrl)
                .then(res => {
                    if (res.data?.status !== "ok")
                        throw new Error(res.data?.message || "Failed to restart");

                    if (res.data.board) {
                        initialBoard = res.data.board;
                        rows = res.data.rows ?? rows;
                        cols = res.data.cols ?? cols;
                        mines = res.data.mines ?? mines;

                        savedFlags = {};
                        savedRevealed = {};

                        initGame();
                    }
                })
                .catch(err => {
                    console.error("restart failed", err);
                    alert(err.response?.data?.message || err.message || "Failed to restart game");
                });
        });
    }

    // Boot game
    initGame();
    setupEcho();
    setupRestartButton();
}

if (typeof window !== "undefined") {
    window.addEventListener("DOMContentLoaded", () => {
        if (window.config) {
            initMinesweeper(window.config);
        }
    });
}
