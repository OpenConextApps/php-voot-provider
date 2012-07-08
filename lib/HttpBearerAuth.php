<?php
/**
 * HTTP Bearer Authentication
 *
 * Use this middleware with your Slim Framework application
 * to require HTTP bearer auth for all routes.
 *
 * @author François Kooman <fkooman@tuxed.net>
 * @version 1.0
 * @copyright 2012 François Kooman
 *
 * USAGE
 *
 * $app = new Slim();
 * $app->add(new HttpBearerAuth('https://server/oauth/token','user','pass'));
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
class HttpBearerAuth extends Slim_Middleware {
    /**
     * @var string
     */
    protected $realm;

    /**
     * Constructor
     *
     * @param   string  $realm      The HTTP Authentication realm
     * @return  void
     */
    public function __construct( $tokenEndpoint, $user, $pass, $realm = 'Protected Area' ) {
        $this->tokenEndpoint = $tokenEndpoint;
        $this->user = $user;
        $this->pass = $pass;
        $this->realm = $realm;
    }

    /**
     * Call
     *
     * This method will check the HTTP request headers for previous authentication. If
     * the request has already authenticated, the next middleware is called. Otherwise,
     * a 401 Authentication Required response is returned to the client.
     *
     * @return void
     */
    public function call() {
        $req = $this->app->request();
        $res = $this->app->response();
        $ah = $req->headers('X-Authorization');

        // FIXME: do curl validation at token endpoint with user,pass and bearer token

        // FIXME: how to validate scope???
        // probably add the scope to the environment and create another middleware thingy to check the scope
        // to see if it is allowed? 
        if ( $authUser && $authPass && $authUser === $this->username && $authPass === $this->password ) {
            $this->next->call();
        } else {
            $res->status(401);
            $res->header('WWW-Authenticate', sprintf('Bearer realm="%s"', $this->realm));
        }
    }
}
