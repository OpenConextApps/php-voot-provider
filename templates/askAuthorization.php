<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>OAuth Management Interface</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="../ext/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
    </style>
    <link href="../ext/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="container">


    <div class="modal"> 
  <form method="post" class="form-horizontal">

   <div class="modal-header">
                <h3>Authorization Request</h3>
            </div>
            <div class="modal-body">

  <p><?php echo $protectedResourceDescription; ?></p>


    <table class="table table-striped"> 
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
        <p>You can either approve or reject this request and deselect some of the requested permissions. <strong>Please note by removing permissions the application may not work as expected</strong>.</p>
    <?php } else { ?>
        <p>You can either approve or reject this request.</p>
    <?php } ?>

     </div>
            <div class="modal-footer">
    <input type="hidden" name="authorize_nonce" value="<?php echo $authorizeNonce; ?>" />

                <input type="submit" name="approval" class="btn" value="Reject">
                <input type="submit" name="approval" class="btn btn-primary" value="Approve">
            </div>
 </form>

</div>
    </div> <!-- /container -->

</body>
</html>
