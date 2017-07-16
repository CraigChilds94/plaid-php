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
            new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'])
        );
    }

    public function testCanBeCreatedAsSandbox(): void
    {
        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], true);
        $this->assertEquals($plaid->isSandbox(), true);
    }

    public function testCanBeCreatedAsLive(): void
    {
        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], false);
        $this->assertEquals($plaid->isSandbox(), false);
    }

    public function testThrowsExceptionWhenFetchingStripeBankAccountWithInvalidToken(): void
    {
        $this->expectException(PlaidException::class);

        $plaid = new Plaid($_ENV['PLAID_CLIENT_ID'], $_ENV['PLAID_SECRET'], true);
        $plaid->getStripeBankAccount('gibberishtoken', 'gibberishaccountid');
    }
}