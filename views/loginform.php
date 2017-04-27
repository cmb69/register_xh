<?php if ($this->isHorizontal):?>
<div class="regi_regloginarea_hor">
    <form action="<?=$this->actionUrl()?>" method="post">
        <input type="hidden" name="function" value="registerlogin">
        <div class="regi_user_hor"><?=$this->text('username')?></div>
        <div class="regi_userfield_hor"><input class="regi_userfield_hor" type="text" name="username"></div>
        <div class="regi_password_hor"><?=$this->text('password')?></div>
        <div class="regi_forgotpw_hor">
<?php else:?>
<div class="regi_regloginarea_ver">
    <form action="<?=$this->actionUrl()?>" method="post">
        <input type="hidden" name="function" value="registerlogin">
        <div class="regi_user_ver"><?=$this->text('username')?></div>
        <div class="regi_userfield_ver"><input class="regi_userfield_ver" type="text" name="username"></div>
        <div class="regi_password_ver"><?=$this->text('password')?></div>
        <div class="regi_forgotpw_ver">
<?php endif?>
<?php if ($this->hasForgotPasswordLink):?>
            <a href="<?=$this->forgotPasswordUrl()?>">
                <img src="<?=$this->forgotPasswordIcon()?>" class="regi_forgotpwimage" alt="<?=$this->text('forgot_password')?>" title="<?=$this->text('forgot_password')?>">
            </a>
<?php endif?>
        </div>
<?php if ($this->isHorizontal):?>
        <div class="regi_passwordfield_hor">
            <input type="password" name="password">
        </div>
        <div class="regi_loginbutton_hor">
            <input class="regi_loginbutton_hor" type="image" name="login" src="<?=$this->loginIcon?>" alt="<?=$this->text('login')?>" title="<?=$this->text('login')?>">
        </div>
<?php else:?>
        <div class="regi_passwordfield_ver">
            <input type="password" name="password">
        </div>
        <div class="regi_loginbutton_ver">
            <input class="regi_loginbutton_ver" type="image" name="login" src="<?=$this->loginIcon?>" alt="<?=$this->text('login')?>" title="<?=$this->text('login')?>">
        </div>
<?php endif?>
<?php if ($this->hasRememberMe):?>
<?php   if ($this->isHorizontal):?>
        <div class="regi_remember_hor">
            <input type="checkbox" name="remember" class="regi_remember_hor"><?=$this->text('remember')?>
            <div style="clear: both;"></div>
        </div>
<?php   else:?>
        <div class="regi_remember_ver">
            <hr>
            <input type="checkbox" name="remember" class="regi_remember_ver"><?=$this->text('remember')?>
        </div>
<?php   endif?>
<?php endif?>
<?php if ($this->isHorizontal):?>
<?php   if ($this->isRegisterAllowed):?>
        <div class="regi_register_hor">
            <a href="<?=$this->registerUrl()?>"><?=$this->text('register')?></a>
        </div>
<?php   endif?>
<?php else:?>
<?php   if ($this->isRegisterAllowed):?>
        <div class="regi_register_ver">
            <a href="<?=$this->registerUrl()?>"><?=$this->text('register')?></a>
        </div>
<?php   endif?>
<?php endif?>
    </form>
    <div style="clear: both;"></div>
</div>
