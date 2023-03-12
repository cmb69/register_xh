<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $url
 */
?>
<!-- register change password -->
<form class="register_change_password" action="<?=$url?>" method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <p>
    <label>
      <span><?=$this->text('password')?></span>
      <input name="password1" type="password" value="" required>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('password2')?></span>
      <input name="password2" type="password" value="" required>
    </label>
  </p>
  <p>
    <button><?=$this->text('change')?></button>
  </p>
</form>
