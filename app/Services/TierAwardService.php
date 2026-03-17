<?php

namespace App\Services;

use App\Contracts\TierAwardServiceInterface;

class TierAwardService implements TierAwardserviceInterface{

    public function determineTier(float $point):string{
        $tier = "";
        if($point >= 2500){
            $tier = "Platinum";
        }elseif($point >= 1500){
            $tier= "Gold";
        }elseif($point >= 500){
            $tier= "Silver";
        }else{
            $tier="Basic";
        }
        return $tier;
        
    }

}


