<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="ext/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
    </style>
    <link href="ext/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">Portal</a>
          <div class="nav-collapse">
            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
              <!-- how to use pull-right on an element? -->
              <li><a href="#">Welcome <strong><?php echo $resourceOwner; ?></strong></a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container">

      <h1>Demo Portal</h1>
      <h2>My Installed Applications</h2>
    <p>Below is a list of applications that you approved and can access your data without requiring your authorization (again). Removing them revokes this permission and also removes all currently active access tokens.</p>
    <table class="table table-striped">
        <tr><th>Application</th><th>Description</th><th>Action</th></tr>
        <?php if(!empty($resourceOwnerApprovals)) { ?>
            <?php foreach ($resourceOwnerApprovals as $r) { ?>
                <tr>
                    <td>
                        <span title="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></a>
                    </td>
                    <td>
                        <?php echo $r['description']; ?>
                    </td>                    
                    <td>
                        <a class="btn btn-success" href="<?php echo $r['redirect_uri'] . $appLaunchFragment; ?>">Launch</a>
                        <a class="btn btn-inverse" href="#">Remove</a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td class="note" colspan="3"><small>No applications installed...</small></td></tr>
        <?php } ?>
    </table>

     <h2>Available Applications</h2>
<p>Installing these applications will create an OAuth approval for this particular client, removing them above will remove the approval (and possibly delete all current active access tokens).</p>
    <table class="table table-striped">
        <tr><th>Application</th><th>Description</th><th>Action</th></tr>
        <?php if(!empty($registeredClients)) { ?>
            <?php foreach ($registeredClients as $r) { ?>
                <tr>
                    <td>
                        <span title="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></a>
                    </td>
                    <td>
                        <?php echo $r['description']; ?>
                    </td>                    
                    <td>
                        <a class="btn btn-success" href="<?php echo $r['redirect_uri'] . $appLaunchFragment; ?>">Install</a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td class="note" colspan="3"><small>No applications available...</small></td></tr>
        <?php } ?>
    </table>



    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="ext/js/jquery.js"></script>
<!--    <script src="../assets/js/bootstrap-transition.js"></script>
    <script src="../assets/js/bootstrap-alert.js"></script>
    <script src="../assets/js/bootstrap-modal.js"></script>
    <script src="../assets/js/bootstrap-dropdown.js"></script>
    <script src="../assets/js/bootstrap-scrollspy.js"></script>
    <script src="../assets/js/bootstrap-tab.js"></script>
    <script src="../assets/js/bootstrap-tooltip.js"></script>
    <script src="../assets/js/bootstrap-popover.js"></script>
    <script src="../assets/js/bootstrap-button.js"></script>
    <script src="../assets/js/bootstrap-collapse.js"></script>
    <script src="../assets/js/bootstrap-carousel.js"></script>
    <script src="../assets/js/bootstrap-typeahead.js"></script> -->

  </body>
</html>

