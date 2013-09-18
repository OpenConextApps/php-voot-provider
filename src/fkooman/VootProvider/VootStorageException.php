<?php

namespace fkooman\VootProvider;

class VootStorageException extends \Exception
{
    private $description;

    public function __construct($message, $description, $code = 0, Exception $previous = null)
    {
        $this->description = $description;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getResponseCode()
    {
        switch ($this->message) {
            case "not_found":
                return 404;
            case "ldap_error":
            case "internal_server_error":
                return 500;
            default:
                return 400;
        }
    }
}
