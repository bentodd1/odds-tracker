@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="mb-4">
        <a href="{{ route('nws.analysis') }}" class="text-blue-600 hover:text-blue-800">← Back to NWS Analysis</a>
    </div>
    
    <h1 class="text-2xl font-bold mb-4">
        Examples: {{ $city === 'all' ? 'All Cities' : $city }} - {{ $difference > 0 ? '+' : '' }}{{ $difference }}°F Difference
    </h1>
    
    <p class="mb-4 text-gray-600">
        Showing {{ $examples->count() }} examples where the NWS prediction was {{ $difference > 0 ? 'higher' : 'lower' }} than the actual temperature by {{ abs($difference) }}°F{{ $city === 'all' ? ' across all cities' : ' in ' . $city }}.
    </p>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    @if($city === 'all')
                        <th class="p-2 border">City</th>
                    @endif
                    <th class="p-2 border">Target Date</th>
                    <th class="p-2 border">Prediction Date</th>
                    <th class="p-2 border">Forecast Hour</th>
                    <th class="p-2 border">Predicted High</th>
                    <th class="p-2 border">Actual High</th>
                    <th class="p-2 border">Difference</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examples as $example)
                    <tr class="border-b">
                        @if($city === 'all')
                            <td class="p-2 border">{{ $example->city }}</td>
                        @endif
                        <td class="p-2 border">{{ $example->target_date->format('M j, Y') }}</td>
                        <td class="p-2 border">{{ $example->prediction_date->format('M j, Y') }}</td>
                        <td class="p-2 border text-center">{{ sprintf('%02d:00', $example->forecast_hour) }}</td>
                        <td class="p-2 border text-center">{{ $example->predicted_high }}°F</td>
                        <td class="p-2 border text-center">{{ $example->actual_high }}°F</td>
                        <td class="p-2 border text-center {{ $example->high_difference > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $example->high_difference > 0 ? '+' : '' }}{{ $example->high_difference }}°F
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if($examples->isEmpty())
        <div class="text-center py-8 text-gray-500">
            No examples found for this combination.
        </div>
    @endif
</div>
@endsection 