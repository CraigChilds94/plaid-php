<?php 

namespace Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Plaid\Plaid;
use Plaid\PlaidException;

/**
 * @covers Plaid\Plaid
 */
final class PlaidTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Load in env from .env.testing
        $dotenv = new Dotenv(__DIR__ . '/../', '.env.testing');
        $dotenv->load();
    }

    public function testCanBeCreatedWithValidCredentials(): void
    {
        $this->assertInstanceOf(
            Plaid::class,
            new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], $_ENV['PLAID_ENV'])
        );
    }

    public function testCanBeCreatedAsSandbox(): void
    {
        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], 'sandbox');
        $this->assertEquals($plaid->isSandbox(), true);
    }

    public function testCanBeCreatedAsDevelopment(): void
    {
        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], 'development');
        $this->assertEquals($plaid->isDevelopment(), true);
    }

    public function testCanBeCreatedAsProduction(): void
    {
        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], 'production');
        $this->assertEquals($plaid->isProduction(), true);
    }

    public function testThrowsExceptionWhenCreatedWithInvalidEnvironment(): void
    {
        $this->expectException(PlaidException::class);
        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], 'gibberish');
    }

    public function testFetchingStripeBankAccount(): void
    {
        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], $_ENV['PLAID_ENV']);

        $response = \Unirest\Request::post($plaid->getHost().'link/item/create', [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], json_encode([
            'credentials' => [
                'username' => 'user_good',
                'password' => 'pass_good',
            ],
            'initial_products' => ['auth'],
            'institution_id'   => 'ins_1',
            'public_key'       => $_ENV['PLAID_PUBLIC_ID'],
        ]));
        
        $this->assertTrue(isset($response->body->accounts[0]->account_id), 'Checking for account id in response');
        $this->assertTrue(isset($response->body->public_token), 'Checking for public token in response');

        $account_id = $response->body->accounts[0]->account_id;
        $public_token = $response->body->public_token;

        $token = $plaid->getStripeBankAccount($public_token, $account_id);

        $this->assertNotNull($token, 'Checking to see if we got the token back');
    }

    public function testThrowsExceptionWhenFetchingStripeBankAccountWithInvalidToken(): void
    {
        $this->expectException(PlaidException::class);

        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], $_ENV['PLAID_ENV']);
        $plaid->getStripeBankAccount('gibberishtoken', 'gibberishaccountid');
    }
}
