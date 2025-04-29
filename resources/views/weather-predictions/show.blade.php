@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">{{ $city }} Weather Predictions</h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">City Statistics</h5>
            @php
                $cityStats = $predictions->whereNotNull('actual_high')
                    ->reduce(function ($carry, $prediction) {
                        $carry['high_diff'] += abs($prediction->high_difference);
                        $carry['low_diff'] += abs($prediction->low_difference);
                        $carry['count']++;
                        return $carry;
                    }, ['high_diff' => 0, 'low_diff' => 0, 'count' => 0]);
            @endphp

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Average High Temperature Difference</h6>
                            <p class="card-text h3">
                                {{ $cityStats['count'] > 0 ? number_format($cityStats['high_diff'] / $cityStats['count'], 1) : 0 }}°F
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Average Low Temperature Difference</h6>
                            <p class="card-text h3">
                                {{ $cityStats['count'] > 0 ? number_format($cityStats['low_diff'] / $cityStats['count'], 1) : 0 }}°F
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
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

    <a href="{{ route('weather-predictions.index') }}" class="btn btn-primary">Back to All Cities</a>
</div>
@endsection 