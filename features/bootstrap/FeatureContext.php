<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Illuminate\Support\Facades\Artisan;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context
{
    use \Laracasts\Behat\Context\Migrator;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
    }

    /** @BeforeScenario
     * @param BeforeScenarioScope $scope
     */
    public function before(BeforeScenarioScope $scope)
    {
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    /** @AfterScenario
     * @param AfterScenarioScope $scope
     */

    public function after(AfterScenarioScope $scope)
    {
        Artisan::call('migrate:rollback');
    }

    /**
     * @Given I visit the path :path
     */
    public function iVisitThePath($path)
    {
        $this->visitPath($path);
        $this->assertResponseStatus(200);
    }

    /**
     * @Then I should see the text :text
     */
    public function iShouldSeeTheText($text)
    {
        $this->assertPageContainsText($text);
    }

    /**
     * @Given a user called :user with email :email and password :password exists
     */
    public function aUserCalledExists($user, $email, $password)
    {
        $user = factory(App\Models\User::class)->create([
            'name' => $user,
            'email' => $email,
            'password' => bcrypt($password),
        ]);
    }

    /**
     * @Given I am logged out
     */
    public function logoutUser()
    {
        $this->visitPath('/logout');
        Artisan::call('migrate:refresh');
    }

    /** @Given I fill in the form with the email :email and password :password and submit the form */
    public function iFillInTheFormWithMyEmailAndPasswordAndSubmitTheForm($email, $password)
    {
        $this->fillField('email', $email);
        $this->fillField('password', $password);
        $this->pressButton('Login');
    }

    /** @Given I fill in the form with my name :name and email :email password :password and submit the form */
    public function iFillInTheFormWithMyNameAndPasswordAndPasswordConfirmationAndSubmitTheForm($name, $email, $password)
    {
        $this->fillField('name', $name);
        $this->fillField('email', $email);
        $this->fillField('password', $password);
        $this->fillField('password_confirmation', $password);
        $this->pressButton('Register');
    }
}
