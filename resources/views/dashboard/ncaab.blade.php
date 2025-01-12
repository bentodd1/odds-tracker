{{-- resources/views/dashboard/ncaab.blade.php --}}
@extends('layouts.app')

@section('title', 'NCAAB Odds Dashboard')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <x-dashboard.explanation-header :sport="$sport" />
        <x-dashboard.casino-selector
            :availableCasinos="$availableCasinos"
            :selectedCasinos="$selectedCasinos"
            :sport="$sport" />

        <!-- Main Odds Table -->
        @include('dashboard.partials.odds-table', [
            'games' => $games,
            'selectedCasinos' => $selectedCasinos
        ])
    </div>
@endsection

@push('scripts')
    @include('dashboard.partials.casino-selector-script')
@endpush
