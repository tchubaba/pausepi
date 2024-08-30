@php use App\Enums\PauseResultStatus; @endphp
@extends('main')
@section('content')
    @if (count($report) > 0)
        <h1 class="text-2xl" id="message">Hurry, do your thing! Ad blocking will resume in <span id="time">{{ $seconds }}</span> seconds
            :)</h1>
        <ul>
            @foreach ($report as $result)
                <li>{{ $result->piholeBox->name }} Pi-hole ({{ $result->piholeBox->hostname }}) status: <span class="font-bold {{ $result->status === PauseResultStatus::SUCCESS ? 'text-green-600' : 'text-red-600' }}">{{ $result->status === PauseResultStatus::SUCCESS ? 'Ad Blocking Paused' : 'Could not pause! (Ad blocking may be active...)' }}</span>
                </li>
            @endforeach
        </ul>
        <button id="again" type="button" disabled>Pause Again</button>
    @else
        <h1 class="text-red-600 text-2xl my-2">Could not find any ad-blockers to pause :(</h1>
            <p class="my-1">Please add a Pi-hole ad-blocker via the manager. Run the "php artisan pausepi:manager" command at the root of the project.</p>
    @endif
@endsection
@section('javascript')
    $(document).ready(function () {
        var seconds = {{ $seconds }},
        display = document.querySelector('#time'),
        allFailed = {{ $allFailed ? 'true' : 'false' }},
        againButton = $('#again');

        againButton.click(function () {
            location.reload();
        });

        if (allFailed) {
            againButton.prop('disabled', false);
            againButton.text('Try again');
            $('#message').text('OH NO! We couldn\'t disable any of the ad blockers! :( Please ensure they are running and their information (hostname, API Token) are configured correctly.');
        } else {
            againButton.prop('disabled', true);
            startTimer(seconds, display);
        }
    });

    function startTimer(duration, display) {
        var timer = duration, minutes, seconds;
        var intervalId = setInterval(function () {
            minutes = parseInt(timer / 60, 10)
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            // seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = timer;

            if (--timer < 0) {
                clearInterval(intervalId);
                $('.status').text('Ad Blocking Active').css('color', 'red');
                $('#again').prop('disabled', false);
                $('#message').text('Ad blocking has resumed. Please pause again if you need more time.')
            }
        }, 1000);
    }
@endsection
