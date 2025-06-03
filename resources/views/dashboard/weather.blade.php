@extends('layouts.app')

@section('title', 'Weather Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Weather Dashboard</h1>
    <form method="GET" class="mb-4">
        <label for="date" class="mr-2 font-semibold">Select Date:</label>
        <select name="date" id="date" onchange="this.form.submit()" class="border rounded px-2 py-1">
            <option value="{{ $today }}" {{ request('date', $today) == $today ? 'selected' : '' }}>Today ({{ $today }})</option>
            <option value="{{ $tomorrow }}" {{ request('date', $today) == $tomorrow ? 'selected' : '' }}>Tomorrow ({{ $tomorrow }})</option>
        </select>
    </form>
    <table class="min-w-full bg-white rounded shadow overflow-x-auto">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2">City</th>
                <th class="p-2">Timezone</th>
                <th class="p-2">Hours to 3PM</th>
                <th class="p-2">AccuWeather High</th>
                <th class="p-2">NWS High</th>
                <th class="p-2">Kalshi Market</th>
                <th class="p-2">Yes %</th>
                <th class="p-2">No %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $row)
                @php $first = true; @endphp
                @foreach($row['kalshi_markets'] as $market)
                    <tr class="border-b">
                        @if($first)
                            <td class="p-2 font-semibold" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">{{ $row['city'] }}</td>
                            <td class="p-2 text-xs" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">{{ $row['timezone'] }}</td>
                            <td class="p-2" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">{{ number_format($row['hours_to_3pm'], 1) }}</td>
                            <td class="p-2" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">
                                {{ $row['accuweather'] ? $row['accuweather']->predicted_high : 'N/A' }}
                            </td>
                            <td class="p-2" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">
                                {{ $row['nws'] ? $row['nws']->predicted_high : 'N/A' }}
                            </td>
                        @endif
                        <td class="p-2 max-w-xs whitespace-normal break-words">{{ $market->title }}</td>
                        <td class="p-2">
                            @if($market->filtered_state)
                                {{ $market->filtered_state->yes_ask !== null ? number_format($market->filtered_state->yes_ask * 100, 1) . '%' : 'N/A' }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="p-2">
                            @if($market->filtered_state)
                                {{ $market->filtered_state->no_ask !== null ? number_format($market->filtered_state->no_ask * 100, 1) . '%' : 'N/A' }}
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @php $first = false; @endphp
                @endforeach
                @if($row['kalshi_markets']->isEmpty())
                    <tr class="border-b">
                        <td class="p-2 font-semibold">{{ $row['city'] }}</td>
                        <td class="p-2 text-xs">{{ $row['timezone'] }}</td>
                        <td class="p-2">{{ number_format($row['hours_to_3pm'], 1) }}</td>
                        <td class="p-2">
                            {{ $row['accuweather'] ? $row['accuweather']->predicted_high : 'N/A' }}
                        </td>
                        <td class="p-2">
                            {{ $row['nws'] ? $row['nws']->predicted_high : 'N/A' }}
                        </td>
                        <td class="p-2 text-gray-400" colspan="3">No Kalshi markets</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@endsection 