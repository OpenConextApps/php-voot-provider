<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>My Portal</title>
  <style>
    body { font-size: 90%; font-family: sans-serif; width: 90%; margin: 20px auto; border: 1px solid #000; padding: 10px; border-radius: 10px; }
    h2 { text-align: center; }
    th { text-align: left; font-weight: normal; }
    td { font-weight: bold; }
    td.note { text-align: center; color: #555; }
    table { border: 1px solid #000; width: 100%; background-color: #ddd; border-radius: 10px; }
    td,th { padding: 5px; }
    p.footer { text-align: right; }
  </style>
</head>

<body>
  <h2>My Unhosted Portal</h2>
    <p>Hello <strong><?php echo $resourceOwner; ?></strong></p>
    <table>
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
</body>
</html>
