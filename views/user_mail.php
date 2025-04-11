<?php

use Plib\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $email
 * @var string $subject
 * @var string $message
 */
?>
<!-- register admin email -->
<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="register_token" value="<?=$this->esc($token)?>">
  <p>
    <label>
      <span><?=$this->text('label_email')?></span>
      <input type="email" value="<?=$this->esc($email)?>" disabled>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('label_subject')?></span>
      <input name="subject" value="<?=$this->esc($subject)?>">
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('label_message')?></span>
      <textarea name="message" rows="10" cols="50"><?=$this->esc($message)?></textarea>
    </label>
  </p>
  <p>
    <button name="action" value="do_mail"><?=$this->text('label_send')?></button>
  </p>
</form>
