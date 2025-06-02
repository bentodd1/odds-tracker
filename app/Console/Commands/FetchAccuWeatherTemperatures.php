<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherEvent;
use App\Models\WeatherTemperature;
use App\Services\AccuWeatherService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchAccuWeatherTemperatures extends Command
{
    protected $signature = 'weather:fetch-accuweather
                            {--date= : The date to fetch temperatures for (default: today)}
                            {--location= : The specific location to fetch (default: all locations)}
                            {--debug : Show debug information}';

    protected $description = 'Fetch and store AccuWeather temperature predictions';

    protected $accuWeather;
    protected $debug = false;

    public function __construct(AccuWeatherService $accuWeather)
    {
        parent::__construct();
        $this->accuWeather = $accuWeather;
    }

    public function handle()
    {
        $this->debug = $this->option('debug');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $location = $this->option('location');

        // Get all active Kalshi events for the date
        $query = KalshiWeatherEvent::whereDate('target_date', $date);
        if ($location) {
            $query->where('location', $location);
        }
        $events = $query->get();

        if ($this->debug) {
            $this->info("Found {$events->count()} events for date: {$date->format('Y-m-d')}");
        }

        $temperaturesStored = 0;

        foreach ($events as $event) {
            try {
                if ($this->debug) {
                    $this->info("Processing location: {$event->location}");
                }

                // Get temperature forecast from AccuWeather
                $forecast = $this->accuWeather->getDailyForecast($event->location, $event->target_date);

                if (!$forecast) {
                    if ($this->debug) {
                        $this->warn("No forecast found for {$event->location} on {$event->target_date->format('Y-m-d')}");
                    }
                    continue;
                }

                // Store the temperature
                WeatherTemperature::updateOrCreate(
                    [
                        'location' => $event->location,
                        'date' => $event->target_date,
                        'source' => 'accuweather',
                    ],
                    [
                        'high_temperature' => $forecast['high_temperature'],
                        'low_temperature' => $forecast['low_temperature'],
                        'collected_at' => now(),
                    ]
                );

                $temperaturesStored++;

                if ($this->debug) {
                    $this->info("Stored temperatures for {$event->location}: High {$forecast['high_temperature']}°F, Low {$forecast['low_temperature']}°F");
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$event->location}: " . $e->getMessage());
                if ($this->debug) {
                    $this->error($e->getTraceAsString());
                }
            }
        }

        $this->info("✓ Stored {$temperaturesStored} temperature records");

        return 0;
    }
} 