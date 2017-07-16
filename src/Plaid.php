<?php

namespace Plaid;

use Plaid\PlaidException;

/**
 * Handle interactions with the Plaid platform
 */
class Plaid
{
    /**
     * Our Plaid client ID
     *
     * @var string
     */
    private $client_id;

    /**
     * Our Plaid API secret
     *
     * @var string
     */
    private $secret;

    /**
     * Are we using the test environment?
     *
     * @var boolean
     */
    private $testing;

    /**
     * The Plaid API hostname
     *
     * @var  string
     */
    private $host = 'https://api.plaid.com/';

    /**
     * Construct the Plaid helper instance with our
     * authentication values.
     *
     * @param string $client_id
     * @param boolean $secret
     * @param string $testing
     */
    public function __construct($client_id, $secret, $testing = false)
    {
        $this->client_id = $client_id;
        $this->secret = $secret;

        $this->testing = $testing;

        if ($this->testing) {
            $this->host = 'https://sandbox.plaid.com/';
        }

        \Unirest\Request::defaultHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);
    }

    /**
     * Get the stripe bank account information
     * from the public token.
     *
     * @param  string $public_token
     * @param  string $account_id
     * @return string
     */
    public function getStripeBankAccount($public_token, $account_id)
    {
        $access_token = $this->getAccessToken($public_token);

        $response = $this->requestEndpoint(\Unirest\Method::POST, 'processor/stripe/bank_account_token/create', [
            'access_token' => $access_token,
            'account_id'   => $account_id,
        ]);

       if ($response->code != 200 || !isset($response->body->stripe_bank_account_token)) {
            throw new PlaidException($response, 'Error attempting to exchange access_token for Stripe bank_account_token: ' . $response->raw_body);
        }

        return $response->body->stripe_bank_account_token;
    }

    /**
     * Make a request to an endpoint.
     *
     * @param  string $method
     * @param  string $endpoint
     * @param  array $body
     * @param  array $headers
     * @return \Unirest\Response
     */
    protected function requestEndpoint($method, $endpoint, $body = '', $headers = [])
    {
        $body = array_merge($this->getRequestAuthDetails(), $body);

        return \Unirest\Request::send(
            $method,
            $this->buildUrl($endpoint),
            json_encode($body),
            $headers
        );
    }

    /**
     * Get an access token from a public token.
     *
     * @param  string $public_token
     * @return string
     */
    protected function getAccessToken($public_token)
    {
        $response = $this->requestEndpoint(\Unirest\Method::POST, 'item/public_token/exchange', [
            'public_token' => $public_token,
        ]);

        if ($response->code != 200 || !isset($response->body->access_token)) {
            throw new PlaidException($response, 'Error attempting to exchange public_token for access token: ' . $response->raw_body);
        }

        return $response->body->access_token;
    }

    /**
     * Get the auth details ready for the request.
     *
     * @return array
     */
    private function getRequestAuthDetails()
    {
        return [
            'client_id' => $this->client_id,
            'secret'    => $this->secret,
        ];
    }

    /**
     * Build the url with the endpoint
     *
     * @param  string $endpoint
     * @return string
     */
    private function buildUrl($endpoint)
    {
        return $this->host . $endpoint;
    }

    /**
     * Are we accessing the sandbox?
     * 
     * @return boolean
     */
    public function isSandbox()
    {
        return $this->testing;
    }
}
