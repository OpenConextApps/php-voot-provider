<?php

session_start();

if("POST" === $_SERVER['REQUEST_METHOD']) {
    // form submitted
    $_SESSION['client_id'] = $_POST['client_id'];
    $_SESSION['secret'] = $_POST['secret'];
    $_SESSION['authorize_endpoint'] = $_POST['authorize_endpoint'];
    $_SESSION['token_endpoint'] = $_POST['token_endpoint'];
    $_SESSION['scope'] = $_POST['scope'];
    $_SESSION['api_endpoint'] = $_POST['api_endpoint'];
    $_SESSION['redirect_uri'] = $_POST['redirect_uri'];

    $httpLocation = sprintf("Location: %s?client_id=%s&response_type=code&scope=%s&redirect_uri=%s", $_SESSION['authorize_endpoint'], $_SESSION['client_id'], $_SESSION['scope'], urlencode($_SESSION['redirect_uri']));
    header($httpLocation);

} else if("GET" === $_SERVER['REQUEST_METHOD'] && array_key_exists("code", $_GET)) {
    // call back from OAuth AS

    $auth = base64_encode($_SESSION['client_id'] . ":" . $_SESSION['secret']);
    $ch = curl_init();
    $post = array("code" => $_GET['code'], "grant_type" => "authorization_code", "redirect_uri" => $_SESSION['redirect_uri']);
    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, $_SESSION['token_endpoint']);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Authorization: Basic $auth"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);


    // grab URL and pass it to the browser
    $data = curl_exec($ch);
    if(FALSE === $data) {
        die(curl_error($ch));
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($code !== 200) {
        die($data);
    }
    // close cURL resource, and free up system resources
    curl_close($ch);

    $d = json_decode($data, TRUE);
    $accessToken = $d['access_token'];
 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $_SESSION['api_endpoint']);
    curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Authorization: Bearer $accessToken"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    // grab URL and pass it to the browser
    $data = curl_exec($ch);
    if(FALSE === $data) {
        die(curl_error($ch) . "<br>" . $data);
    }
    // close cURL resource, and free up system resources
    curl_close($ch);

    header("Content-Type: application/json");
    echo $data;

} else {
    // clean call, show form
?>
<html>
<head>
<title>OAuth Client</title>
<style>
label { display: block; font-size: small; font-weight: bold; margin: 10px;}
input { display: block; }
</style>
</head>
<body>
<h1>OAuth Client</h1>
<form method="post">

    <label>client_id <input size="50" type="text" name="client_id" value="webapp"></label>
    <label>secret <input size="50" type="text" name="secret" value="s3cr3t"></label>
    <label>authorize_endpoint <input size="50" type="text" name="authorize_endpoint" value="http://localhost/phpvoot/oauth/authorize"></label>
    <label>token_endpoint <input size="50" type="text" name="token_endpoint" value="http://localhost/phpvoot/oauth/token"></label>
    <label>api_endpoint <input size="50" type="text" name="api_endpoint" value="http://localhost/phpvoot/oauth/userinfo"></label>

    <label>scope <input size="50" type="text" name="scope" value="read oauth_userinfo"></label>
    <label>redirect_uri <input size="50" type="text" name="redirect_uri" value="http://localhost/phpvoot/web/index.php"></label>
    <input type="submit" value="Go">
</form>
</body>
</html>
<?php
}
?>
