<?php

class HttpBearerAuthException extends Exception {

}

class HttpBearerAuth extends Slim_Middleware {

    private $_verificationEndpoint;
    private $_realm;

    public function __construct($verificationEndpoint, $realm = 'Protected Area' ) {
        $this->_verificationEndpoint = $verificationEndpoint;
        $this->_realm = $realm;
    }

    public function call() {
        try { 
            $req = $this->app->request();
            $res = $this->app->response();
            $ah = $req->headers('X-Authorization');

            $b64TokenRegExp = '(?:[[:alpha:][:digit:]-._~+/]+=*)';
            $result = preg_match('|^Bearer (?P<value>' . $b64TokenRegExp . ')$|', $ah, $matches);
            if($result === FALSE || $result === 0) {
                // FIXME: error handling!
                throw new HttpBearerAuthException("the access token is malformed");
            }
            $accessToken = $matches['value'];

            $ch = curl_init();
            $post = array("token" => $accessToken, "grant_type" => "urn:pingidentity.com:oauth2:grant_type:validate_bearer");
            curl_setopt($ch, CURLOPT_URL, $this->_verificationEndpoint);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Authorization: Basic ABCDEF"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

            // grab URL and pass it to the browser
            $data = curl_exec($ch);
            if(FALSE === $data) {
                // FIXME: error handling!
                error_log(curl_error($c));
                throw new HttpBearerAuthException("unable to verify the access token");
            }

            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if(200 !== $code) {
                // FIXME: error handling!
                error_log("error: " . $data);
                throw new HttpBearerAuthException("the access token is invalid");
            }
            curl_close($ch);
            $d = json_decode($data, TRUE);

            // add access_token to the environment
            $env = $this->app->environment();
            $env['oauth.token'] = $d;

            // Call next middleware
            $this->next->call();

        } catch (HttpBearerAuthException $e) {
            $res->status(401);
            $res->header('WWW-Authenticate', sprintf('Bearer realm="%s"', $this->_realm));
        }
    }
}
