<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $url
 */
?>
<!-- register activation -->
<div class="register_activation">
  <p class="xh_success"><?=$this->text('message_activated')?></p>
  <p><a href="<?=$url?>"><?=$this->text('label_login')?></a></p>
</div>