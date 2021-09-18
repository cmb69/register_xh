<div class="regi_regloginarea">
    <form action="<?=$this->actionUrl()?>" method="post">
        <input type="hidden" name="function" value="registerlogin">
        <div class="regi_user"><?=$this->text('username')?></div>
        <div class="regi_userfield"><input class="regi_userfield" type="text" name="username"></div>
        <div class="regi_password"><?=$this->text('password')?></div>
        <div class="regi_forgotpw">
<?php if ($this->hasForgotPasswordLink):?>
            <a href="<?=$this->forgotPasswordUrl()?>" title="<?=$this->text('forgot_password')?>">
                <?=$this->text('forgot_password')?>
            </a>
<?php endif?>
        </div>
        <div class="regi_passwordfield">
            <input type="password" name="password">
        </div>
        <div class="regi_loginbutton">
            <button class="regi_loginbutton" name="login"><?=$this->text('login')?></button>
        </div>
<?php if ($this->hasRememberMe):?>
        <div class="regi_remember">
            <input type="checkbox" name="remember" class="regi_remember"><?=$this->text('remember')?>
        </div>
<?php endif?>
<?php if ($this->isRegisterAllowed):?>
        <div class="regi_register">
            <a href="<?=$this->registerUrl()?>"><?=$this->text('register')?></a>
        </div>
<?php endif?>
    </form>
    <div style="clear: both;"></div>
</div>
