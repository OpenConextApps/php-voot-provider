<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Registered Applications</title>
  <style>
    body { font-size: 90%; font-family: sans-serif; width: 400px; margin: 20px auto; border: 1px solid #000; padding: 10px; border-radius: 10px; }
    h2 { text-align: center; }
    th { text-align: left; font-weight: normal; }
    td { font-weight: bold; color: red; }
    td.note { text-align: center; color: #555; }
    table { border: 1px solid #000; width: 400px; background-color: #ddd; border-radius: 10px; }
    td,th { padding: 5px; }
    p.footer { text-align: right; }
  </style>
</head>

<body>
  <h2>Registered Applications</h2>

  <p>Here you can see the applications that are registered to access this service.</p>
    <table>
        <tr><th>Application</th><th>Type</th><th>Action</th></tr>
        <?php if(!empty($registeredClients)) { ?>
            <?php foreach ($registeredClients as $r) { ?>
                <tr>
                    <td>
                        <span title="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></a>
                    </td>
                    <td>
                        <?php echo $r['type']; ?>
                    </td>                    
                    <td>
                        <form method="post" action="">
                            <input type="submit" value="Delete" />
                            <input type="hidden" name="client_id" value="<?php echo $r['id']; ?>" />
                        </form>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td class="note" colspan="3"><small>No applications registered...</small></td></tr>
        <?php } ?>
    </table>
    <p>If you delete an application, the application can no longer access any data for any of the users that gave permission for this.</p>
</body>
</html>
