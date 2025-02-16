@extends('layouts.app')

@section('title', 'Subscribe for All Sports Access')

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    
    function subscribe() {
        // Disable the button to prevent multiple clicks
        document.getElementById('subscribe-button').disabled = true;
        
        // Create checkout session
        fetch('{{ route('subscription.checkout') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(session => {
            // Redirect to Stripe Checkout
            return stripe.redirectToCheckout({ sessionId: session.id });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('subscribe-button').disabled = false;
        });
    }
</script>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold mb-6 text-center">Unlock All Sports</h1>
        
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="text-center p-4">
                <h2 class="text-xl font-semibold mb-2">Free Access</h2>
                <ul class="text-gray-600 space-y-2">
                    <li>✓ NFL Odds</li>
                    <li class="text-gray-400">✗ NCAAF Odds</li>
                    <li class="text-gray-400">✗ NBA Odds</li>
                    <li class="text-gray-400">✗ NCAAB Odds</li>
                    <li class="text-gray-400">✗ MLB Odds</li>
                </ul>
            </div>
            
            <div class="text-center p-4 bg-blue-50 rounded-lg border-2 border-blue-500">
                <h2 class="text-xl font-semibold mb-2">Premium Access</h2>
                <div class="text-3xl font-bold text-blue-600 mb-4">$5</div>
                <ul class="text-gray-600 space-y-2">
                    <li>✓ NFL Odds</li>
                    <li>✓ NCAAF Odds</li>
                    <li>✓ NBA Odds</li>
                    <li>✓ NCAAB Odds</li>
                    <li>✓ MLB Odds</li>
                </ul>
                <button 
                    id="subscribe-button"
                    onclick="subscribe()"
                    class="mt-6 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Unlock All Sports
                </button>
            </div>
        </div>
    </div>
</div>
@endsection 