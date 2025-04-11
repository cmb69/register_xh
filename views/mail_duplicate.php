<?php

use Plib\View;

/**
 * @var View $this
 * @var string $fullname
 * @var string $username
 * @var string $email
 * @var string $remoteAddress
 * @var string $other_fullname
 * @var string $other_username
 * @var string $other_email
 * @var string $url
 */
?>
<?=$this->text('email_salutation', $other_fullname)?><br>

<?=$this->text('email_register_text1')?><br>

  <?=$this->text('label_name')?>: <?=$this->esc($fullname)?><br>
  <?=$this->text('label_username')?>: <?=$this->esc($username)?><br>
  <?=$this->text('label_email')?>: <?=$this->esc($email)?><br>
  <?=$this->text('label_fromip')?>: <?=$this->esc($remoteAddress)?><br>

<?=$this->text('email_register_text3')?><br>

  <?=$this->text('label_name')?>: <?=$this->esc($other_fullname)?><br>
  <?=$this->text('label_username')?>: <?=$this->esc($other_username)?><br>
  <?=$this->text('label_email')?>: <?=$this->esc($other_email)?><br>

<?=$this->text('email_register_text4')?><br>

&lt;<?=$this->esc($url)?>&gt;

<?=$this->text('email_closing')?><br>
