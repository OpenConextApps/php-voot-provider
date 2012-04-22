<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Authorized Applications</title>
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
  <h2>Authorized Applications</h2>

  <p>Here you can see the applications you authorized to access your data.</p>
    <table>
        <tr><th>Application</th><th>Permission(s)</th><th>Action</th></tr>
        <?php if(!empty($approvals)) { ?>
            <?php foreach ($approvals as $a) { ?>
                <tr>
                    <td>
                        <span title="<?php echo $a['id']; ?>"><?php echo $a['name']; ?></a></td><td><?php echo $a['scope']; ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="submit" value="Revoke" />
                            <input type="hidden" name="client_id" value="<?php echo $a['id']; ?>" />
                            <input type="hidden" name="scope" value="<?php echo $a['scope']; ?>" />
                        </form>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr><td class="note" colspan="3"><small>No applications authorized...</small></td></tr>
        <?php } ?>
    </table>
    <p>If you revoke authorization for an application, the next time an application wants to access your data you will again be asked for confirmation.</p>
</body>
</html>
