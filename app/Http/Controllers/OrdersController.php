<?php

namespace App\Http\Controllers;

use App\CustomHelpers;
use App\Order;
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

//        TODO: Need to fix the validation rule around latitude|longitude
        // Origin and destination must be array of exactly two strings
        $arrayValidationRule = "required|array|min:2|max:2";

        // Validation rule for latitude: float between -90 and +90
        $latitudeValidationRule = ["string","/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/"];

        // Validation rule for longitude: float between -180 and +180
        $longitudeValidationRule = ["string","/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/"];


        $validator = Validator::make($request->all(),[
            "origin"            => $arrayValidationRule,
            "destination"       => $arrayValidationRule,
            'origin[0]'         => $latitudeValidationRule,
            'origin[1]'         => $longitudeValidationRule,
            'destination[0]'    => $latitudeValidationRule,
            'destination[1]'    => $longitudeValidationRule
        ]);

        $validationErrors = $validator->errors()->messages();
        if(count($validationErrors)>0)
            return CustomHelpers::returnValidationErrors();

        $order = new Order();
        $order->orderPlaced(
            request('origin'),
            request('destination')
        );

        return response([
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
        $order = Order::find(request('id'));

        if($order->status=="TAKEN")
            return response()->json([
                "error" => "Order already taken"
            ],500);

        if(request('status')=='TAKEN')
            $order->orderTaken();

        return response()->json([
            "status" => "Order successfully taken"
        ]);
    }

    //    QUERY STRING PARAMETERS
    //    page, limit
    public function listOrders(Request $request){
        $validationRule = "integer|min:1";

        $validator = Validator::make($request->all(),[
            "page"      => $validationRule,
            "limit"     => $validationRule
        ]);


        $validationErrors = $validator->errors()->messages();
        if(count($validationErrors)>0)
            return CustomHelpers::returnValidationErrors();


        return response()->json(Order::returnFormattedOrders(
            request('page'),
            request('limit')
        ));
    }
}
