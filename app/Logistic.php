<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use yidas\googleMaps\Client;


class Logistic extends Model
{
    public static function computeDistance($startCoordinates,$endCoordinates){
        $gmaps = new \yidas\googleMaps\Client(['key'=>env('GOOGLE_MAPS_API_KEY')]);

        $directionsResult = $gmaps->directions("$startCoordinates[0],$startCoordinates[1]", "$endCoordinates[0],$endCoordinates[1]", [
                'mode' => "transit",
                'departure_time' => time(),
            ]
        );

        if($directionsResult["status"]=="ZERO_RESULTS")
            return "Unable to compute distance between origin and destination";

        return $directionsResult["routes"][0]["legs"][0]["distance"]["value"];
    }
}
