<?php

use Plib\View;

/**
 * @var View $this
 * @var string $key
 * @var string $fullname
 * @var string $username
 * @var string $email
 * @var string $remoteAddress
 */
?>
<?=$this->text('email_salutation', $fullname)?><br>

<?=$this->text($key)?><br>

 <?=$this->text('label_name')?>: <?=$this->esc($fullname)?><br>
 <?=$this->text('label_username')?>: <?=$this->esc($username)?><br>
 <?=$this->text('label_email')?>: <?=$this->esc($email)?><br>
 <?=$this->text('label_fromip')?>: <?=$this->esc($remoteAddress)?><br>

<?=$this->text('email_updated_text')?><br>

<?=$this->text('email_closing')?><br>
