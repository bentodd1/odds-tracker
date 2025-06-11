@extends('layouts.app')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold mb-4">National Weather Service High Temperature Prediction Analysis</h1>
    <p class="mb-4 italic">Note: All differences have their signs flipped from the original data, as requested.</p>
    
    <!-- Filters -->
    <div class="mb-6">
        <form method="GET" class="flex gap-4">
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                <select name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="all" {{ $selectedCity === 'all' ? 'selected' : '' }}>All Cities</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}" {{ $selectedCity === $city ? 'selected' : '' }}>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700">Month</label>
                <select name="month" id="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>All Months</option>
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $selectedMonth == $i ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label for="hour" class="block text-sm font-medium text-gray-700">Forecast Hour</label>
                <select name="hour" id="hour" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="all" {{ $selectedHour === 'all' ? 'selected' : '' }}>All Hours</option>
                    @for($i = 0; $i < 24; $i++)
                        <option value="{{ $i }}" {{ $selectedHour == $i ? 'selected' : '' }}>
                            {{ sprintf('%02d:00', $i) }}
                        </option>
                    @endfor
                </select>
            </div>
            
            <div class="self-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Apply Filters</button>
            </div>
        </form>
    </div>
    
    <!-- Median Chart -->
    <div class="my-8">
        <canvas id="medianDiffChart" height="100"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('medianDiffChart').getContext('2d');
            const cityLabels = @json($cityLabels ?? []);
            const medianData = @json($medianData ?? []);
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: cityLabels,
                    datasets: [{
                        label: 'Median High Temp Difference (°F)',
                        data: medianData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Median High Temperature Differences by City'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Median Difference (°F)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'City'
                            }
                        }
                    }
                }
            });
        });
    </script>
    
    <!-- Median Table -->
    <h2 class="text-xl font-semibold mt-6 mb-2">Median High Temperature Differences by City</h2>
    <div class="overflow-x-auto mb-8">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border">City</th>
                    <th class="p-2 border">Number of Predictions</th>
                    <th class="p-2 border">Median Difference (°F)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cityStats as $city => $stats)
                    <tr>
                        <td class="p-2 border">{{ $city }}</td>
                        <td class="p-2 border text-center">{{ $stats['count'] }}</td>
                        <td class="p-2 border text-center">{{ number_format($stats['median'], 1) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-gray-50 font-semibold">
                    <td class="p-2 border">Overall</td>
                    <td class="p-2 border text-center">{{ $overallStats['count'] }}</td>
                    <td class="p-2 border text-center">{{ number_format($overallStats['median'], 1) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- City Distributions -->
    @foreach($cityStats as $city => $stats)
        <div class="mt-6">
            <h3 class="text-lg font-medium mb-2">{{ $city }} ({{ $stats['count'] }} predictions)</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">Difference (°F)</th>
                            <th class="p-2 border">Count</th>
                            <th class="p-2 border">Percentage</th>
                            <th class="p-2 border">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['distribution'] as $diff => $count)
                            <tr>
                                <td class="p-2 border text-center">{{ $diff }}</td>
                                <td class="p-2 border text-center">{{ $count }}</td>
                                <td class="p-2 border text-center">{{ round(($count / $stats['count']) * 100, 1) }}%</td>
                                <td class="p-2 border">
                                    <div class="bg-blue-500 h-4" style="width: {{ min(($count / $stats['count']) * 300, 100) }}%"></div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
    
    <!-- Overall Distribution -->
    <h2 class="text-xl font-semibold mt-8 mb-2">Overall Distribution of Differences</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border">Difference (°F)</th>
                    <th class="p-2 border">Count</th>
                    <th class="p-2 border">Percentage</th>
                    <th class="p-2 border">Distribution</th>
                </tr>
            </thead>
            <tbody>
                @foreach($overallStats['distribution'] as $item)
                    <tr>
                        <td class="p-2 border text-center">{{ $item['difference'] }}</td>
                        <td class="p-2 border text-center">{{ $item['count'] }}</td>
                        <td class="p-2 border text-center">{{ $item['percentage'] }}%</td>
                        <td class="p-2 border">
                            <div class="bg-blue-500 h-4" style="width: {{ min($item['percentage'] * 3, 100) }}%"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection 