<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta name="generator" content=
  "HTML Tidy for Linux/x86 (vers 25 March 2009), see www.w3.org" />

  <title>Authorization</title>
</head>

<body>
  <h2>Authorization Requested</h2>

  <p>The application <strong><?php echo $clientId; ?></strong> wants access to your
  group membership details with the following permissions:</p>

  <?php if(NULL !== $scope){ ?>
  <ul>
    <?php foreach(AuthorizationServer::normalizeScope($scope, TRUE) as $s) { ?>
    <li><?php echo $s; ?></li>
    <?php } ?>
  </ul>You can either approve or reject the request.
  <?php } ?>

  <form method="post" action="">
    <input type="submit" name="approval" value="Approve" />
    <input type="submit" name="approval" value="Deny" />
    <input type="hidden" name="authorize_nonce" value=
    "<?php echo $authorizeNonce; ?>" />
  </form>
</body>
</html>
