<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
  <svg
    viewBox="0 0 24 24"
    class="h-5 w-5 fill-current text-white/90"
    role="img"
    aria-label="Minesweeper icon"
  >
    <style>
      /* Namespaced to avoid collisions */
      .mi-state { opacity: 0; }
      .mi-cell  { animation: mi-cycle 9s infinite; animation-delay: 0s; }
      .mi-flag  { animation: mi-cycle 9s infinite; animation-delay: 3s; }
      .mi-mine  { animation: mi-cycle 9s infinite; animation-delay: 6s; }
      /* Show each state for 1/3 of the time, then hide */
      @keyframes mi-cycle {
        0%   { opacity: 1; }
        33.333% { opacity: 1; }
        33.334% { opacity: 0; }
        100% { opacity: 0; }
      }
      /* Respect reduced motion */
      @media (prefers-reduced-motion: reduce) {
        .mi-state { animation: none !important; }
        .mi-cell  { opacity: 1 !important; } /* default static state */
      }
      /* Minor stroke styling */
      .mi-stroke { stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; }
    </style>

    <!-- State 1: Cell (unrevealed tile) -->
    <g class="mi-state mi-cell">
      <rect x="4" y="4" width="16" height="16" rx="3" fill="none" class="mi-stroke" stroke-width="2"/>
      <!-- subtle bevel lines -->
      <path d="M5 9h14M9 5v14" class="mi-stroke" stroke-width="1" opacity=".25"/>
    </g>

    <!-- State 2: Flag -->
    <g class="mi-state mi-flag">
      <line x1="7" y1="5" x2="7" y2="19" class="mi-stroke" stroke-width="2"/>
      <path d="M8 6l8 2-8 3z" />
      <circle cx="7" cy="19" r="1.6" />
    </g>

    <!-- State 3: Mine -->
    <g class="mi-state mi-mine">
      <circle cx="12" cy="12" r="3.2" />
      <!-- spikes -->
      <g class="mi-stroke" stroke-width="2">
        <line x1="12" y1="3.5" x2="12" y2="7.5"/>
        <line x1="12" y1="16.5" x2="12" y2="20.5"/>
        <line x1="3.5" y1="12" x2="7.5" y2="12"/>
        <line x1="16.5" y1="12" x2="20.5" y2="12"/>
        <line x1="6.2" y1="6.2" x2="8.9" y2="8.9"/>
        <line x1="15.1" y1="15.1" x2="17.8" y2="17.8"/>
        <line x1="6.2" y1="17.8" x2="8.9" y2="15.1"/>
        <line x1="15.1" y1="8.9" x2="17.8" y2="6.2"/>
      </g>
      <!-- fuse sparkle -->
      <circle cx="16.8" cy="7.2" r="0.9" />
    </g>
  </svg>
</span>
