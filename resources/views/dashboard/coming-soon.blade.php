@extends('layouts.app')

@section('title', $sport . ' Odds Dashboard - Coming Soon')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Coming Soon</h1>
            <p class="text-xl text-gray-600 mb-6">{{ $sport }} odds dashboard is currently under development.</p>
            <div class="animate-pulse flex justify-center items-center space-x-4">
                <div class="h-3 w-3 bg-blue-500 rounded-full"></div>
                <div class="h-3 w-3 bg-blue-500 rounded-full"></div>
                <div class="h-3 w-3 bg-blue-500 rounded-full"></div>
            </div>
        </div>
    </div>
@endsection
