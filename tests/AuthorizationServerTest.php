<?php

require_once 'lib/Config.php';
require_once 'lib/OAuth/AuthorizationServer.php';
require_once 'lib/OAuth/ResourceServer.php';
require_once 'lib/OAuth/PdoOAuthStorage.php';
require_once 'lib/OAuth/DummyResourceOwner.php';

class ImplicitGrantTest extends PHPUnit_Framework_TestCase {

    private $_tmpDb;
    private $_ro;
    private $_as;
    private $_rs;
    private $_storage;

    public function setUp() {
        $this->_tmpDb = tempnam(sys_get_temp_dir(), "oauth_");
        if(FALSE === $this->_tmpDb) {
            throw new Exception("unable to generate temporary file for database");
        }
        $dsn = "sqlite:" . $this->_tmpDb;
        // load DB scheme
        // NOT EASY, aargh!

        // load default config
        $c = new Config(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "oauth.ini.defaults");

        // override DB config in memory only
        $c->setValue("storageBackend", "PdoOAuthStorage");
        $c->setSectionValue("PdoOAuthStorage", "dsn", $dsn);

        // intialize storage
        $this->_storage = new PdoOAuthStorage($c);
        
        $this->_storage->initDatabase();
        $this->_storage->updateDatabase();

        // add a client
        $uaba = array("id" => "testclient",
                  "name" => "Simple Test Client",
                  "description" => "Client for unit testing",
                  "secret" => NULL,
                  "redirect_uri" => "http://localhost/phpvoot/unit/test.html",
                  "type" => "user_agent_based_application");

        $wa = array ("id" => "testcodeclient",
                  "name" => "Simple Test Client for Authorization Code Profile",
                  "description" => "Client for unit testing",
                  "secret" => "abcdef",
                  "redirect_uri" => "http://localhost/phpvoot/unit/test.html",
                  "type" => "web_application");
        $this->_storage->addClient($uaba);
        $this->_storage->addClient($wa);

        // initialize authorization server
        $this->_as = new AuthorizationServer($this->_storage, $c);
        $this->_rs = new ResourceServer($this->_storage, $c);
        $this->_ro = new DummyResourceOwner($c);
    }

    public function tearDown() {
        unlink($this->_tmpDb);
    }

    public function testImplicitGrant() {
        // now we ask the authorize endpoint
        $get = array("client_id" => "testclient", 
                     "response_type" => "token",
                     "scope" => "read");
        $response = $this->_as->authorize($this->_ro, $get);
        $action = $response['action'];
        $client = $response['client'];

        $this->assertEquals("ask_approval", $action);
        $this->assertEquals("testclient", $client->id);

        // now we approve
        $post = array("approval" => "Approve", "scope" => array("read"));
        $response = $this->_as->approve($this->_ro, $get, $post);

        $action = $response['action'];
        $url = $response['url'];

        $this->assertEquals("redirect", $action);
        // regexp match to deal with random access token
        $this->assertRegExp('|^http://localhost/phpvoot/unit/test.html#access_token=[a-zA-Z0-9]+&expires_in=3600&token_type=bearer&scope=read$|', $url);
    }

    public function testAuthorizationCode() {
        $get = array("client_id" => "testcodeclient", 
                     "response_type" => "code",
                     "scope" => "read");
        $response = $this->_as->authorize($this->_ro, $get);
        $action = $response['action'];
        $client = $response['client'];

        $this->assertEquals("ask_approval", $action);
        $this->assertEquals("testcodeclient", $client->id);

        // now we approve
        $post = array("approval" => "Approve", "scope" => array("read"));
        $response = $this->_as->approve($this->_ro, $get, $post);

        $action = $response['action'];
        $url = $response['url'];

        $this->assertEquals("redirect", $action);
        // regexp match to deal with random authorization code
        $this->assertRegExp('|^http://localhost/phpvoot/unit/test.html\?code=[a-zA-Z0-9]+$|', $url);

        preg_match('|^http://localhost/phpvoot/unit/test.html\?code=([a-zA-Z0-9]+)$|', $url, $matches);
        
        // exchange code for token
        
        $ah = "Basic " . base64_encode("testcodeclient:abcdef");
        $post = array ("grant_type" => "authorization_code",
                       "code" => $matches[1]);
        $response = $this->_as->token($post, $ah);

        $this->assertRegExp('|^[a-zA-Z0-9]+$|', $response->access_token);
        $this->assertEquals(3600, $response->expires_in);
        $this->assertRegExp('|^[a-zA-Z0-9]+$|', $response->refresh_token);
        // .. redirect_uri, scope
    }

}
?>


