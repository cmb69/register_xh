<?php

$plugin_tx['register']['menu_main']="Pages";
$plugin_tx['register']['menu_group_admin']="Groups";
$plugin_tx['register']['menu_user_admin']="Users";

$plugin_tx['register']['label_access']="Access";
$plugin_tx['register']['label_access_error']="Access Restricted";
$plugin_tx['register']['label_accessgroups']="Access Groups";
$plugin_tx['register']['label_cancel']="Cancel";
$plugin_tx['register']['label_change']="Change";
$plugin_tx['register']['label_change_password']="Change Password";
$plugin_tx['register']['label_create']="Create";
$plugin_tx['register']['label_delete']="Delete";
$plugin_tx['register']['label_edit']="Edit";
$plugin_tx['register']['label_email']="Email";
$plugin_tx['register']['label_filter']="Filter";
$plugin_tx['register']['label_forgot_password']="Password forgotten";
$plugin_tx['register']['label_fromip']="From IP-Address";
$plugin_tx['register']['label_groupname']="Groupname";
$plugin_tx['register']['label_login']="Login";
$plugin_tx['register']['label_logout']="Logout";
$plugin_tx['register']['label_mail']="Mail";
$plugin_tx['register']['label_message']="Message";
$plugin_tx['register']['label_name']="Full Name";
$plugin_tx['register']['label_new']="New";
$plugin_tx['register']['label_none']="--- NONE ---";
$plugin_tx['register']['label_oldpassword']="Old Password";
$plugin_tx['register']['label_pages']="Pages";
$plugin_tx['register']['label_password']="Password";
$plugin_tx['register']['label_password2']="Confirm Password";
$plugin_tx['register']['label_refresh']="Refresh";
$plugin_tx['register']['label_register']="Register";
$plugin_tx['register']['label_remember']="Remember User";
$plugin_tx['register']['label_send']="Send";
$plugin_tx['register']['label_status']="Status";
$plugin_tx['register']['label_subject']="Subject";
$plugin_tx['register']['label_submit']="Submit";
$plugin_tx['register']['label_update']="Update";
$plugin_tx['register']['label_user_delete']="Delete User";
$plugin_tx['register']['label_user_prefs']="User Preferences";
$plugin_tx['register']['label_username']="Username";
$plugin_tx['register']['label_users']="Users";

$plugin_tx['register']['status_activated']="activated";
$plugin_tx['register']['status_locked']="locked";
$plugin_tx['register']['status_deactivated']="deactivated";
$plugin_tx['register']['status_not_yet_activated']="not yet activated";

$plugin_tx['register']['message_activated']="You have successfully activated your new account.";
$plugin_tx['register']['message_change_password']="To change your password, you also need to enter your old password.";
$plugin_tx['register']['message_change_prefs']="To change your account data, you also need to enter your old password.";
$plugin_tx['register']['message_delete_account']="To delete your user account, you need to enter your old password.";
$plugin_tx['register']['message_forgot']="If you forgot your login data, enter your email address, and we will send an email with the data including instructions on how to reset your password, if an account with this email address exists.";
$plugin_tx['register']['message_register']="You can register a new user account. After registration, an email with instructions to activate the account will be sent to you.";
$plugin_tx['register']['message_welcometext']="Hello %s!";

$plugin_tx['register']['email_closing']="Sincerely,\nThe Webmaster";
$plugin_tx['register']['email_forgot_text1']="A password reset for the following user account has been requested:";
$plugin_tx['register']['email_forgot_text2']="If you did not request a password reset, just ignore this email.\nOtherwise click the following link to reset your password:";
$plugin_tx['register']['email_password_updated']="The password of the following user account has been changed:";
$plugin_tx['register']['email_prefs_updated']="Your account information has been updated as follows:";
$plugin_tx['register']['email_register_text1']="A user account with your email address has been registered with following information:";
$plugin_tx['register']['email_register_text2']="If you did not register a user account, just ignore this email.\nOtherwise click the following link to activate your new user account:";
$plugin_tx['register']['email_register_text3']="However, a user account for this email address exists already:";
$plugin_tx['register']['email_register_text4']="If you forgot your password, click the following link to reset it.\nOtherwise ignore this mail.";
$plugin_tx['register']['email_salutation']="Hello %s!";
$plugin_tx['register']['email_subject']="Your user account at %s";
$plugin_tx['register']['email_updated_text']="If you did not do this yourself, please inform us immediately.";

