<?php

class Config {

    private $_configFile;
    private $_configValues;
   
    public function __construct($configFile) {
        $this->_configFile = $configFile;

        if(!file_exists($configFile) || !is_file($configFile) || !is_readable($configFile)) {
            throw new ConfigException("configuration file '$configFile' not found");
        }

        $this->_configValues = parse_ini_file($configFile, TRUE);
    }

    public function getValue($key, $required = TRUE) {
        if(array_key_exists($key, $this->_configValues)) {
            return $this->_configValues[$key];
        } else {
            if($required) {
                throw new ConfigException("configuration key '$key' not set in '$this->_configFile'");
            }
            return NULL;
        }
    }

    public function getSectionValue($section, $key, $required = TRUE) {
        if(array_key_exists($section, $this->_configValues) && array_key_exists($key, $this->_configValues[$section])) {
            return $this->_configValues[$section][$key];
        } else {
            if($required) {
                throw new ConfigException("configuration key '$key' in section '$section' not set in '$this->_configFile'");
            }
            return NULL;
        }
    }

    public function setValue($key, $value) {
        $this->_configValues[$key] = $value;
    }

    public function setSectionValue($section, $key, $value) {
        $this->_configValues[$section][$key] = $value;
    }

}

class ConfigException extends Exception {
}

?>
