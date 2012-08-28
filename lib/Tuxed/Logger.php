<?php

namespace Tuxed;

class Logger {

    private $_logLevel;
    private $_appName;
    private $_logFile;
    private $_sendMail;

    public function __construct($logLevel, $appName, $logFile, $sendMail = NULL) {
        if(!in_array($logLevel, array(100, 200, 300, 400))) {
            $logLevel = 300;
        }
        $this->_logLevel = $logLevel;
        $this->_appName = $appName;
        $this->_logFile = $logFile;
        if(NULL === $sendMail || !is_array($sendMail)) {
            $this->_sendMail = array();
        } else {
            $this->_sendMail = $sendMail;
        }
    }

    public function logMessage($level, $message) {
        if($level < $this->_logLevel) {
            return;
        }
        switch($level) {
            case 100:
                $logLevel = "[DEBUG]  ";
                break;
            case 200:
                $logLevel = "[INFO]   ";
                break;
            case 300:
                $logLevel = "[WARNING]";
                break;
            case 400:
                $logLevel = "[FATAL]  ";
                break;
            default:
                $logLevel = "[DEFAULT]";
                break;
        }

        // write log message
        $logMessage = "[" . $this->_appName . "] " . date("c") . " " . $logLevel . " " . $message . PHP_EOL;
        if(FALSE === file_put_contents($this->_logFile, $logMessage, FILE_APPEND | LOCK_EX)) {
            throw new LoggerException("unable to write to log file");
        }

        // send out mail
        foreach($this->_sendMail as $v) {
            if(NULL !== $v && !empty($v)) {
                $mailSubject = "(" . microtime(TRUE) . ") [" . $this->_appName . "] " . $logLevel . " " . substr(strtok($message, PHP_EOL), 0, 50);
                $mailBody = $message;
                if(FALSE === mail($v, $mailSubject, $mailBody)) {
                    // silently ignore for now... better no mail than breakage
                    // throw new LoggerException("unable to mail log entry");
                }
            }
        }
    }

    public function logDebug($message) {
        $this->logMessage(100, $message);
    }

    public function logInfo($message) {
        $this->logMessage(200, $message);
    }

    public function logWarn($message) {
        $this->logMessage(300, $message);
    }

    public function logFatal($message) {
        $this->logMessage(400, $message);
    }

}
