<?php
require_once 'ext/Slim/Slim/Slim.php';
require_once 'lib/OAuth/AuthorizationServer.php';

$app = new Slim(array(
    // we need to disable Slim's session handling due to incompatibilies with
    // simpleSAMLphp sessions
    'session.handler' => null,
    'debug' => false
));

$app->error(function ( Exception $e ) use ($app) {
    switch(get_class($e)) {
        case "VerifyException":
            // the request for the resource was not valid, tell client
            list($error, $description) = explode(":", $e->getMessage());
            $app->response()->header('WWW-Authenticate', 'realm="remoteStorage API",error="' . $error . '",error_description="' . $description . '"');
            $app->response()->status(401);
            break;
        case "OAuthException":
            // we cannot establish the identity of the client, tell user
            $app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "The identity of the application that tried to access this resource could not be established. Therefore we stopped processing this request. The message below may be of interest to the application developer."));
            break;
        case "AdminException":
            // the authenticated user wants to perform some operation that is 
            // privileged
            $app->render("errorPage.php", array ("error" => $e->getMessage(), "description" => "You are not authorized to perform this operation."), 403);
            break;
        default:
            $app->halt(500, $e->getMessage());
    }
});

$oauthConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "oauth.ini", TRUE);
$remoteStorageConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . "remoteStorage.ini", TRUE);

$oauthStorageBackend = $oauthConfig['OAuth']['storageBackend'];
require_once "lib/OAuth/$oauthStorageBackend.php";
$oauthStorage = new $oauthStorageBackend($oauthConfig[$oauthStorageBackend]);

$app->get('/oauth/:uid/authorize', function ($uid) use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if($resourceOwner !== $uid) {
        throw new OAuthException("$uid does not match $resourceOwner");
    }
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
    $result = $o->authorize($resourceOwner, $app->request()->get());
    // we know that all request parameters we used below are acceptable because they were verified by the authorize method.
    // Do something with case where no scope is requested!
    if($result['action'] === 'ask_approval') { 
        $client = $oauthStorage->getClient($app->request()->get('client_id'));
        $app->render('askAuthorization.php', array (
            'clientId' => $client->id,
            'clientName' => $client->name,
            'redirectUri' => $client->redirect_uri,
            'scope' => $app->request()->get('scope'), 
            'authorizeNonce' => $result['authorize_nonce'],
            'allowFilter' => $oauthConfig['OAuth']['allowResourceOwnerScopeFiltering']));
    } else {
        $app->redirect($result['url']);
    }
});

$app->post('/oauth/:uid/authorize', function ($uid) use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if($resourceOwner !== $uid) {
        throw new OAuthException("$uid does not match $resourceOwner");
    }
    $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
    $result = $o->approve($resourceOwner, $app->request()->get(), $app->request()->post());
    $app->redirect($result['url']);
});

$app->get('/oauth/revoke', function() use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $approvals = $oauthStorage->getApprovals($resourceOwner);
    $app->render('listApprovals.php', array( 'approvals' => $approvals));
});

$app->post('/oauth/revoke', function() use ($app, $oauthStorage, $oauthConfig) {
    // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id and 
    //        scope can remove an approval for any (authenticated) user by crafting
    //        a POST call to this endpoint. IMPACT: low risk, denial of service.

    // FIXME: we need to also remove the access tokens that are currently used
    //        by this service if the user wants this. Maybe we should have a 
    //        checkbox "terminate current access" or "keep current access
    //        tokens available for at most 1h"
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    $oauthStorage->deleteApproval($app->request()->post('client_id'), $resourceOwner, $app->request()->post('scope'));
    $approvals = $oauthStorage->getApprovals($resourceOwner);
    $app->render('listApprovals.php', array( 'approvals' => $approvals));
});

$app->get('/oauth/clients', function() use ($app, $oauthStorage, $oauthConfig) {
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if(!in_array($resourceOwner, $oauthConfig['OAuth']['adminResourceOwnerId'])) {
        throw new AdminException("not an administrator");
    }
    $registeredClients = $oauthStorage->getClients();
    $app->render('listClients.php', array( 'registeredClients' => $registeredClients));
});

