<?php

class Random {

    public static function hex($len = 16) {
        $randomString = bin2hex(openssl_random_pseudo_bytes($len, $strong));
        // @codeCoverageIgnoreStart
        if ($strong === FALSE) {
            throw new Exception("unable to securely generate random string");
        }
        // @codeCoverageIgnoreEnd
        return $randomString;
    }

    public static function base64($len = 16) {
        return base64_encode(self::hex($len));
    }
}

?>
