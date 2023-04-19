<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $key
 * @var string $fullname
 * @var string $username
 * @var string $email
 * @var string $remoteAddress
 */
?>
<?=$this->text($key)?><br>

 <?=$this->text('label_name')?>: <?=$fullname?><br>
 <?=$this->text('label_username')?>: <?=$username?><br>
 <?=$this->text('label_email')?>: <?=$email?><br>
 <?=$this->text('label_fromip')?>: <?=$remoteAddress?><br>

<?=$this->text('email_updated_text')?><br>
