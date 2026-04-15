@php
use App\Enums\PauseResultStatus;
use App\Models\NonDB\PauseResult;
use Illuminate\Support\Collection;

/**
 * @var Collection<PauseResult> $report
 * @var int $seconds
 * @var int $totalSeconds
 * @var bool $allFailed
 */

// Pre-format the initial timer display so there's no raw-integer flash before JS runs.
$initH = intdiv($seconds, 3600);
$initM = intdiv($seconds % 3600, 60);
$initS = $seconds % 60;
$initTime = $initH > 0
    ? sprintf('%d:%02d:%02d', $initH, $initM, $initS)
    : sprintf('%02d:%02d', $initM, $initS);
@endphp
@extends('main')
@section('content')
<div class="min-h-screen flex flex-col items-center justify-center py-12 px-6"
     id="pausepi-app"
     data-seconds="{{ $seconds }}"
     data-total-seconds="{{ $totalSeconds }}"
     data-all-failed="{{ $allFailed ? '1' : '0' }}">

    {{-- ── App header ──────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 mb-12">
        <img src="{{ asset('images/pausepi.png') }}" alt="PausePi" class="h-8 w-auto">
        <span class="text-lg font-bold tracking-wide text-slate-100">PausePi</span>
    </div>

    @if (count($report) > 0)

        @unless ($allFailed)
            {{-- ── Doughnut countdown timer ─────────────────────────────── --}}
            {{--
                SVG internals: viewBox 240×240, centre (120,120), r=100
                Circumference = 2π × 100 ≈ 628.318
                Ring rotated −90° so it starts at 12 o'clock.
                stroke-dashoffset is driven by JS.
            --}}
            <div class="relative mb-8 w-52 h-52 sm:w-60 sm:h-60" id="timer-container">
                <svg viewBox="0 0 240 240" class="w-full h-full" aria-hidden="true">
                    {{-- Background track --}}
                    <circle
                        cx="120" cy="120" r="100"
                        fill="none"
                        stroke="#1e293b"
                        stroke-width="10"
                        transform="rotate(-90 120 120)"
                    />
                    {{-- Progress ring --}}
                    <circle
                        id="timer-ring"
                        cx="120" cy="120" r="100"
                        fill="none"
                        stroke="#34d399"
                        stroke-width="10"
                        stroke-linecap="round"
                        transform="rotate(-90 120 120)"
                    />
                </svg>
                {{-- Centred time overlay --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span id="time-display"
                          class="text-4xl font-mono font-bold text-slate-100 tabular-nums leading-none">
                        {{ $initTime }}
                    </span>
                    <span id="timer-label"
                          class="mt-2 text-xs font-medium uppercase tracking-widest text-slate-400">
                        remaining
                    </span>
                </div>
            </div>

            <p id="message" class="text-2xl font-semibold text-slate-100 mb-2 text-center">
                Hurry, do your thing!
            </p>
            <p id="sub-message" class="text-base text-slate-400 mb-10 text-center">
                Ad blocking resumes when the timer reaches zero.
            </p>

        @else
            {{-- ── All-failed error state ───────────────────────────────── --}}
            <div class="flex flex-col items-center mb-10">
                <div class="w-24 h-24 rounded-full flex items-center justify-center
                            bg-red-500/10 border-2 border-red-500/20 mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-10 h-10 text-red-400" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                                 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34
                                 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <p id="message" class="text-xl font-semibold text-red-400 mb-1 text-center">
                    Could not pause any ad blocker!
                </p>
                <p id="sub-message" class="text-sm text-slate-400 text-center max-w-xs">
                    Check that your Pi-holes are reachable and their hostname&nbsp;/&nbsp;credentials are correct.
                </p>
            </div>
        @endunless

        {{-- ── Per-Pi-hole status cards ─────────────────────────────────── --}}
        <div class="w-full max-w-sm space-y-3 mb-10">
            @foreach ($report as $result)
                @php $ok = $result->status === PauseResultStatus::SUCCESS; @endphp
                <div class="flex items-center justify-between
                            rounded-xl px-5 py-4
                            bg-slate-800/60 border border-slate-700/40">
                    <div class="flex items-center gap-3 min-w-0">
                        {{-- Status dot --}}
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 status-dot
                                     {{ $ok ? 'bg-emerald-400' : 'bg-red-400' }}">
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-200 truncate">
                                {{ $result->piholeBox->name }}
                            </p>
                            <p class="text-xs text-slate-500 font-mono truncate">
                                {{ $result->piholeBox->hostname }}
                            </p>
                        </div>
                    </div>
                    <span class="ml-4 flex-shrink-0 text-xs font-semibold status-badge
                                 {{ $ok ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $ok ? 'Paused' : 'Failed' }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- ── Pause Again button ───────────────────────────────────────── --}}
        <button id="again" type="button" disabled>
            Pause Again
        </button>

    @else
        {{-- ── No Pi-holes configured ───────────────────────────────────── --}}
        <div class="flex flex-col items-center text-center">
            <div class="w-24 h-24 rounded-full flex items-center justify-center
                        bg-slate-800 border-2 border-slate-700 mb-6">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-10 h-10 text-slate-500" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="2" width="20" height="15" rx="2" ry="2"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2 13h20M12 17v4M8 21h8"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-slate-200 mb-2">No ad-blockers configured</h2>
            <p class="text-sm text-slate-400 max-w-xs">
                Add a Pi-hole via the manager to get started.
                <a href="https://github.com/tchubaba/pausepi?tab=readme-ov-file#configuration"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="text-emerald-400 hover:text-emerald-300 underline underline-offset-2 ml-1">
                    View setup guide&nbsp;→
                </a>
            </p>
        </div>
    @endif

</div>
@endsection

@push('scripts')
    @vite('resources/js/home.js')
@endpush
