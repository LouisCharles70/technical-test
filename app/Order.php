<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function orderPlaced(array $startCoordinates,array $endCoordinates){
        $this->start_latitude = $startCoordinates[0];
        $this->start_longitude = $startCoordinates[0];

        $this->end_latitude = $endCoordinates[0];
        $this->end_longitude = $endCoordinates[0];
        $this->distance = Logistic::computeDistance($startCoordinates,$endCoordinates);
        $this->save();
    }

    public function orderTaken(){
        $this->status = "TAKEN";
        $this->save();
    }

    public static function returnFormattedOrders(int $page,int $limit){
        dd(Order::paginate($limit, ['*'], 'page', $page));

        return array_map(function($order){
            return [
                "id" => $order->id,
                "distance" => $order->distance,
                "status" => $order->status
            ];
        }, Order::paginate($limit, ['*'], 'page', $page)->get());
    }
}
