<!DOCTYPE html>

<html lang="en">
<head>
  <title>Authorization</title>
  <script src="../templates/askAuthorization.js" type=
  "text/javascript">
</script>
  <link rel="stylesheet" type="text/css" href=
  "../templates/default.css">
</head>

<body>
  <div id="container">
    <form method="post" action="">
      <h3><?php echo $serviceName; ?></h3>

      <p><strong><?php echo $clientName; ?></strong> wants to
      access your
      <strong><?php echo $serviceResources; ?></strong>.</p>

      <table id="detailsTable">
        <tr>
          <th>Application Identifier</th>

          <td><?php echo $clientId; ?></td>
        </tr>

        <tr>
          <th>Description</th>

          <td><span><?php echo $clientDescription; ?></span></td>
        </tr>

        <tr>
          <th>Requested Permission(s)</th>

          <td>
            <?php if($allowFilter) { ?><?php foreach($scope as $s) { ?><label><input type="checkbox"
            checked="checked" name="scope[]" value=
            "<?php echo $s; ?>"> <?php echo $s; ?></label>
            <?php } ?> <?php if($allowFilter) { ?>

            <div>
              By removing permissions, the application may not work
              as expected!
            </div><?php } ?><?php } else { ?>

            <ul>
              <?php foreach($scope as $s) { ?>

              <li><?php echo $s; ?></li>

              <li style="list-style: none"><input type="hidden"
              name="scope[]" value="<?php echo $s; ?>">
              <?php } ?></li>
            </ul><?php } ?>
          </td>
        </tr>

        <tr>
          <th>Redirect URI</th>

          <td><?php echo $clientRedirectUri; ?></td>
        </tr>
      </table><button id="showDetails" type=
      "button">Details</button> <input type="submit" class=
      "formButton" name="approval" value="Approve"> <input type=
      "submit" class="formButton" name="approval" value="Reject">
    </form>
  </div><!-- /container -->
</body>
</html>
