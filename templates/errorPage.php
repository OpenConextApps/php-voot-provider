<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Error</title>
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

   <div class="modal-header">
     <h3>Error</h3>
   </div>

   <div class="modal-body">
        <p><?php echo $description; ?></p>

        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>

    </div>
   </div>

</div>

</body>
</html>
