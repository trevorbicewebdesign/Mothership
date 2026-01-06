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

     /**
     * Captures a full-page screenshot by resizing the browser to the entire page height, 
     * taking the screenshot, then restoring the original window size.
     *
     * @param string $filename Name or path for the screenshot (without .png extension).
     */
    public function takeFullPageScreenshot(string $filename = 'fullpage'): void
    {
        $I = $this;

        // 1. Remember the current window size so we can restore it
        $originalWidth = $I->executeInSelenium(static function (\Facebook\WebDriver\Remote\RemoteWebDriver $driver) {
            return $driver->manage()->window()->getSize()->getWidth();
        });
        $originalHeight = $I->executeInSelenium(static function (\Facebook\WebDriver\Remote\RemoteWebDriver $driver) {
            return $driver->manage()->window()->getSize()->getHeight();
        });

        // 2. Get the full page height
        $pageHeight = $I->executeJS("
            return Math.max(
                document.body.scrollHeight,
                document.documentElement.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.offsetHeight,
                document.body.clientHeight,
                document.documentElement.clientHeight
            );
        ");

        // 3. Resize the browser window to match the full page height
        //    (use a fixed width, e.g., 1920 - adjust as needed)
        $I->resizeWindow(1920, $pageHeight);

        // 4. Wait briefly for the resize to take effect
        $I->wait(1);

        // 5. Capture screenshot
        $I->makeScreenshot($filename);

        // 6. Restore the original window size
        $I->resizeWindow($originalWidth, $originalHeight);
    }

}
