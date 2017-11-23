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
     * What environment are we in?
     *
     * @var string
     */
    private $environment;

    /**
     * The Plaid API hostname
     *
     * @var  string
     */
    private $host = 'https://%s.plaid.com/';

    /**
     * The environments that are available to use.
     */
    const ENVIRONMENTS = ['sandbox', 'development', 'production'];

    /**
     * Construct the Plaid helper instance with our
     * authentication values.
     *
     * @param string $client_id
     * @param boolean $secret
     * @param string $environment
     */
    public function __construct($client_id, $secret, $environment = 'sandbox')
    {
        $this->client_id = $client_id;
        $this->secret = $secret;

        $this->environment = $environment;

        $this->generateEnvironmentHost();

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
     * Are we in a specific environment?
     *
     * @param  string  $environment
     * @return boolean
     */
    public function isEnvironment($environment)
    {
        return $this->environment == $environment;
    }

    /**
     * Are we in the sandbox env?
     *
     * @return boolean
     */
    public function isSandbox()
    {
        return $this->isEnvironment('sandbox');
    }

    /**
     * Are we in the development env?
     *
     * @return boolean
     */
    public function isDevelopment()
    {
        return $this->isEnvironment('development');
    }

    /**
     * Are we in the production env?
     *
     * @return boolean
     */
    public function isProduction()
    {
        return $this->isEnvironment('production');
    }

    /**
     * Grab the host we're using
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->host;
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
     * @throws PlaidException
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
     * Make our host url from our environment key. This will
     * throw a PlaidException if the environment which has been
     * passed into the instance is not valid.
     *
     * @return void
     * @throws PlaidException
     */
    protected function generateEnvironmentHost()
    {
        if (!in_array($this->environment, self::ENVIRONMENTS)) {
            throw new PlaidException(null, 'The environment you are trying to use is not valid: ' . $this->environment);
        }

        $this->host = sprintf($this->host, $this->environment);
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
}
