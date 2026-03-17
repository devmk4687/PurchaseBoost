<?php
namespace App\Services;

use App\Models\Order;


class CampaignPointsService
{
    public function __construct(){
        $this->campStartDate = "2026-03-01 00:00:00";
        $this->campEndDate = "2026-03-31 00:00:00";
        $this->orderCampPoints = 0;

    }

    public function processCampaign($order){
        if($order->orderDate >=  $this->campStartDate && $order->orderDate <= $this->campEndDate){
            $this->orderCampPoints = $order->price + 100;

            return $this->orderCampPoints;
        }else {
            return false;
        }
    }

}
