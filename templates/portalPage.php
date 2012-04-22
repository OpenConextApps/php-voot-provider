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
          <a class="brand" href="#">Demo Portal</a>
          <div class="nav-collapse">
            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
             <!-- <li><a href="#about">About</a></li>
              <li><a href="#contact">Contact</a></li> -->
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container">

      <h1>Demo Portal</h1>
      <p>Welcome <strong><?php echo $resourceOwner; ?></strong>. This is the Demo Portal for Web Applications.<br> On this page you can find selected applications that use data storage provided by this service, leaving <strong>you</strong> in charge of your data.</p>

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
                        <a href="<?php echo $r['redirect_uri'] . $appLaunchFragment; ?>">Launch</a>
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

