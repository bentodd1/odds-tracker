<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up()
    {
        // Convert timestamps in accuweather_predictions
        DB::table('accuweather_predictions')->orderBy('id')->chunk(100, function ($predictions) {
            foreach ($predictions as $prediction) {
                DB::table('accuweather_predictions')
                    ->where('id', $prediction->id)
                    ->update([
                        'prediction_date' => Carbon::parse($prediction->prediction_date)->setTimezone('America/Chicago'),
                        'prediction_time' => Carbon::parse($prediction->prediction_time)->setTimezone('America/Chicago'),
                        'target_date' => Carbon::parse($prediction->target_date)->setTimezone('America/Chicago'),
                        'created_at' => Carbon::parse($prediction->created_at)->setTimezone('America/Chicago'),
                        'updated_at' => Carbon::parse($prediction->updated_at)->setTimezone('America/Chicago'),
                    ]);
            }
        });

        // Convert timestamps in kalshi_weather_events
        DB::table('kalshi_weather_events')->orderBy('id')->chunk(100, function ($events) {
            foreach ($events as $event) {
                DB::table('kalshi_weather_events')
                    ->where('id', $event->id)
                    ->update([
                        'target_date' => Carbon::parse($event->target_date)->setTimezone('America/Chicago'),
                        'created_at' => Carbon::parse($event->created_at)->setTimezone('America/Chicago'),
                        'updated_at' => Carbon::parse($event->updated_at)->setTimezone('America/Chicago'),
                    ]);
            }
        });

        // Convert timestamps in kalshi_weather_markets
        DB::table('kalshi_weather_markets')->orderBy('id')->chunk(100, function ($markets) {
            foreach ($markets as $market) {
                DB::table('kalshi_weather_markets')
                    ->where('id', $market->id)
                    ->update([
                        'created_at' => Carbon::parse($market->created_at)->setTimezone('America/Chicago'),
                        'updated_at' => Carbon::parse($market->updated_at)->setTimezone('America/Chicago'),
                    ]);
            }
        });

        // Convert timestamps in kalshi_weather_categories
        DB::table('kalshi_weather_categories')->orderBy('id')->chunk(100, function ($categories) {
            foreach ($categories as $category) {
                DB::table('kalshi_weather_categories')
                    ->where('id', $category->id)
                    ->update([
                        'created_at' => Carbon::parse($category->created_at)->setTimezone('America/Chicago'),
                        'updated_at' => Carbon::parse($category->updated_at)->setTimezone('America/Chicago'),
                    ]);
            }
        });

        // Convert timestamps in kalshi_weather_market_states
        DB::table('kalshi_weather_market_states')->orderBy('id')->chunk(100, function ($states) {
            foreach ($states as $state) {
                DB::table('kalshi_weather_market_states')
                    ->where('id', $state->id)
                    ->update([
                        'created_at' => Carbon::parse($state->created_at)->setTimezone('America/Chicago'),
                        'updated_at' => Carbon::parse($state->updated_at)->setTimezone('America/Chicago'),
                    ]);
            }
        });

        // Convert timestamps in weather_temperatures
        DB::table('weather_temperatures')->orderBy('id')->chunk(100, function ($temperatures) {
            foreach ($temperatures as $temperature) {
                DB::table('weather_temperatures')
                    ->where('id', $temperature->id)
                    ->update([
                        'date' => Carbon::parse($temperature->date)->setTimezone('America/Chicago'),
                        'collected_at' => Carbon::parse($temperature->collected_at)->setTimezone('America/Chicago'),
                        'created_at' => Carbon::parse($temperature->created_at)->setTimezone('America/Chicago'),
                        'updated_at' => Carbon::parse($temperature->updated_at)->setTimezone('America/Chicago'),
                    ]);
            }
        });
    }

    public function down()
    {
        // Convert timestamps back to UTC
        DB::table('accuweather_predictions')->orderBy('id')->chunk(100, function ($predictions) {
            foreach ($predictions as $prediction) {
                DB::table('accuweather_predictions')
                    ->where('id', $prediction->id)
                    ->update([
                        'prediction_date' => Carbon::parse($prediction->prediction_date)->setTimezone('UTC'),
                        'prediction_time' => Carbon::parse($prediction->prediction_time)->setTimezone('UTC'),
                        'target_date' => Carbon::parse($prediction->target_date)->setTimezone('UTC'),
                        'created_at' => Carbon::parse($prediction->created_at)->setTimezone('UTC'),
                        'updated_at' => Carbon::parse($prediction->updated_at)->setTimezone('UTC'),
                    ]);
            }
        });

        // Convert other tables back to UTC
        $tables = [
            'kalshi_weather_events',
            'kalshi_weather_markets',
            'kalshi_weather_categories',
            'kalshi_weather_market_states',
            'weather_temperatures'
        ];

        foreach ($tables as $table) {
            DB::table($table)->orderBy('id')->chunk(100, function ($records) use ($table) {
                foreach ($records as $record) {
                    $updates = [];
                    foreach ($record as $key => $value) {
                        if (in_array($key, ['created_at', 'updated_at', 'date', 'target_date', 'collected_at'])) {
                            $updates[$key] = Carbon::parse($value)->setTimezone('UTC');
                        }
                    }
                    if (!empty($updates)) {
                        DB::table($table)->where('id', $record->id)->update($updates);
                    }
                }
            });
        }
    }
}; 