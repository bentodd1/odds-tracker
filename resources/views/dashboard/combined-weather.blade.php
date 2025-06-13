@extends('layouts.app')

@section('title', 'Combined Weather Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Combined Weather Dashboard</h1>
    <form method="GET" class="mb-4 flex gap-4 items-center">
        <div>
            <label for="date" class="mr-2 font-semibold">Select Date:</label>
            <select name="date" id="date" onchange="this.form.submit()" class="border rounded px-2 py-1">
                <option value="{{ $today }}" {{ request('date', $today) == $today ? 'selected' : '' }}>Today ({{ $today }})</option>
                <option value="{{ $tomorrow }}" {{ request('date', $today) == $tomorrow ? 'selected' : '' }}>Tomorrow ({{ $tomorrow }})</option>
            </select>
        </div>
        <div>
            <label for="hour" class="mr-2 font-semibold">Select Hour:</label>
            <select name="hour" id="hour" onchange="this.form.submit()" class="border rounded px-2 py-1">
                @for($i = 0; $i < 24; $i++)
                    <option value="{{ $i }}" {{ request('hour', 1) == $i ? 'selected' : '' }}>{{ sprintf('%02d:00', $i) }}</option>
                @endfor
            </select>
        </div>
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
                <th class="p-2">Accu Model Prob</th>
                <th class="p-2">NWS Model Prob</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $row)
                @php
                    $city = $row['city'];
                    $accuweather = $row['accuweather'];
                    $nws = $row['nws'];
                @endphp
                @php $first = true; @endphp
                @foreach($row['kalshi_markets'] as $market)
                    @php
                        $parsed = \App\WeatherProbabilityHelper::extractTemperaturesFromTitle($market->title);
                        $type = $parsed['type'];
                        $lowTemp = $parsed['low_temperature'];
                        $highTemp = $parsed['high_temperature'];
                        $displayRange = '';
                        if (($type === 'above' || $type === 'below' || $type === 'between') && ($highTemp !== null || $lowTemp !== null)) {
                            if ($type === 'above' && $highTemp !== null) {
                                $displayRange = '&gt;' . $highTemp . '°';
                            } elseif ($type === 'below' && $highTemp !== null) {
                                $displayRange = '&lt;' . $highTemp . '°';
                            } elseif ($type === 'between' && $lowTemp !== null && $highTemp !== null) {
                                $displayRange = $lowTemp . '-' . $highTemp . '°';
                            }
                        } else {
                            $displayRange = e($market->title);
                        }
                    @endphp
                    <tr class="border-b">
                        @if($first)
                            <td class="p-2 font-semibold" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">{{ $city }}</td>
                            <td class="p-2 text-xs" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">
                                {{ $accuweather && $accuweather->timezone ? $accuweather->timezone : 'N/A' }}
                            </td>
                            <td class="p-2" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">
                                {{ $accuweather && $accuweather->hours_to_3pm ? number_format($accuweather->hours_to_3pm, 1) : 'N/A' }}
                            </td>
                            <td class="p-2" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">
                                {{ $accuweather ? $accuweather->predicted_high : 'N/A' }}
                            </td>
                            <td class="p-2" rowspan="{{ max(1, $row['kalshi_markets']->count()) }}">
                                {{ $nws ? $nws->predicted_high : 'N/A' }}
                            </td>
                        @endif
                        <td class="p-2 max-w-xs whitespace-normal break-words text-center">{!! $displayRange !!}</td>
                        <td class="p-2 text-center">
                            @if($market->filtered_state)
                                <div class="font-bold">{{ $market->filtered_state->yes_ask !== null ? number_format($market->filtered_state->yes_ask, 1) . '%' : 'N/A' }}</div>
                                @php $yesEdge = null; $yesProb = $market->accu_model_prob; $yesAsk = $market->filtered_state->yes_ask !== null ? $market->filtered_state->yes_ask / 100.0 : null; if ($yesProb !== null && $yesAsk !== null) { $yesEdge = $yesProb - $yesAsk; } @endphp
                                @if($yesEdge !== null)
                                    <div class="text-sm {{ $yesEdge >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                        Edge {{ $yesEdge >= 0 ? '+' : '' }}{{ number_format($yesEdge * 100, 1) }}%
                                    </div>
                                @endif
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="p-2 text-center">
                            @if($market->filtered_state)
                                <div class="font-bold">{{ $market->filtered_state->no_ask !== null ? number_format($market->filtered_state->no_ask, 1) . '%' : 'N/A' }}</div>
                                @php $noEdge = null; $noProb = $market->accu_model_prob !== null ? 1 - $market->accu_model_prob : null; $noAsk = $market->filtered_state->no_ask !== null ? $market->filtered_state->no_ask / 100.0 : null; if ($noProb !== null && $noAsk !== null) { $noEdge = $noProb - $noAsk; } @endphp
                                @if($noEdge !== null)
                                    <div class="text-sm {{ $noEdge >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                        Edge {{ $noEdge >= 0 ? '+' : '' }}{{ number_format($noEdge * 100, 1) }}%
                                    </div>
                                @endif
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="p-2 text-center">{{ $market->accu_model_prob !== null ? number_format($market->accu_model_prob * 100, 1) . '%' : 'N/A' }}</td>
                        <td class="p-2 text-center">{{ $market->nws_model_prob !== null ? number_format($market->nws_model_prob * 100, 1) . '%' : 'N/A' }}</td>
                    </tr>
                    @php $first = false; @endphp
                @endforeach
                @if($row['kalshi_markets']->isEmpty())
                    <tr class="border-b">
                        <td class="p-2 font-semibold">{{ $city }}</td>
                        <td class="p-2 text-xs">N/A</td>
                        <td class="p-2">N/A</td>
                        <td class="p-2">N/A</td>
                        <td class="p-2">N/A</td>
                        <td class="p-2 text-gray-400" colspan="5">No Kalshi markets</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@endsection 