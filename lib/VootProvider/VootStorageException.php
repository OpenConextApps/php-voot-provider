<?php

namespace VootProvider;

class VootStorageException extends \Exception
{
    private $_description;

    public function __construct($message, $description, $code = 0, Exception $previous = null)
    {
        $this->_description = $description;
        parent::__construct($message, $code, $previous);
    }

    public function getDescription()
    {
        return $this->_description;
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

    public function getLogMessage($includeTrace = FALSE)
    {
        $msg = 'Message    : ' . $this->getMessage() . PHP_EOL .
               'Description: ' . $this->getDescription() . PHP_EOL;
        if ($includeTrace) {
            $msg .= 'Trace      : ' . PHP_EOL . $this->getTraceAsString() . PHP_EOL;
        }

        return $msg;
    }

}
