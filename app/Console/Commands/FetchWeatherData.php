<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchWeatherData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:weather-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch weather data from AccuWeather';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $apiKey = env('ACCUWEATHER_API_KEY');
        if (!$apiKey) {
            $this->error('AccuWeather API key not found in .env file.');
            return;
        }

        foreach ($this->accuWeatherLocationKeys as $city => $locationKey) {
            $url = "http://dataservice.accuweather.com/forecasts/v1/daily/5day/{$locationKey}?apikey={$apiKey}&details=false&metric=false";
            $response = file_get_contents($url);
            if ($response === false) {
                $this->error("Failed to fetch data for {$city}.");
                continue;
            }

            $data = json_decode($response, true);
            if (isset($data['DailyForecasts'])) {
                foreach ($data['DailyForecasts'] as $forecast) {
                    $prediction = new \App\Models\AccuWeatherPrediction();
                    $prediction->city = $city;
                    $prediction->location_url = $url;
                    $prediction->prediction_date = \Carbon\Carbon::now()->setTimezone('America/Chicago');
                    $prediction->prediction_time = \Carbon\Carbon::now()->setTimezone('America/Chicago');
                    $prediction->target_date = $forecast['Date'];
                    $prediction->predicted_high = $forecast['Temperature']['Maximum']['Value'];
                    $prediction->predicted_low = $forecast['Temperature']['Minimum']['Value'];
                    $prediction->save();
                }
            }
        }
    }

    protected $accuWeatherLocationKeys = [
        'Austin' => '351193',      // Austin-Bergstrom International Airport (AUS)
        'Denver' => '2626644',     // Denver International Airport (DEN)
        'Chicago' => '2626577',    // Chicago O'Hare International Airport (ORD)
        'Los Angeles' => '2142541', // Los Angeles International Airport (LAX)
        'New York' => '2627448',   // New York (Central Park)
        'Philadelphia' => '350540', // Philadelphia International Airport (PHL)
        'Miami' => '347936'        // Miami International Airport (MIA)
    ];
} 