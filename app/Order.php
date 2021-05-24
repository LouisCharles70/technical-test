<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    public function orderPlaced(array $startCoordinates,array $endCoordinates){
        $this->start_latitude = $startCoordinates[0];
        $this->start_longitude = $startCoordinates[0];

        $this->end_latitude = $endCoordinates[0];
        $this->end_longitude = $endCoordinates[0];
        $this->distance = Logistic::computeDistance($startCoordinates,$endCoordinates);

        if(!is_int($this->distance))
            return response()->json([
                "message" => $this->distance
            ],500);

        $this->save();
    }

    public function orderTaken(){
        DB::table("orders")
            ->where('status','=','UNASSIGNED')
            ->where('id','=',$this->id)
            ->update([
                "status" => "TAKEN"
            ]);
    }

    public static function returnFormattedOrders(int $page,int $limit){
        return array_map(function($order){
            return [
                "id" => $order["id"],
                "distance" => $order["distance"],
                "status" => $order["status"]
            ];
        }, Order::take($limit)->skip(($page-1)*$limit)->get()->toArray());
    }
}
