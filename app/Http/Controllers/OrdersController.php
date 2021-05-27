<?php

namespace App\Http\Controllers;

use App\CustomHelpers;
use App\Logistic;
use App\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class OrdersController extends Controller
{

    //  BODY POST REQUEST
    //  {
    //      "origin": ["START_LATITUDE", "START_LONGITUDE"],
    //      "destination": ["END_LATITUDE", "END_LONGITUDE"]
    //  }
    public function placeOrder(Request $request){
        //        Validation rules

        //        Origin and destination must be array of exactly two strings
        $arrayValidationRule = "required|array|min:2|max:2";

        $validator = Validator::make($request->all(),[
            "origin"            => $arrayValidationRule,
            "destination"       => $arrayValidationRule,
            "origin.*"          => "string",
            "destination.*"     => "string"
        ]);

        //        Latitude/Longitude validation rules
        $latitudeRule = "/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/";
        $longitudeRule = "/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/";


        if(isset(request('origin')[0]) && !preg_match($latitudeRule,request('origin')[0]))
            $validator->errors()->add("Origin latitude ","Origin latitude incorrect");

        if(isset(request('destination')[0]) && !preg_match($latitudeRule,request('destination')[0]))
            $validator->errors()->add("Destination latitude ","Destination latitude incorrect");

        if(isset(request('origin')[1]) && !preg_match($longitudeRule,request('origin')[1]))
            $validator->errors()->add("Origin longitude ","Origin longitude incorrect");

        if(isset(request('destination')[1]) && !preg_match($longitudeRule,request('destination')[1]))
            $validator->errors()->add("Destination longitude ","Destination longitude incorrect");


        //        If validation errors have been detected, throw 400 bad request response
        if(count($validator->messages())>0)
            return CustomHelpers::returnValidationErrors($validator);

        //        Check the distance has been successfully computed from the Google Maps Direction API
        try {
            $distance = Logistic::computeDistance(request('origin'), request('destination'));
        } catch (Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ],500);
        }

        //        Store new order's distance inside the database
        $order = new Order();
        $order->orderPlaced($distance);

        return response()->json([
            "id" => $order->id,
            "distance" => $order->distance,
            "status" => "UNASSIGNED"
        ]);
    }

    //  BODY PATCH REQUEST
    //  {
    //      "status": "TAKEN"
    //  }
    public function takeOrder(){
        //        Fetch taken order
        $order = Order::find(request('id'));

        //        If order not found, throw error
        if(is_null($order))
            return response()->json([
                "error" => "Order not found in the database..."
            ],500);

        //        If the error has already been taken, throw error
        if($order->status=="TAKEN")
            return response()->json([
                "error" => "Order already taken"
            ],500);

        //        Update the database
        if(request('status')=='TAKEN')
            $order->orderTaken();


        return response()->json([
            "status" => "Order successfully taken"
        ]);
    }

    //    QUERY STRING PARAMETERS
    //    page, limit
    public function listOrders(Request $request){
        $validationRule = "required|integer|min:1";

        $validator = Validator::make($request->all(),[
            "page"      => $validationRule,
            "limit"     => $validationRule
        ]);

        //        If validation errors have been detected, throw 400 bad request response
        if(count($validator->messages())>0)
            return CustomHelpers::returnValidationErrors($validator);

        $orders = Order::returnFormattedOrders(
            request('page'),
            request('limit')
        );

        return response()->json($orders);
    }
}
