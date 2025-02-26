@extends('layouts.app')

@section('title', 'Subscribe for All Sports Access')

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    
    async function subscribe() {
        // Disable the button to prevent multiple clicks
        const button = document.getElementById('subscribe-button');
        button.disabled = true;
        button.textContent = 'Processing...';
        
        try {
            // Create checkout session
            const response = await fetch('{{ route('subscription.checkout') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to create checkout session');
            }
            
            const session = await response.json();
            
            // Redirect to Stripe checkout
            const result = await stripe.redirectToCheckout({
                sessionId: session.id
            });
            
            if (result.error) {
                throw new Error(result.error.message);
            }
        } catch (error) {
            console.error('Error:', error);
            button.disabled = false;
            button.textContent = 'Unlock All Sports';
            alert('There was an error processing your request. Please try again.');
        }
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
        
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="text-center p-4">
                <h2 class="text-xl font-semibold mb-2">Free Access</h2>
                <div class="text-3xl font-bold text-gray-400 mb-4">$0</div>
                <ul class="text-gray-600 space-y-2">
                    <li>NFL Odds</li>
                    <li>NBA Odds</li>
                    <li>College Football (NCAAF) Odds</li>
                    <li>College Basketball (NCAAB) Odds</li>
                </ul>
                <p class="mt-2 text-sm text-gray-600">Limited to one game per sport</p>
            </div>
            
            <div class="text-center p-4 bg-blue-50 rounded-lg border-2 border-blue-500">
                <h2 class="text-xl font-semibold mb-2">Premium Access</h2>
                <div class="text-3xl font-bold text-blue-600 mb-4">$10</div>
                <ul class="text-gray-600 space-y-2">
                    <li>NFL Odds</li>
                    <li>NBA Odds</li>
                    <li>College Football (NCAAF) Odds</li>
                    <li>College Basketball (NCAAB) Odds</li>
                </ul>
                <p class="mt-2 text-sm text-gray-600">Access to all games</p>
                <button 
                    id="subscribe-button"
                    onclick="subscribe()"
                    class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition mt-4">
                    Unlock All Sports
                </button>
            </div>
        </div>
        
        <div class="mt-8 border-t pt-6">
            <h3 class="text-lg font-semibold mb-4 text-center">What You'll Get</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-blue-600 mb-2">
                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium">All Sports Access</h4>
                    <p class="text-sm text-gray-600">View odds for every major sport</p>
                </div>
                <div class="text-center">
                    <div class="text-blue-600 mb-2">
                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium">Advanced Analytics</h4>
                    <p class="text-sm text-gray-600">FPI and win probability data</p>
                </div>
                <div class="text-center">
                    <div class="text-blue-600 mb-2">
                        <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium">Real-Time Updates</h4>
                    <p class="text-sm text-gray-600">Latest odds from top sportsbooks</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 