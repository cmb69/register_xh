# Register_XH

Register_XH facilitates to restrict access to certain CMSimple_XH pages to
registered users. Therefore it has a simple user and group management as
well as a login form, with the optional possibility for visitors to register
per email. It is used by some other plugins to add user management (e.g.
[Chat_XH](https://github.com/cmb69/chat_xh) and
[Forum_XH](https://github.com/cmb69/forum_xh)).

- [Requirements](#requirements)
- [Download](#download)
- [Installation](#installation)
- [Settings](#settings)
- [Usage](#usage)
  - [User and Group Administration](#user-and-group-administration)
  - [Login Form](#login-form)
  - [Access to Pages](#access-to-pages)
  - [Special Pages](#special-pages)
- [Limitations](#limitations)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Credits](#credits)

## Requirements

Register_XH is a plugin for [CMSimple_XH](https://cmsimple-xh.org/).
It requires CMSimple_XH ≥ 1.7.0 with the [Fa_XH plugin](https://github.com/cmb69/fa_xh)
and PHP ≥ 7.0.2.

## Download

The [lastest release](https://github.com/cmb69/register_xh/releases/latest)
is available for download on Github.

## Installation

The installation is done as with many other CMSimple_XH plugins.

1. Backup the data on your server.
1. Unzip the distribution on your computer.
1. Upload the whole directory `register/` to your server into the `plugins/`
   directory of CMSimple_XH.
1. Set write permissions to the subdirectories `config/`,
   `css/` and `languages/`.
1. Navigate to `Plugins` → `Register` in the back-end to check if all
   requirements are fulfilled.

## Settings

The configuration of the plugin is done as with many other CMSimple_XH plugins
in the back-end of the Website.
Go to `Plugins` → `Register`.

You can change the default settings of Register_XH under `Config`.
Hints for the options will be displayed when hovering over the help icon
with your mouse.

Localization is done under `Language`. You can translate the character
strings to your own language if there is no appropriate language file available,
or customize them according to your needs.

The look of Register_XH can be customized under `Stylesheet`.

## Usage

### User and Group Administration

The first thing you should do is to adjust the user groups according to your
needs. You can do this under `Plugins` → `Register` → `Group administration`.
You can administrate the users under `Plugins` → `Register` → `User administration`.
Both screens should be pretty much self explaining,
but some notes about the latter seem to be in order:

- The selectbox allows to filter the users by an access group. When you add a
  new user, the filter is reset, as you might not be able to see the new user
  record otherwise. When you save the data all user records will be saved,
  even if they are filtered out.

- You can sort the user records by clicking on the respective column heading.

- The status can have one of the following values (the label of the values are
  language specific, and can be changed in the language settings):

  - `activated`:
    the user has the full privileges according to his groups
  - `locked`:
    the user has the full privileges according to his groups, but may not change his preferences
  - `deactivated`:
    the user account is (temporarily) deactivated
  - `not yet activated`:
    the user has registered, but the account has not been activated

- In both group as well as user administration adding or deleting a user is
  temporary; to make the changes permanent, you have to explicitly save them.

Please note also, that the possibility to switch to admin mode after being
logged in as registered admin has been removed. The call to
`registeradminmodelink()` is now deprecated and does not return
the link anymore. Plugins should not offer any backdoor to circumvent the
login security of CMSimple_XH.

### Login Form

To offer the user the possibility to log in and optionally register first,
the *login form* must be displayed. You can put it in the template, so it is
shown on all pages:

    <?=registerloginform()?>

Alternatively you can put it on one or several CMSimple_XH pages:

    {{{registerloginform()}}}

In this case you might want to add the *logged in* form to the
template. You can do so with the following call:

    <?=Register_loggedInForm()?>

If users forgot their password, they can request an email with intructions
to reset their password. If registration of new users is allowed, they can
register themselves, and an email with the activation link will be sent to
them, so they can activate their account and log in nearly immediately. All
this happens without requiring any actions from the admin, who will still
receive copies of the emails to be informed.

After successful login users can edit their user preferences, i.e. name,
email address and password. Unregistering, i.e. deleting the account, is
also possible via the preferences screen.

Note that all login and logout attempts will be logged in the logfile of
CMSimple_XH.

### Access to Pages

To restrict the access to a CMSimple_XH page to certain user groups, you have
to enter on that page:

    {{{access('LIST-OF-GROUPS')}}}

`LIST-OF-GROUPS` is a comma separated list of access groups that
will have access to the page. For example:

    {{{access('admin,member,guest')}}}

or

    {{{access('admin')}}}

### Special Pages

Register_XH dynamically adds some special pages to CMSimple_XH, if these do
not already exist. Usually you do not have to care about this, but you can
use the feature to create your own pages with the respective heading, if you
want to customize any of these pages beyond what is possible with adjusting
the related language strings. The headings of these pages are specified by
the *actual values* of the following language strings and the
pages should contain the respective plugin call, if applicable:

- `register`:
  the page where users can register for a new account

        {{{registerUser()}}}

- `forgot_password`:
  the page where a user can request an email which allows to reset his password

      {{{registerForgotPassword()}}}

- `user_prefs`:
  the page where a user can change his account setting

      {{{registerUserPrefs()}}}`

- `login_error`:
  the page where a user is informed about an invalid login attempt

- `loggedout`:
  the page that is displayed after a user has logged out

- `loggedin`:
  the page that is displayed after a user has logged in

- `access_error`:
  the page that is displayed when a user browses to a page he is not allowed to access

Please note that the handling and recognition of these pages is a bit sloppy
currently (e.g. it does not matter on which menu level they are defined), but
this is likely to change in the future.

## Limitations

If any CMSimple_XH page inadvertently has a heading that is used for one of
the [special Register_XH pages](#special-pages), the plugin might
not work as expected.

Depending on the PHP ini settings `max_input_vars`,
`suhosin.post.max_vars` and `suhosin.request.max_vars`
there is a limit on the maximum number of users that can be administrated in
the plugin back-end. In a default configuration of PHP ≥ 7.0.2 at most 142
users are allowed. The plugin checks this limit, and does not permit more
users to be added. If this limit is exceeded, you have to administrate the
users in `users.csv` manually.

## Troubleshooting

Report bugs and ask for support either on
[Github](https://github.com/cmb69/register_xh/issues)
or in the [CMSimple_XH Forum](https://cmsimpleforum.com/).

## License

Register_XH is freeware.

Copyright © 2007 [Carsten Heinelt](http://cmsimple.heinelt.eu/)  
Copyright © 2010-2012 [Gert Ebersbach](https://www.ge-webdesign.de/)  
Copyright © 2012-2021 Christoph M. Becker

Slovak translation © 2012 Dr. Martin Sereday  
Czech translation © 2012 Josef Němec  
Danish translation © 2012 Jens Maegard  
Russian translation © 2012 Lubomyr Kydray

## Credits

Register was developed in 2007 by [Carsten Heinelt](http://cmsimple.heinelt.eu/)
based on the [Memberpages plugin](http://cmsimplewiki-com.keil-portal.de/doku.php?id=plugins:memberpages) by Michael Svarrer.
In 2010 he gave permission to [Gert Ebersbach](https://www.ge-webdesign.de/)
to adapt it to CMSimple_XH and to further improve it.
The plugin was then distributed as Register_mod_XH.
In 2012 Gert Ebersbach discontinued the developement,
and gave me the permission to maintain and distribute the plugin.
*Many thanks to Carsten Heinelt and Gert Ebersbach for their good
work and the permission to further maintain the plugin!*

The plugin logo is designed by Wendell Fernandes.
Many thanks for publishing this icon as freeware.

Many thanks to the community at the [CMSimple_XH forum](https://www.cmsimpleforum.com/)
for tips, suggestions and testing.
Particularly I want to thank *Holger* for finding a severe flaw,
and for his suggestion to improve the user administration,
*kmsmei* for reporting a security issue,
and of course *Joe* for many good suggestions.

And last but not least many thanks to [Peter Harteg](https://www.harteg.dk),
the “father” of CMSimple, and all developers of [CMSimple_XH](https://cmsimple-xh.org/)
without whom this amazing CMS would not exist.
