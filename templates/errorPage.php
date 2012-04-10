<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Error</title>
  <style>
    body { font-size: 90%; font-family: sans-serif; width: 400px; margin: 20px auto; border: 1px solid #000; padding: 10px; border-radius: 10px; }
    h2 { text-align: center; color: red; }
    p.error { background-color: #ddd; color: red; padding: 10px; border: 1px solid #000; border-radius: 10px; }
  </style>
</head>

<body>
  <h2>Error</h2>

  <p><?php echo $description; ?></p>

  <p class="error"><?php echo $error; ?></p>

</body>
</html>
