<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

/**
 * App\Logistic
 *
 * @method static Builder|Logistic newModelQuery()
 * @method static Builder|Logistic newQuery()
 * @method static Builder|Logistic query()
 * @mixin Eloquent
 */
class Logistic extends Model
{
    public static function computeDistance($startCoordinates,$endCoordinates){
        $directionsResult = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                'origin' => "$startCoordinates[0],$startCoordinates[1]",
                'destination' => "$endCoordinates[0],$endCoordinates[1]",
                'key' => env('GOOGLE_MAPS_API_KEY')
            ]);

        $directionsResult = json_decode($directionsResult,TRUE);

        if($directionsResult["status"]=="ZERO_RESULTS")
            throw new \Exception("Unable to compute distance between origin and destination");

        return $directionsResult["routes"][0]["legs"][0]["distance"]["value"];
    }
}
