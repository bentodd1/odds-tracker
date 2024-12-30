<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('api/*') && !$request->is('admin/*')) {
            $ip = $request->ip();
            $locationData = Location::get($ip);

            // Create visitor record with null location data if location lookup fails
            Visitor::create([
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'country' => $locationData ? $locationData->countryName : null,
                'city' => $locationData ? $locationData->cityName : null,
                'region' => $locationData ? $locationData->regionName : null,
                'latitude' => $locationData ? $locationData->latitude : null,
                'longitude' => $locationData ? $locationData->longitude : null,
                'page_url' => $request->fullUrl(),
                'visited_at' => now()
            ]);
        }

        return $next($request);
    }
}
