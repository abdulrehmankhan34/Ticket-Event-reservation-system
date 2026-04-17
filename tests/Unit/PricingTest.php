<?php

namespace App\Tests\Unit;

use App\Service\Pricing;
use PHPUnit\Framework\TestCase;

final class PricingTest extends TestCase
{
    public function testFinalPriceAddsOnePercentFee(): void
    {
        $pricing = new Pricing();

        self::assertSame(202, $pricing->finalPrice(200));
        self::assertSame(2, $pricing->systemFee(200));
    }
}

