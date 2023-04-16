<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<string> $users
 */
?>
<!-- register active users -->
<section class="register_active_users">
  <p>Active Users</p>
  <p>
<?foreach ($users as $user):?>
  <span><?=$user?></span>
<?endforeach?>
  </p>
</section>
