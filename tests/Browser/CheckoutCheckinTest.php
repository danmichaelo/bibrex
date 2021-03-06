<?php

namespace Tests\Browser;

use App\Item;
use App\Loan;
use App\Thing;
use App\User;
use App\UserIdentifier;
use Closure;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\LoansPage;
use function Stringy\create as s;

class CheckoutCheckinTest extends DuskTestCase
{
    use DatabaseMigrations;

    /* @var Thing[] */
    private $things;

    /* @var Item[] */
    private $items;

    /* @var User[] */
    private $users;

    /**
     * Create a few things, a few items and a few patrons
     */
    public function setUp(): void
    {
        parent::setUp();

        $faker = app('Faker\Generator');
        $faker->seed(1234);

        // Make a few things
        $this->things = Thing::factory(5)->create();

        // And a few items
        $this->items = $this->things->flatMap(
            function (Thing $thing) {
                $items = Item::factory(3)->make();
                $thing->items()->saveMany($items);
                return $items;
            }
        );

        // And a few patrons
        $this->users = User::factory(10)->create()->each(function (User $user) {
            $user->identifiers()->save(UserIdentifier::factory()->make());
        });
    }

    protected function checkout($thing, $user)
    {
        \Log::info(sprintf('Trying to checkout "%s" to "%s"', $thing, $user));

        $this->browse(
            function (Browser $browser) use ($thing, $user) {
                $browser->loginAs('post@eksempelbiblioteket.no');

                $browser->visit(new LoansPage)
                    ->waitForText('Til hvem?')
                    ->type('user', $user)
                    ->type('thing', $thing)
                    ->clickLink('Lån ut', 'button')
                    ->waitForText('Lånte ut')
                    ->waitForText('nå nettopp');
            }
        );
    }

    /**
     * Test that we can checkout using one of the identifiers of a user.
     */
    public function testCheckoutUsingUserIdentifier()
    {
        $thing = $this->items[0]->barcode;
        $user = $this->users[0]->identifiers[0]->value;

        $this->checkout($thing, $user);
    }

    /**
     * Test that we can checkout using the Alma primary id of a user.
     */
    public function testCheckoutUsingAlmaPrimaryId()
    {
        $thing = $this->items[0]->barcode;
        $user = $this->users[0]->alma_primary_id;

        $this->checkout($thing, $user);
    }

    /**
     * Test that we can checkout an item using the name of a user.
     */
    public function testCheckoutUsingNameOfUser()
    {
        $thing = $this->items[0]->barcode;
        $user = $this->users[0]->name;

        $this->checkout($thing, $user);
    }

    /**
     * Test that we can enter the name of an non-existing user and get guided through the creation of a local user.
     */
    public function testCheckoutUsingNameOfNonExistingUser()
    {
        $this->browse(
            function (Browser $browser) {
                $faker = app('Faker\Generator');
                $browser->loginAs('post@eksempelbiblioteket.no');

                $lastname = $faker->lastName;
                $firstname = $faker->firstName;
                $fullname = "$lastname, $firstname";

                $browser->visit(new LoansPage)
                    ->waitForText('Til hvem?')
                    ->type('user', $fullname)
                    ->type('thing', $this->items[0]->barcode)
                    ->clickLink('Lån ut', 'button')
                    ->waitForText('Brukeren ble ikke funnet')
                    ->clickLink('opprette en lokal bruker')
                    ->waitForText('Opprett lokal bruker')
                    ->assertInputValue('lastname', $lastname)
                    ->assertInputValue('firstname', $firstname)
                    ->type('email', $faker->email)
                    ->radio('lang', 'nob')
                    ->press('Lagre')
                    ->waitForText('Brukeren ble opprettet')
                    ->assertInputValue('user', $fullname)
                    ->type('thing', $this->items[0]->barcode)
                    ->clickLink('Lån ut', 'button')
                    ->waitForText('Lånte ut')
                    ->waitForText('nå nettopp');
            }
        );
    }

    /**
     * Test that we can enter the barcode of an non-existing user and get guided through the creation of a local user.
     */
    public function testCheckoutUsingBarcodeOfNonExistingUser()
    {
        $this->browse(
            function (Browser $browser) {
                $faker = app('Faker\Generator');
                $faker->addProvider(new \Tests\Faker\Library($faker));

                $browser->loginAs('post@eksempelbiblioteket.no');

                $barcode = $faker->userBarcode;
                $lastname = $faker->lastname;
                $firstname = $faker->firstname;
                $fullname = "$lastname, $firstname";

                $browser->visit(new LoansPage)
                    ->waitForText('Til hvem?')
                    ->type('user', $barcode)
                    ->type('thing', $this->items[0]->barcode)
                    ->clickLink('Lån ut', 'button')
                    ->waitForText('Strekkoden ble ikke funnet')
                    ->clickLink('opprett en lokal bruker')
                    ->waitForText('Opprett lokal bruker')
                    ->assertInputValue('identifier_value_0', $barcode)
                    ->type('lastname', $lastname)
                    ->type('firstname', $firstname)
                    ->type('email', $faker->email)
                    ->radio('lang', 'nob')
                    ->press('Lagre')
                    ->waitForText('Brukeren ble opprettet')
                    ->assertInputValue('user', $fullname)
                    ->type('thing', $this->items[0]->barcode)
                    ->clickLink('Lån ut', 'button')
                    ->waitForText('Lånte ut')
                    ->waitForText('nå nettopp');
            }
        );
    }

    /**
     * Test that we can checkout an item using the name of a thing if loans_without_barcode is activated.
     */
    public function testCheckoutUsingNameOfThing()
    {
        $settings = $this->things[1]->getLibrarySettingsAttribute($this->currentLibrary);
        $settings->loans_without_barcode = true;
        $settings->save();

        $this->browse(
            function (Browser $browser) {
                $browser->loginAs('post@eksempelbiblioteket.no');

                // First try a thing that cannot be loaned by name
                $browser->visit(new LoansPage)
                    ->waitForText('Til hvem?')
                    ->type('user', $this->users[0]->identifiers[0]->value)
                    ->type('thing', $this->things[0]->name())
                    ->clickLink('Lån ut', 'button')
                    ->waitForText('Utlån av denne tingen må gjøres med strekkode');

                // Then try a thing that can be loaned by name
                $browser->visit(new LoansPage)
                    ->waitForText('Til hvem?')
                    ->type('user', $this->users[0]->identifiers[0]->value)
                    ->type('thing', $this->things[1]->name())
                    ->clickLink('Lån ut', 'button')
                    ->waitForText('Lånte ut')
                    ->waitForText('nå nettopp');
            }
        );
    }

    /**
     * Test that we can make a checkin easily.
     */
    public function testCheckin()
    {
        // Make a loan
        $item = $this->items[0];
        $user = $this->users[0];

        $item->loans()->save(
            Loan::factory()->make([
                'user_id' => $user->id,
                'library_id' => $this->currentLibrary->id,
                'as_guest' => false,
            ])
        );

        $this->browse(
            function (Browser $browser) use ($item) {
                $browser->loginAs('post@eksempelbiblioteket.no');

                $browser->visit(new LoansPage)
                    ->waitForText('Til hvem?')
                    ->click('#nav-checkin-tab')
                    ->waitForText('Strekkode:')
                    ->type('barcode', $item->barcode)
                    ->clickLink('Returner', 'button')
                    ->waitForText('Returnerte')
                        ->pause(1000); // Give the loans table some time to update, to avoid errors in the log from the xhr request.
            }
        );
    }
}
