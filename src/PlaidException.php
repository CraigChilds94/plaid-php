<?php 

namespace Plaid;

class PlaidException extends \Exception
{
    public $response;

    public function __construct($response, $message)
    {
        parent::__construct($message, $response != null ? $response->code : 500);

        $this->response = $response;
    }
}