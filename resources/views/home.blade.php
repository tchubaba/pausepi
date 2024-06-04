@extends('main')
@section('content')
    @if (count($report) > 0)
        <h1 id="message">Hurry, do your thing! Ad blocking will resume in <span id="time">{{ $seconds }}</span> seconds :)</h1>
        <ul>
            @foreach ($report as $name => $result)
                <li>{{ $name }} Pi-hole ({{ $result['ip'] }}) status: <span class="status" style="{{ $result['result'] ? '' : 'color: red;' }}">{{ $result['result'] ? 'Ad Blocking Paused' : 'Could not disable! (Blocking may be active...)' }}</span></li>
            @endforeach
        </ul>
        <button id="again" type="button" disabled>Pause Again</button>
    @else
        <h1 style="color:red">Could not find any ad-blockers to pause :(</h1>
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
            $('#message').text('OH NO! We couldn\'t disable any of the ad blockers! Please try again or tell Dad about it. Sorry :(');
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
