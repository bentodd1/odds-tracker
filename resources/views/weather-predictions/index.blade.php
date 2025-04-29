@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Weather Predictions</h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Prediction Accuracy Stats</h5>
            <a href="{{ route('weather-predictions.stats') }}" class="btn btn-primary">View Detailed Stats</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>City</th>
                    <th>Prediction Date</th>
                    <th>Target Date</th>
                    <th>Predicted High</th>
                    <th>Predicted Low</th>
                    <th>Actual High</th>
                    <th>Actual Low</th>
                    <th>High Diff</th>
                    <th>Low Diff</th>
                </tr>
            </thead>
            <tbody>
                @foreach($predictions as $prediction)
                <tr>
                    <td>
                        <a href="{{ route('weather-predictions.show', $prediction->city) }}">
                            {{ $prediction->city }}
                        </a>
                    </td>
                    <td>{{ $prediction->prediction_date->format('Y-m-d') }}</td>
                    <td>{{ $prediction->target_date->format('Y-m-d') }}</td>
                    <td>{{ $prediction->predicted_high }}°F</td>
                    <td>{{ $prediction->predicted_low }}°F</td>
                    <td>{{ $prediction->actual_high ? $prediction->actual_high . '°F' : '-' }}</td>
                    <td>{{ $prediction->actual_low ? $prediction->actual_low . '°F' : '-' }}</td>
                    <td>{{ $prediction->high_difference ? $prediction->high_difference . '°F' : '-' }}</td>
                    <td>{{ $prediction->low_difference ? $prediction->low_difference . '°F' : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $predictions->links() }}
</div>
@endsection 