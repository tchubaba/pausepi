// ── Bootstrap ────────────────────────────────────────────────────────────────
// PHP values are passed via data-* attributes on #pausepi-app so this file
// stays free of Blade syntax and can be compiled as a normal Vite asset.

const appEl = document.getElementById('pausepi-app');

// If the app container isn't present (e.g. no Pi-holes configured), there is
// nothing to initialise.
if (appEl) {
    const REMAINING     = parseInt(appEl.dataset.seconds, 10);
    const TOTAL_SECONDS = parseInt(appEl.dataset.totalSeconds, 10);
    const ALL_FAILED    = appEl.dataset.allFailed === '1';

    // ── Constants ────────────────────────────────────────────────────────
    const CIRCUMFERENCE = 2 * Math.PI * 100; // r = 100, matches SVG

    // Ring colour stops as [r, g, b].
    // > 50% remaining : green
    // 50% → 25%       : green → orange  (lerp)
    // < 25% remaining : orange → red    (lerp)
    const COLOR_GREEN  = [52,  211, 153]; // #34d399  emerald-400
    const COLOR_ORANGE = [249, 115, 22];  // #f97316  orange-500
    const COLOR_RED    = [248, 113, 113]; // #f87171  red-400

    // ── Element refs ─────────────────────────────────────────────────────
    const ring        = document.getElementById('timer-ring');
    const timeDisplay = document.getElementById('time-display');
    const timerLabel  = document.getElementById('timer-label');
    const messageEl   = document.getElementById('message');
    const subMsgEl    = document.getElementById('sub-message');
    const againBtn    = document.getElementById('again');

    // ── Colour helpers ───────────────────────────────────────────────────

    /** Linear interpolation between two RGB triples. t: 0 = c1, 1 = c2. */
    function lerpColor(c1, c2, t) {
        return [
            Math.round(c1[0] + (c2[0] - c1[0]) * t),
            Math.round(c1[1] + (c2[1] - c1[1]) * t),
            Math.round(c1[2] + (c2[2] - c1[2]) * t),
        ];
    }

    /**
     * Returns the [r, g, b] ring colour for the given progress fraction
     * (1.0 = full / just started, 0.0 = empty / expired).
     */
    function getRingColor(fraction) {
        if (fraction > 0.5) {
            return COLOR_GREEN;
        } else if (fraction > 0.25) {
            // t goes 0 → 1 as fraction goes 0.5 → 0.25
            const t = (0.5 - fraction) / 0.25;
            return lerpColor(COLOR_GREEN, COLOR_ORANGE, t);
        } else {
            // t goes 0 → 1 as fraction goes 0.25 → 0
            const t = (0.25 - fraction) / 0.25;
            return lerpColor(COLOR_ORANGE, COLOR_RED, t);
        }
    }

    /** Applies stroke colour and matching glow to the ring element. */
    function applyRingColor(fraction) {
        const [r, g, b] = getRingColor(fraction);
        ring.style.stroke = `rgb(${r} ${g} ${b})`;
        ring.style.filter = `drop-shadow(0 0 8px rgba(${r}, ${g}, ${b}, 0.45))`;
    }

    // ── Timer helpers ────────────────────────────────────────────────────

    function formatTime(secs) {
        if (secs <= 0) return '00:00';
        const h   = Math.floor(secs / 3600);
        const m   = Math.floor((secs % 3600) / 60);
        const s   = Math.floor(secs % 60);
        const pad = n => String(n).padStart(2, '0');
        return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
    }

    function setRingOffset(remaining, total) {
        const fraction = total > 0 ? remaining / total : 0;
        ring.style.strokeDashoffset = CIRCUMFERENCE * (1 - fraction);
    }

    // ── Timer expired ────────────────────────────────────────────────────
    function onTimerComplete() {
        const [r, g, b] = COLOR_RED;
        ring.style.stroke           = `rgb(${r} ${g} ${b})`;
        ring.style.filter           = `drop-shadow(0 0 8px rgba(${r}, ${g}, ${b}, 0.45))`;
        ring.style.strokeDashoffset = CIRCUMFERENCE;

        if (timeDisplay) timeDisplay.textContent = '00:00';
        if (timerLabel) {
            timerLabel.textContent = 'resumed';
            timerLabel.style.color = `rgb(${r} ${g} ${b})`;
        }

        if (messageEl) messageEl.textContent = 'Ad blocking has resumed.';
        if (subMsgEl)  subMsgEl.textContent  = 'Please pause again if you need more time.';

        document.querySelectorAll('.status-dot').forEach(el => {
            el.style.backgroundColor = `rgb(${r} ${g} ${b})`;
        });
        document.querySelectorAll('.status-badge').forEach(el => {
            el.textContent = 'Active';
            el.style.color = `rgb(${r} ${g} ${b})`;
        });

        if (againBtn) againBtn.disabled = false;
    }

    // ── Start countdown ──────────────────────────────────────────────────
    function startTimer(remaining, total) {
        const initialFraction = total > 0 ? remaining / total : 0;

        // Set initial position and colour without transitions (avoids a
        // sweep-in animation and incorrect colour flash on load).
        ring.style.transition      = 'none';
        ring.style.strokeDasharray = CIRCUMFERENCE;
        setRingOffset(remaining, total);
        applyRingColor(initialFraction);
        if (timeDisplay) timeDisplay.textContent = formatTime(remaining);

        // Re-enable transitions after the first paint so subsequent tick
        // updates animate smoothly.
        requestAnimationFrame(() => requestAnimationFrame(() => {
            ring.style.transition =
                'stroke-dashoffset 1s linear, stroke 0.9s ease, filter 0.9s ease';
        }));

        let current = remaining;

        const intervalId = setInterval(() => {
            current--;

            if (current < 0) {
                clearInterval(intervalId);
                onTimerComplete();
                return;
            }

            const fraction = total > 0 ? current / total : 0;
            setRingOffset(current, total);
            applyRingColor(fraction);
            if (timeDisplay) timeDisplay.textContent = formatTime(current);
        }, 1000);
    }

    // ── Boot ─────────────────────────────────────────────────────────────

    // Belt-and-suspenders: ensure button is disabled on first paint regardless
    // of the HTML attribute state.
    if (againBtn) {
        againBtn.disabled = true;
        againBtn.addEventListener('click', () => location.reload());
    }

    if (ALL_FAILED) {
        // Defer enabling so the disabled state renders before transitioning
        // to the retry state (CSS transition animates the colour change).
        setTimeout(() => {
            if (againBtn) {
                againBtn.disabled    = false;
                againBtn.textContent = 'Try Again';
            }
        }, 500);
    } else if (ring && REMAINING > 0) {
        startTimer(REMAINING, TOTAL_SECONDS);
    } else if (REMAINING <= 0) {
        // Cache hit where the remaining time already elapsed — show expired state.
        onTimerComplete();
    }
}
