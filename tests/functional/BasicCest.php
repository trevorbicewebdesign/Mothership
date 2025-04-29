<?php

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class BasicCest
{
    public function _before(FunctionalTester $I) {}

    public function homepageIsVisible(FunctionalTester $I)
    {
        $I->amOnPage("/");
        $I->see("Login Form", "h3");
    }
}
