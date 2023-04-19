<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $fullname
 * @var string $username
 * @var string $email
 * @var string $url
 */
?>
<?=$this->text('email_salutation', $fullname)?><br>

<?=$this->text('email_forgot_text1')?><br>

 <?=$this->text('label_name')?>: <?=$fullname?><br>
 <?=$this->text('label_username')?>: <?=$username?><br>
 <?=$this->text('label_email')?>: <?=$email?><br>

<?=$this->text('email_forgot_text2')?><br>

&lt;<?=$url?>&gt;

<?=$this->text('email_closing')?><br>
