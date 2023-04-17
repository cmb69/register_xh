<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $email
 */
?>
<!-- register password forgotten -->
<div class="register_password_forgotten">
  <form method="post">
<?foreach ($errors as $error):?>
    <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
    <p>
      <label>
        <span><?=$this->text('label_email')?></span>
        <input class="text" name="email" type="email" value="<?=$email?>" required>
      </label>
    </p>
    <p>
      <button class="submit" name="register_action" value="forgot_password"><?=$this->text('label_send')?></button>
    </p>
  </form>
</div>
