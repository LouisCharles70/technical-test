<?php

namespace Tests\Unit;

use App\Order;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Faker;

class UnitTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */


    /** @test */
    public function placeOrder(){
        $faker = Faker\Factory::create();

        // Happy scenarios: Test 10 random latitude, longitude
        for($test=0;$test<10;$test++){
            $origin = [
                "$faker->latitude",
                "$faker->longitude"
            ];
            $destination = [
                "$faker->latitude",
                "$faker->longitude"
            ];

//            TODO: MOCK GOOGLE CLOUD FUNCTION API
            $response = $this
                ->post(env('APP_URL').'/orders',[
                    "origin" => $origin,
                    "destination" => $destination
                ]);

//            Google Directions API can not always compute the distance between two points as the random latitude
//            longitude are often located in the sea, in these cases the distance can not be computed by Google
//            Directions API
            if($response->status()!=200)
                echo $response->content() . "\n";

//            Case where Google Directions API can compute the distance
            if(!isset(json_decode($response->getContent())->message)){
                $response->assertStatus(200);

                $decodedResponse = json_decode($response->getContent());

                $this->assertDatabaseHas("orders",[
                    "id" => $decodedResponse->id,
                    "distance" => $decodedResponse->distance,
                    "status" => "UNASSIGNED"
                ]);
            }
        }

        // Test with integers
        $origin = [
            $faker->randomNumber(2),
            $faker->randomNumber(2),
        ];
        $destination = [
            $faker->randomNumber(2),
            $faker->randomNumber(2),
        ];

        $response = $this
            ->post(env('APP_URL').'/orders',[
                "origin" => $origin,
                "destination" => $destination
            ]);
        $response->assertStatus(400);


        // Test with floats
        $origin = [
            $faker->randomFloat(2),
            $faker->randomFloat(2),
        ];
        $destination = [
            $faker->randomFloat(2),
            $faker->randomFloat(2),
        ];

        $response = $this
            ->post(env('APP_URL').'/orders',[
                "origin" => $origin,
                "destination" => $destination
            ]);
        $response->assertStatus(400);


//        Test with incorrect latitude (>90°)
        $origin = [
            "114.127620",
            "114.127620",
        ];
        $destination = [
            "114.127620",
            "114.127620",
        ];

        $response = $this
            ->post(env('APP_URL').'/orders',[
                "origin" => $origin,
                "destination" => $destination
            ]);
        $response->assertStatus(400);

        //        Test with incorrect longitude (>180°)
        $origin = [
            "22.127620",
            "214.127620",
        ];
        $destination = [
            "22.127620",
            "214.127620",
        ];

        $response = $this
            ->post(env('APP_URL').'/orders',[
                "origin" => $origin,
                "destination" => $destination
            ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function takeOrder(){
        $freeOrdersIds = Order::inRandomOrder()
            ->limit(10)
            ->where('status','=','UNASSIGNED')
            ->pluck("id");

//        Happy scenarios: Successful orders taken
        foreach($freeOrdersIds as $randomOrderId){
            $response = $this
                ->patch('/orders/'.$randomOrderId,[
                    "status" => "TAKEN"
                ]);
            $response->assertStatus(200);

            $this->assertDatabaseHas("orders",[
                "id" => $randomOrderId,
                "status" => "TAKEN"
            ]);
        }

//        Orders already taken
        $takenOrders = Order::inRandomOrder()
            ->limit(10)
            ->where('status','=','TAKEN')
            ->pluck("id");

        foreach($takenOrders as $orderId){
            $response = $this
                ->patch('/orders/'.$orderId,[
                    "status" => "TAKEN"
                ]);
            $response->assertStatus(500);
        }

        //        Order doesn't exist
        $nonExistingOrderId = DB::table("orders")
            ->orderBy("id","desc")
            ->first()
            ->id+1;

        $response = $this
            ->patch('/orders/'.$nonExistingOrderId,[
                "status" => "TAKEN"
            ]);
        $response->assertStatus(500);

    }

    /** @test */
    public function orderList(){
        $faker = Faker\Factory::create();

        //        Successful responses
        for($test=0;$test<10;$test++){
            $randomPageNumber = $faker->randomNumber(2);
            $randomLimit = $faker->randomNumber(2);

            $response = $this
                ->get("/orders/?page=$randomPageNumber&limit=$randomLimit");

            if($randomLimit==0 or $randomPageNumber==0){
                $response->assertStatus(400);
                continue;
            }

            $response->assertStatus(200);
        }

        $ordersCount = DB::table("orders")->count()+1;
        //        Test with 0 result
        $response = $this
            ->get("/orders/?page=$ordersCount&limit=1");
        $response->assertStatus(200);

        $this->assertCount(0,json_decode($response->getContent()));
    }
}
