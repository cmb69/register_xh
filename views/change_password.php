<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $password1
 * @var string $password2
 * @var string $cancel
 */
?>
<!-- register change password -->
<div class="register_change_password">
  <form method="post">
<?foreach ($errors as $error):?>
    <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
    <p><?=$this->text('message_reminderexplanation')?></p>
    <p class="register_field">
      <label>
        <span><?=$this->text('label_password')?></span>
        <input name="password1" type="password" value="<?=$password1?>" required>
      </label>
    </p>
    <p class="register_field">
      <label>
        <span><?=$this->text('label_password2')?></span>
        <input name="password2" type="password" value="<?=$password2?>" required>
      </label>
    </p>
    <p class="register_buttons">
      <button name="register_action" value="change_password"><?=$this->text('label_change')?></button>
      <a href="<?=$cancel?>"><?=$this->text('label_cancel')?></a>
    </p>
  </form>
</div>
