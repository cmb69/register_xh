<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $actionUrl
 * @var list<array{string}> $errors
 * @var string $email
 */
?>
<!-- register password forgotten -->
<form method="post" action="<?=$actionUrl?>" target="_self">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <table class="regi_register">
    <tr>
      <td><?=$this->text('email')?></td>
      <td><input class="text" name="email" type="email" size="35" value="<?=$email?>"></td>
      <td><button class="submit" name="register_action" value="forgot_password"><?=$this->text('send')?></button></td>
    </tr>
  </table>
</form>
