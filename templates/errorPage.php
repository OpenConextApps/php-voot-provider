<!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Error</title>
  <link rel="stylesheet" type="text/css" href=
  "../templates/default.css">
</head>

<body>
  <div id="container">
    <h3>Error</h3>

    <p><?php echo $description; ?></p>

    <div class="alertBox">
      <?php echo $error; ?>
    </div>
  </div>
</body>
</html>
