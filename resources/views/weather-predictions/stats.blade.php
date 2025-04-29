@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Weather Prediction Accuracy Stats</h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Overall Statistics</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Average High Temperature Difference</h6>
                            <p class="card-text h3">{{ number_format($stats->avg('avg_high_diff'), 1) }}°F</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Average Low Temperature Difference</h6>
                            <p class="card-text h3">{{ number_format($stats->avg('avg_low_diff'), 1) }}°F</p>
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
                    <th>City</th>
                    <th>Avg High Diff</th>
                    <th>Avg Low Diff</th>
                    <th>Total Predictions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats as $stat)
                <tr>
                    <td>{{ $stat->city }}</td>
                    <td>{{ number_format($stat->avg_high_diff, 1) }}°F</td>
                    <td>{{ number_format($stat->avg_low_diff, 1) }}°F</td>
                    <td>{{ $stat->total_predictions }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('weather-predictions.index') }}" class="btn btn-primary">Back to Predictions</a>
</div>
@endsection 