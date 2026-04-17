<?php

namespace App\Service;

final class Pricing
{
    public const SYSTEM_FEE_RATE = 0.01;

    /**
     * Final price = base * 1.01, rounded to nearest integer credit.
     */
    public function finalPrice(int $basePrice): int
    {
        return (int) round($basePrice * (1 + self::SYSTEM_FEE_RATE));
    }

    public function systemFee(int $basePrice): int
    {
        return $this->finalPrice($basePrice) - $basePrice;
    }
}

