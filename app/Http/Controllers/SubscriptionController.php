<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class SubscriptionController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        return view('dashboard.subscribe');
    }

    public function checkout()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Sports Odds Premium Access',
                        ],
                        'unit_amount' => 500, // $5.00 in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('subscription.success', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.cancel', [], true),
                'metadata' => [
                    'user_id' => auth()->id()
                ]
            ]);

            return response()->json(['id' => $session->id]);
        } catch (\Exception $e) {
            Log::error('Stripe session creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment session creation failed'], 500);
        }
    }

    public function success(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = Session::retrieve($request->get('session_id'));
            
            if ($session->payment_status === 'paid') {
                // Create subscription
                Subscription::create([
                    'user_id' => auth()->id(),
                    'stripe_id' => $session->id,
                    'status' => 'active',
                    'expires_at' => now()->addYear()
                ]);

                return redirect()->route('dashboard.ncaaf')
                    ->with('success', 'Thank you for subscribing! You now have access to all sports.');
            }
        } catch (\Exception $e) {
            Log::error('Subscription creation failed: ' . $e->getMessage());
            return redirect()->route('dashboard.subscribe')
                ->with('error', 'There was a problem processing your payment.');
        }
    }

    public function cancel()
    {
        return redirect()->route('dashboard.subscribe')
            ->with('error', 'Your payment was cancelled.');
    }
} 