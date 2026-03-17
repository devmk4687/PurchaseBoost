<?php

namespace App\Contracts;

interface TierAwardServiceInterface {
    public function determineTier(float $point):string;
}
