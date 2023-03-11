<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $email
 */
?>
<!-- register password forgotten -->
<form method="post" action="<?=$actionUrl?>" target="_self">
  <table class="regi_register">
    <tr>
      <td><input type="hidden" name="action" value="forgotten_password"><?=$this->text('email')?></td>
      <td><input class="text" name="email" type="email" size="35" value="<?=$email?>"></td>
      <td><button class="submit" value="submit"><?=$this->text('send')?></button></td>
    </tr>
  </table>
</form>
