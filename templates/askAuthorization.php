<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Authorization</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="../ext/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
      span.unregistered { color: red; font-weight: bold; }
      form { margin-bottom: 0; }
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

  <p><strong><?php echo $clientName; ?></strong> wants to access your <em><?php echo $serviceName; ?></em>.</p>

    <a class="btn btn-mini btn-info infoButton" href="#">Details...</a>

    <table class="table table-striped detailsTable" > 
        <tr><th>Application Identifier</th><td><?php echo $clientId; ?></td></tr>
        <tr><th>Description</th>
            <td>
                <?php if($clientName === "Unknown Client") { ?>
                    <span class="unregistered"><?php echo $clientDescription; ?></span>
                <?php } else { ?>
                    <span><?php echo $clientDescription; ?></span>
                <?php } ?>
            </td>
        </tr>
        <tr><th>Requested Permission(s)</th>
            <td>
            <?php if($allowFilter) { ?>

                <?php foreach(AuthorizationServer::normalizeScope($scope, TRUE) as $s) { ?>
                    <label><input type="checkbox" checked="checked" name="scope[]" value="<?php echo $s; ?>"> <?php echo $s; ?></label>
                <?php } ?>

                <?php if($allowFilter) { ?>
                    <p><div class="alert alert-info">
                        By removing permissions, the application may not work as expected!
                    </div></p>
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
        <tr><th>Redirect URI</th><td><?php echo $clientRedirectUri; ?></td></tr>
    </table>

      <!--  <p>You can either approve or reject this request.</p> -->

     </div>
            <input type="hidden" name="authorize_nonce" value="<?php echo $authorizeNonce; ?>" />

            <div class="modal-footer">
                <input type="submit" name="approval" class="btn" value="Reject">
                <input type="submit" name="approval" class="btn btn-primary" value="Approve">
            </div>
 </form>

</div>
    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="../ext/js/jquery.js"></script>
    <script src="../templates/askAuthorization.js"></script>


</body>
</html>