$plugin_tx['register']['error_unauthorized']="You are not authorized for this action!";
$plugin_tx['register']['error_user_does_not_exist']="User '%s' does not exist!";
$plugin_tx['register']['error_name']="Please enter your full name.";
$plugin_tx['register']['error_groupname_exists']="This groupname already exists!";
$plugin_tx['register']['error_username']="Please choose a Username.";
$plugin_tx['register']['error_username_illegal']="The username must contain only following characters: A-Z, a-z, 0-9, _";
$plugin_tx['register']['error_username_exists']="The chosen username exists already.";
$plugin_tx['register']['error_username_notfound']="The Username '%s' could not be found!";
$plugin_tx['register']['error_password']="Please choose a password.";
$plugin_tx['register']['error_password2']="The two entered passwords do not match.";
$plugin_tx['register']['error_old_password_wrong']="The old password you entered is wrong.";
$plugin_tx['register']['error_email']="Please enter your email address.";
$plugin_tx['register']['error_email_invalid']="The given email address is invalid.";
$plugin_tx['register']['error_email_exists']="A user with the given email address exists already.";
$plugin_tx['register']['error_colon']="The full Name must not contain a colon!";
$plugin_tx['register']['error_group_does_not_exist']="Group '%s' does not exist!";
$plugin_tx['register']['error_group_missing']="The user belongs to no group!";
$plugin_tx['register']['error_cannot_write_csv']="Saving CSV file failed.";
$plugin_tx['register']['error_code_missing']="No verification code supplied!";
$plugin_tx['register']['error_code_invalid']="The entered verification code is invalid.";
$plugin_tx['register']['error_status']="Invalid status!";
$plugin_tx['register']['error_group_illegal']="The group name must contain only following characters: A-Z, a-z, 0-9, '_', '-'.";
$plugin_tx['register']['error_subject']="Invalid subject!";
$plugin_tx['register']['error_message']="Invalid message!";
$plugin_tx['register']['error_send_mail']="The email could not be sent!";
$plugin_tx['register']['error_user_locked']="User Preferences for '%s' can't be changed!";
$plugin_tx['register']['error_login'] = "You entered a wrong username or password, or your account still is not activated.";
$plugin_tx['register']['error_access']="This page is only accessible for members with appropriate permissions.";
$plugin_tx['register']['error_expired']="The password reset has expired!";

$plugin_tx['register']['alt_help']="Question mark";

$plugin_tx['register']['log_login']="User “%s” logged in";
$plugin_tx['register']['log_login_user']="User “%s” does not exist";
$plugin_tx['register']['log_login_forbidden']="User “%s” is not allowed to log in";
$plugin_tx['register']['log_login_password']="User “%s” submitted wrong password";
$plugin_tx['register']['log_autologin']="User “%s” automatically logged in";
$plugin_tx['register']['log_resetlogin']="User “%s” logged in after password reset";
$plugin_tx['register']['log_logout']="User “%s” logged out";
$plugin_tx['register']['log_unregister']="User “%s” deleted account";

$plugin_tx['register']['syscheck_extension']="PHP extension %s is loaded";
$plugin_tx['register']['syscheck_extension_no']="PHP extension %s is not loaded";
$plugin_tx['register']['syscheck_phpversion']="PHP version is at least %s";
$plugin_tx['register']['syscheck_phpversion_no']="PHP version is not at least %s";
$plugin_tx['register']['syscheck_title']="System check";
$plugin_tx['register']['syscheck_writable']="%s is writable";
$plugin_tx['register']['syscheck_writable_no']="%s is not writable";
$plugin_tx['register']['syscheck_xhversion']="CMSimple_XH version is at least %s";
$plugin_tx['register']['syscheck_xhversion_no']="CMSimple_XH version is not at least %s";

$plugin_tx['register']['hint_accessgroups']="A comma separated list of user groups which have access to this page. Leave empty to grant access to everybody, including visitors.";

$plugin_tx['register']['cf_allowed_register']="Whether new users may register themselves.";
$plugin_tx['register']['cf_allowed_remember']="Whether users are allowed to use the remember me feature.";
$plugin_tx['register']['cf_allowed_settings']="Whether users are allowed to change their account settings.";
$plugin_tx['register']['cf_allowed_password_forgotten']="Whether users are allowed to use the password forgotten feature.";
$plugin_tx['register']['cf_fix_mail_headers']="Whether to fix problems with some buggy mail transfer agents. Enable it, if you don't receive the emails or if some header information (e.g. \"MIME-Version: 1.0\") is visible in the body of the mail.";
$plugin_tx['register']['cf_group_activated']="The status of new members (group after registration and account activation).";
$plugin_tx['register']['cf_group_default']="The status of new members (default group for user after registration, but before account activation).";
$plugin_tx['register']['cf_hide_pages']="Whether to hide pages in the table of contents to which the user does not have access.";
$plugin_tx['register']['cf_senderemail']="The e-mail account of the administrator";
$plugin_tx['register']['cf_activity_period']="The time in seconds after which a logged in user is considered inactive.";
?>