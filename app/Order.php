<?php

namespace App;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * App\Order
 *
 * @property int $id
 * @property int $distance
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Order newModelQuery()
 * @method static Builder|Order newQuery()
 * @method static Builder|Order query()
 * @method static Builder|Order whereCreatedAt($value)
 * @method static Builder|Order whereDistance($value)
 * @method static Builder|Order whereId($value)
 * @method static Builder|Order whereStatus($value)
 * @method static Builder|Order whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Order extends Model
{
    public function orderPlaced(int $distance){
        $this->distance = $distance;
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
        return Order::take($limit)
            ->skip(($page-1)*$limit)
            ->select([
                "id",
                "distance",
                "status"
            ])->get();
    }
}
