<?php

namespace App;

class CustomHelpers{


    public static function returnValidationErrors($validator){
        return response()->json($validator->messages(),"400");
    }
}