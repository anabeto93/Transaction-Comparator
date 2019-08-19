<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Illuminate\Support\Facades\Artisan;

/**
 * Defines application features from the specific context.
 */
class TransactionContext extends MinkContext implements Context
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
        Artisan::call('migrate:rollback');
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

    /** @Given I am an authenticated user */
    public function authenticatedUser()
    {
        $this->visitPath('/logout');
        $pass = 'not_so_secret_password';
        $user = factory(App\Models\User::class)->create([
            'name' => 'Tester',
            'email' => 'qa@tutuka.com',
            'password' => bcrypt($pass),
        ]);

        $this->visitPath('/login');
        $this->fillField('email', $user->email);
        $this->fillField('password', $pass);
        $this->pressButton('Login');
    }

    /**
     * @Given I submit the comparator form
     */
    public function iSubmitTheComparatorForm()
    {
        $this->pressButton('Compare');
    }

    /**
     * @When I press the button :button
     */
    public function pressTheButton($button)
    {
        $this->pressButton($button);
    }

    /**
     * @When I fill the form field :field with data :data
     */
    public function fillTheField($field, $data)
    {
        $this->fillField($field, $data);
    }

    /**
     * @Then I should see the validation error :arg1
     */
    public function iShouldSeeTheValidationError($arg1)
    {
        $this->assertPageContainsText($arg1);
    }
}
