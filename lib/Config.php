<?php

class Config {

    private $_instance;
    private $_config;
   
    public function __construct($configInstance) {
        $this->_instance = $configInstance;

        $configFile = "config" . DIRECTORY_SEPARATOR . $configInstance . ".ini";
        if(!file_exists($configFile) || !is_file($configFile) || !is_readable($configFile)) {
            throw new ConfigException("configuration file '$configFile' not found");
        }

        $this->_config = parse_ini_file($configFile, TRUE);
    }

    public function getValue($key, $required = TRUE) {
        if(array_key_exists($key, $this->_config)) {
            return $this->_config[$key];
        } else {
            if($required) {
                throw new ConfigException("configuration key '$key' not set in '$this->_instance'");
            }
            return NULL;
        }
    }

    public function getSectionValue($section, $key, $required = TRUE) {
        if(array_key_exists($section, $this->_config) && array_key_exists($key, $this->_config[$section])) {
            return $this->_config[$section][$key];
        } else {
            if($required) {
                throw new ConfigException("configuration key '$key' in sectoin '$section' not set in '$this->_instance'");
            }
            return NULL;
        }
    }

}

class ConfigException extends Exception {
}

?>
