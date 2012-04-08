<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Authorization Request</title>
  <style>
    body { font-size: 90%; font-family: sans-serif; width: 400px; margin: 20px auto; border: 1px solid #000; padding: 10px; border-radius: 10px; }
    h2 { text-align: center; }
    th { text-align: left; font-weight: normal; }
    td { font-weight: bold; color: red; }
    table { border: 1px solid #000; width: 400px; background-color: #ddd; border-radius: 10px; }
    td,th { padding: 5px; }
  </style>
</head>

<body>
  <h2>Authorization Request</h2>

  <p>An application wants to access your group membership details.</p>
    <table>
        <tr><th>Application</th><td><?php echo $clientName; ?></td></tr>
        <tr><th>Requested Permission(s)</th><td><?php echo $scope; ?></td></tr>
    </table>

  <p>You can either approve or reject this request.</p>

  <form method="post" action="">
    <input type="submit" name="approval" value="Approve" />
    <input type="submit" name="approval" value="Reject" />
    <input type="hidden" name="authorize_nonce" value="<?php echo $authorizeNonce; ?>" />
  </form>
</body>
</html>
