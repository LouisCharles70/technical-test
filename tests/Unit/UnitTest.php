<?php

namespace Tests\Unit;

use App\Order;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Faker;
use yidas\googleMaps\Client;

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

//        $spy = spy(Client::class);
        $double = $this->getMockClass("Client");

        // Test 10 random latitude, longitude
        for($test=0;$test<10;$test++){
            $origin = [
                "$faker->latitude",
                "$faker->longitude"
            ];
            $destination = [
                "$faker->latitude",
                "$faker->longitude"
            ];

            $response = $this
                ->post(env('APP_URL').'/orders',[
                    "origin" => $origin,
                    "destination" => $destination
                ]);

//            $double->method("directions")->with("$origin[0],$origin[1]", "$destination[0],$destination[1]", [
//                    'mode' => "transit",
//                    'departure_time' => time(),
//                ]
//            );

//            Google Directions API can not always compute the distance between two points
            if($response->status()!=200)
                echo $response->content() . "\n";

//            Case where Google Directions API can compute the distance
            if(json_decode($response->content())->message!=="Unable to compute distance between origin and destination"){
                $response->assertStatus(200);

                $this->assertDatabaseHas("orders",[
                    "start_latitude" => $origin[0],
                    "start_longitude" => $origin[1],
                    "end_latitude" => $destination[0],
                    "end_longitude" => $destination[1]
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

        //        Successful orders
        foreach($freeOrdersIds as $randomOrderId){
            $response = $this
                ->patch('/orders/'.$randomOrderId,[
                    "status" => "TAKEN"
                ]);
            if($response->status()==500)
                dd($randomOrderId,$response->content());

            $response->assertStatus(200);

            $this->assertDatabaseHas("orders",[
                "id" => $randomOrderId,
                "status" => "TAKEN"
            ]);
        }

        //      Orders already taken
        $takenOrders = Order::inRandomOrder()
            ->limit(10)
            ->where('status','=','TAKEN')
            ->pluck("id");

        foreach($takenOrders as $orderId){
            $response = $this
                ->patch('/orders/'.$orderId,[
                    "status" => "TAKEN"
                ]);
            if($response->status()==200)
                dd($orderId);

            $response->assertStatus(500);
        }
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
            $response->assertStatus(200);
        }

        //        Test with Page 0
        $response = $this
            ->get('/orders/?page=0&limit=1');
        $response->assertStatus(400);

        //        Test with Limit 0
        $response = $this
            ->get('/orders/?page=1&limit=0');
        $response->assertStatus(400);

        $ordersCount = DB::table("orders")->count()+1;
        //        Test with 0 result
        $response = $this
            ->get("/orders/?page=$ordersCount&limit=1");
        $response->assertStatus(200);

        $this->assertCount(0,json_decode($response->getContent()));
    }
}
