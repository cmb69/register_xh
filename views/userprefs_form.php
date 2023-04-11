<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $token
 * @var list<array{string}> $errors
 * @var string $name
 * @var string $email
 */
?>
<!-- register user preferences -->
<div class="regi_settings">
  <form method="post" action="<?=$actionUrl?>" target="_self">
    <input type="hidden" name="register_token" value="<?=$token?>">
<?foreach ($errors as $error):?>
    <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
    <table style="margin: auto;">
      <tr>
        <td><?=$this->text('name')?></td>
        <td><input class="text" name="name" type="text" size="35" value="<?=$name?>"></td>
      </tr>
      <tr>
        <td><?=$this->text('oldpassword')?></td>
        <td><input class="text" name="oldpassword" type="password" size="10" value=""></td>
      </tr>
      <tr>
        <td><?=$this->text('password')?></td>
        <td><input class="text" name="password1" type="password" size="10" value=""></td>
      </tr>
      <tr>
        <td><?=$this->text('password2')?></td>
        <td><input class="text" name="password2" type="password" size="10" value=""></td>
      </tr>
      <tr>
        <td><?=$this->text('email')?></td>
        <td><input class="text" name="email" type="email" size="35" value="<?=$email?>"></td>
      </tr>
      <tr>
        <td colspan="2">
          <button class="submit" name="register_action" value="change_prefs"><?=$this->text('change')?></button>
          <button class="submit" name="register_action" value="unregister"><?=$this->text('user_delete')?></button>
        </td>
      </tr>
    </table>
  </form>
</div>
