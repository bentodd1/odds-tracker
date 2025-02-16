@extends('layouts.app')

@section('title', 'Sign Up - Smart Betting Analytics')

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    
    document.addEventListener('DOMContentLoaded', function() {
        const subscribeCheckbox = document.getElementById('subscribe');
        const paymentSection = document.getElementById('payment-section');
        
        subscribeCheckbox.addEventListener('change', function() {
            paymentSection.style.display = this.checked ? 'block' : 'none';
        });
    });

    async function handleRegistration(event) {
        event.preventDefault();
        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        if (document.getElementById('subscribe').checked) {
            try {
                const response = await fetch('{{ route('subscription.checkout') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const session = await response.json();
                
                // First create the user account
                const formData = new FormData(form);
                await fetch('{{ route('register') }}', {
                    method: 'POST',
                    body: formData
                });

                // Then redirect to Stripe checkout
                await stripe.redirectToCheckout({ sessionId: session.id });
            } catch (error) {
                console.error('Error:', error);
                submitButton.disabled = false;
            }
        } else {
            form.submit();
        }
    }
</script>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold mb-6 text-center">Create Your Account</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form onsubmit="handleRegistration(event)">
            @csrf
            
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-medium mb-2">Name</label>
                <input type="text" name="name" id="name" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="{{ old('name') }}" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                <input type="email" name="email" id="email" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    value="{{ old('email') }}" required>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                <input type="password" name="password" id="password" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="subscribe" id="subscribe" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-gray-700">Subscribe now for $5 to access all sports</span>
                </label>
            </div>

            <div id="payment-section" class="mb-6" style="display: none;">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">Premium Access Benefits:</h3>
                    <ul class="text-blue-700 space-y-1">
                        <li>✓ Access to all sports odds</li>
                        <li>✓ NCAAF, NBA, NCAAB, and MLB data</li>
                        <li>✓ Instant activation after payment</li>
                    </ul>
                </div>
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                Create Account
            </button>
        </form>

        <p class="mt-4 text-center text-gray-600">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Log in</a>
        </p>
    </div>
</div>
@endsection 