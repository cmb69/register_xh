<?php

use Register\Infra\View;

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
<?=$this->text('email_register_text1')?><br>

  <?=$this->text('label_name')?>: <?=$fullname?><br>
  <?=$this->text('label_username')?>: <?=$username?><br>
  <?=$this->text('label_email')?>: <?=$email?><br>
  <?=$this->text('label_fromip')?>: <?=$remoteAddress?><br>

<?=$this->text('email_register_text3')?><br>

  <?=$this->text('label_name')?>: <?=$other_fullname?><br>
  <?=$this->text('label_username')?>: <?=$other_username?><br>
  <?=$this->text('label_email')?>: <?=$other_email?><br>

<?=$this->text('email_register_text4')?><br>

&lt;<?=$url?>&gt;
