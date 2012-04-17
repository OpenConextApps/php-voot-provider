<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Authorization Request</title>
  <style>
    body { font-size: 90%; font-family: sans-serif; width: 400px; margin: 20px auto; border: 1px solid #000; padding: 10px; border-radius: 10px; }
    h2 { text-align: center; }
    th { text-align: left; vertical-align: top; }
    table { border: 1px solid #000; width: 400px; background-color: #ddd; border-radius: 10px; }
    td,th { padding: 5px; }
    ul { margin-left: 0; }
    label { display: block; }
  </style>
</head>

<body>
  <h2>Authorization Request</h2>

  <p><?php echo $protectedResourceDescription; ?></p>

  <form method="post" action="">

    <table>
        <tr><th>Application</th><td><span title="<?php echo $clientId; ?>"><?php echo $clientName; ?></span></td></tr>
        <tr><th>Requested Permission(s)</th>
            <td>
            <?php if($allowFilter) { ?>

                <?php foreach(AuthorizationServer::normalizeScope($scope, TRUE) as $s) { ?>
                    <label><input type="checkbox" checked="checked" name="scope[]" value="<?php echo $s; ?>"> <?php echo $s; ?></label>
                <?php } ?>

            <?php } else { ?>

                <ul>
                <?php foreach(AuthorizationServer::normalizeScope($scope, TRUE) as $s) { ?>
                    <li><?php echo $s; ?></li>
                    <input type="hidden" name="scope[]" value="<?php echo $s; ?>">
                <?php } ?>
                </ul>
            <?php } ?>
            </td>
        </tr>
    </table>

    <?php if($allowFilter) { ?>
        <p>You can either approve or reject this request and deselect some of the requested permissions.</p><p>Please note by doing this the application may not work as expected.</p>
    <?php } else { ?>
        <p>You can either approve or reject this request.</p>
    <?php } ?>

    <input type="submit" name="approval" value="Approve" />
    <input type="submit" name="approval" value="Reject" />
    <input type="hidden" name="authorize_nonce" value="<?php echo $authorizeNonce; ?>" />
  </form>
</body>
</html>
