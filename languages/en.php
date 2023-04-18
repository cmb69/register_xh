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
$plugin_tx['register']['message_changeexplanation']="On this page you can change your account settings. For changing your data you will need your old password.";
$plugin_tx['register']['message_loggedin_welcometext']="Hello %s!";
$plugin_tx['register']['message_register_form1']="Newly registered members get the Status \"Guest\" and can see not more as before. The Status can be changed by admin only.";
$plugin_tx['register']['message_register_form2']="After successful registration, an email with instructions to activate the account will be sent to you.";
$plugin_tx['register']['message_reminderexplanation']="On this page you can enter your email address to receive an email with your account settings.";
$plugin_tx['register']['message_remindersent']="An email has been sent to you with your user data.";
$plugin_tx['register']['message_remindersent_reset']="If the email you specified exists in our system, we've sent a password reset link to it.";

$plugin_tx['register']['email_subject']="Account activation for %s";
$plugin_tx['register']['email_text1']="A user account with your email has been registered with following information:";
$plugin_tx['register']['email_text2']="Please click the following link to activate your new user account:";
$plugin_tx['register']['email_text3']="Please click the following link to reset your password:";
$plugin_tx['register']['email_text4']="However, a user account for this email already exists. Please click the following link if you forgot your account settings:";
$plugin_tx['register']['email_reminder_subject']="Account data for %s";
$plugin_tx['register']['email_prefs_subject']="Account data changed for %s";
$plugin_tx['register']['email_prefs_updated']="Your account information has been updated as follows.";

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
$plugin_tx['register']['error_status_empty']="No validation code supplied!";
$plugin_tx['register']['error_status_invalid']="The entered validation code is invalid.";
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