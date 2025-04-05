<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */
    public function logInAs($username, $password)
    {
        $I = $this;
        $I->amOnPage("/");

        // Log in with valid credentials
        $I->fillField(".mod-login input[name=username]", strtolower($username));
        $I->fillField(".mod-login input[name=password]", $password);
        $I->click(".mod-login button[type=submit]");
        $I->wait(1);
    }

}
