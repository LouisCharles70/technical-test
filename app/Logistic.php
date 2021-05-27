<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use yidas\googleMaps\Client;

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
        try {
            $gmaps = new Client(['key' => env('GOOGLE_MAPS_API_KEY')]);
        } catch (\Exception $e) {
            return "Please check your google maps API credentials";
        }

        $directionsResult = $gmaps->directions("$startCoordinates[0],$startCoordinates[1]", "$endCoordinates[0],$endCoordinates[1]", [
                'mode' => "transit",
                'departure_time' => time(),
            ]
        );

        if($directionsResult["status"]=="ZERO_RESULTS")
            throw new \Exception("Unable to compute distance between origin and destination");

        return $directionsResult["routes"][0]["legs"][0]["distance"]["value"];
    }
}