$app->post('/oauth/clients', function() use ($app, $oauthStorage, $oauthConfig) {
    // FIXME: there is no "CSRF" protection here. Everyone who knows a client_id 
    //        can remove or add! an application by crafting a POST call to this 
    //        endpoint. IMPACT: low, XSS required, how to fake POST on other domain?
    $authMech = $oauthConfig['OAuth']['authenticationMechanism'];
    require_once "lib/OAuth/$authMech.php";
    $ro = new $authMech($oauthConfig[$authMech]);
    $resourceOwner = $ro->getResourceOwnerId();
    if(!in_array($resourceOwner, $oauthConfig['OAuth']['adminResourceOwnerId'])) {
        throw new AdminException("not an administrator");
    }
    
    // FIXME: should deal with deletion, new registrations, delete
    //        current access tokens?

});

$app->get('/:uid/:category/:name', function ($uid, $category, $name) use ($app, $oauthConfig, $remoteStorageConfig, $oauthStorage) {
    $app->response()->header("Access-Control-Allow-Origin", "*");

    if($category !== "public") {    
        $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
        $result = $o->verify($app->request());
        $absPath = $remoteStorageConfig['remoteStorage']['filesDirectory'] . DIRECTORY_SEPARATOR . 
                $result->resource_owner_id . DIRECTORY_SEPARATOR . 
                $category . DIRECTORY_SEPARATOR . 
                $name;
    } else {
        $absPath = $remoteStorageConfig['remoteStorage']['filesDirectory'] . DIRECTORY_SEPARATOR . 
                $uid . DIRECTORY_SEPARATOR . 
                "public" . DIRECTORY_SEPARATOR . 
                $name;
    }

    // user directory
    if(!file_exists(dirname(dirname($absPath)))) {
        if (@mkdir(dirname(dirname($absPath)), 0775) === FALSE) {
            $app->halt(500, "Unable to create directory");
        }
    }

    // category directory
    if(!file_exists(dirname($absPath))) {
        if (@mkdir(dirname($absPath), 0775) === FALSE) {
            $app->halt(500, "Unable to create directory");
        }
    }

    if(!file_exists($absPath) || !is_file($absPath)) {
        $app->halt(404, "File Not Found");
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $app->response()->header("Content-Type", $finfo->file($absPath));
    echo file_get_contents($absPath);
});

$app->put('/:uid/:category/:name', function ($uid, $category, $name) use ($app, $oauthConfig, $remoteStorageConfig, $oauthStorage) {
     $app->response()->header("Access-Control-Allow-Origin", "*");
     $o = new AuthorizationServer($oauthStorage, $oauthConfig['OAuth']);
     $result = $o->verify($app->request());
     $absPath = $remoteStorageConfig['remoteStorage']['filesDirectory'] . DIRECTORY_SEPARATOR . $result->resource_owner_id . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $name;

    // user directory
    if(!file_exists(dirname(dirname($absPath)))) {
        if (@mkdir(dirname(dirname($absPath)), 0775) === FALSE) {
            $app->halt(500, "Unable to create directory");
        }
    }

    // category directory
    if(!file_exists(dirname($absPath))) {
        if (@mkdir(dirname($absPath), 0775) === FALSE) {
            $app->halt(500, "Unable to create directory");
        }
    }
    file_put_contents($absPath, $app->request()->getBody());
});

$app->delete('/:uid/:category/:name', function ($uid, $category, $name) use ($app, $oauthConfig, $remoteStorageConfig, $oauthStorage) {
    echo "DELETE /var/www/html/storage/$category/$name";
});

$app->options('/:uid/:category/:name', function($uid, $category, $name) use ($app) {
    $app->response()->header('Access-Control-Allow-Origin', $app->request()->headers('Origin'));
    $app->response()->header('Access-Control-Allow-Methods','GET, PUT, DELETE');
    $app->response()->header('Access-Control-Allow-Headers','content-length, authorization');
});

$app->get('/lrdd/', function() use ($app) {
    $subject = $app->request()->get('uri');
    list($x,$userAddress) = explode(":", $subject);
    
    // FIXME: too bad there is no helper function to get the RootUri including domain
    $baseUri = Slim_Http_Uri::getScheme() . "://" . $_SERVER['HTTP_HOST'] . $app->request()->getRootUri();
    $authUri = $baseUri . "/oauth/$userAddress/authorize";
    $templateUri = $baseUri . "/$userAddress/{category}/";
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $app->response()->header("Content-Type", "application/xrd+xml; charset=UTF-8");
    $app->render('webFinger.php', array ( 'subject' => $subject, 'templateUri' => $templateUri, 'authUri' => $authUri));
});

$app->run();

?>
