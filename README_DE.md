# Register_XH

Register_XH ermöglicht es, den Zugriff auf bestimmte CMSimple_XH-Seiten auf
registrierte Anwender zu beschränken. Zu diesem Zweck verfügt es über eine
einfache Benutzer- und Gruppenverwaltung sowie ein Login-Formular mit der
optionalen Möglichkeit für Besucher sich per E-Mail zu registrieren. Es wird
von einigen anderen Plugins verwendet, um eine Benutzerverwaltung zu
ergänzen (z.B. [Chat_XH](https://github.com/cmb69/chat_xh)
und [Forum_XH](https://github.com/cmb69/forum_xh)).

- [Voraussetzungen](#voraussetzungen)
- [Download](#download)
- [Installation](#installation)
- [Einstellungen](#einstellungen)
- [Verwendung](#verwendung)
  - [Benutzer- und Gruppenverwaltung](#benutzer--und-gruppenverwaltung)
  - [Login-Formular](#login-formular)
  - [Zugriff auf Seiten](#zugriff-auf-seiten)
  - [Spezialseiten](#spezialseiten)
- [Einschränkungen](#einschränkungen)
- [Problembehebung](#problembehebung)
- [Lizenz](#lizenz)
- [Danksagung](#danksagung)

## Voraussetzungen

Register_XH ist ein Plugin für [CMSimple_XH](https://www.cmsimple-xh.org/de/).
Es benötigt CMSimple_XH ≥ 1.7.0 und PHP ≥ 7.1.0 mit den hash und session Extensions.

## Download

Das [aktuelle Release](https://github.com/cmb69/register_xh/releases/latest)
kann von Github herunter geladen werden.

## Installation

Die Installation erfolgt wie bei vielen anderen CMSimple_XH-Plugins auch.

1. Sichern Sie die Daten auf Ihrem Server.
1. Entpacken Sie die Datei auf Ihrem Computer.
1. Laden Sie das gesamte Verzeichnis `register/` auf Ihren Server in
   das `plugins/` Verzeichnis von CMSimple_XH hoch.
1. Vergeben Sie Schreibrechte für die Unterverzeichnisse `config/`,
   `css/` und `languages/`.
1. Browsen Sie zu `Plugins` → `Register` im Administrationsbereich,
   um zu prüfen, ob alle Voraussetzungen erfüllt sind.

## Einstellungen

Die Plugin-Konfiguration erfolgt wie bei vielen anderen CMSimple_XH-Plugins
im Administrationsbereich der Website.
Browsen Sie zu `Plugins` → `Register`.

Sie können die Voreinstellungen von Register_XH unter `Konfiguration`
ändern. Hinweise zu den Optionen werden beim Überfahren der Hilfe-Icons mit
der Maus angezeigt.

Die Lokalisierung wird unter `Sprache` vorgenommen. Sie können die
Zeichenketten in Ihre eigene Sprache übersetzen, falls keine entsprechende
Sprachdatei zur Verfügung steht, oder sie entsprechend Ihren Anforderungen
anpassen.

Das Aussehen von Register_XH kann unter `Stylesheet` angepasst werden.

## Verwendung

### Benutzer- und Gruppenverwaltung

Das erste was Sie tun sollten, ist die Benutzergruppen entsprechend Ihren
Wünschen anzupassen. Dies ist unter `Plugins` → `Register` →
`Gruppenverwaltung` möglich. Sie können die Benutzer unter
`Plugins` → `Register` → `Benutzerverwaltung` verwalten.
Beide Masken sollten weitgehen selbsterklärend sein, aber einige Hinweise
besonders bezüglich der Letzteren scheinen angebracht:

- Das Auswahlfeld erlaubt es die Benutzer nach ihrer Gruppenzugehörig zu
  filtern. Wenn Sie einen Benutzer hinzufügen, wird der Filter zurück gesetzt,
  da Sie sonst u.U. den neuen Benutzerdatensatz nicht sehen könnten. Wenn Sie
  die Daten speichern, werden alle Benutzerdatensätze gespeichert, selbst wenn
  sie ausgefiltert wurden.

- Sie können die Benutzerdatensätze durch Anklicken der entsprechenden
  Spaltenüberschrift sortieren.

- Der Status kann einen der folgenden Werte haben (die Bezeichnungen der Werte
  sind sprachspezifisch und können in den Spracheinstellungen geändert
  werden):

  - `aktiviert`:
  der Benutzer hat die vollen Rechte entsprechend seiner Gruppenzugehörigkeit
  - `gesperrt`:
  der Benutzer hat die vollen Rechte entsprechend seiner Gruppenzugehörigkeit,
  aber darf seine Benutzereinstellungen nicht ändern
  - `deaktiviert`:
  das Benutzerkonto ist (vorübergehend) deaktiviert
  - `noch nicht aktiviert`:
  der Benutzer hat sich registriert, aber das Konto wurde noch nicht aktiviert

Sowohl in der Gruppen- wie auch in der Benutzerverwaltung ist das Hinzufügen
und Löschen eines Benutzers nur temporär; um die Änderungen dauerhaft zu
machen, müssen Sie sie ausdrücklich speichern.

Bitte beachten sie weiterhin, dass die Möglichkeit nach dem Login als
registrierter Benutzer der Admingruppe in den Administrationsmodus zu
wechseln, entfernt wurde. Der Aufruf von
`registeradminmodelink()` ist nun missbilligt und gibt den Link
 nicht mehr zurück. Plugins sollten keine Hintertür anbieten, um die
Login-Sicherheit von CMSimple_XH zu umgehen.

### Login-Formular

Um dem Benutzer die Möglichkeit zu bieten sich einzuloggen und sich optional
zunächst zu registrieren, muss das *Login-Formular* angezeigt werden.
Sie können es im Template aufrufen, so dass es auf allen Seiten angezeigt
wird:

    <?=registerloginform()?>

Alternativ können Sie es auf einer oder mehreren CMSimple_XH-Seiten aufrufen:

    {{{registerloginform()}}}

In diesem Fall werden Sie vermutlich das Formular für eingeloggte Benutzer
zum Template hinzufügen wollen. Dies ist mit dem folgenden Aufruf möglich:

    <?=Register_loggedInForm()?>

Falls Benutzer ihr Kennwort vergessen haben, können sie eine E-Mail mit
Anweisungen zum Zurücksetzen des Kennworts anfordern. Falls die
Registrierung neuer Benutzer erlaubt ist, können sich diese selbst
registrieren, und eine E-Mail mit dem Aktivierungslink wird ihnen zugesandt,
so dass sie ihr Konto aktivieren und sich nahezu sofort einloggen können.
All dies geschieht ohne dass der Administrator etwas unternehmen muss, der
aber Kopien der E-Mails zu seiner Information erhält.

Nach erfolgreichen Einloggen können Benutzer ihre Einstellungen bearbeiten,
also Name, E-Mail-Adresse und Kennwort. Unregistrieren, d.h. Löschen des
Kontos, ist ebenso in der Einstellungsmaske möglich.

Beachten Sie, dass alle Login- und Logoutversuche in der Protokolldatei
von CMSimple_XH festgehalten werden.

### Zugriff auf Seiten

Um den Zugriff auf eine CMSimple_XH-Seite auf bestimmte Benutzergruppen zu
beschränken, müssen die Namen dieser Gruppen durch Komma getrennt im
`Zugriff` Page-Data-Reiter oberhalb des Editors eingegeben werden.
Ist das Feld leer, kann jeder auf die Seite zugreifen.

### Spezialseiten

Register_XH fügt dynamisch ein paar Spezialseiten zu CMSimple_XH hinzu,
falls diese nicht bereits existieren. Normalerweise müssen Sie sich darüber
keine Gedanken machen, aber Sie können das Feature nutzen, um eigene Seiten
mit der entsprechenden Überschrift anzulegen, wenn Sie eine dieser Seiten
weitergehend anpassen möchten, als es durch Anpassen der entsprechenden
Sprachtexte möglich ist. Die Überschriften dieser Spezialseiten werden durch
die *tatsächlichen Werte* der folgenden Sprachtexte bestimmt,
und die Seiten sollten den entsprechenden Pluginaufruf enthalten:

- `register`:
  die Seite, auf der Anwender ein neues Benutzerkonto registrieren können

      {{{registerUser()}}}

- `forgot_password`:
  die Seite, auf der Anwender eine E-Mail anfordern können, die ihnen erlaubt ihr Kennwort zurück zu setzen

      {{{registerForgotPassword()}}}

- `user_prefs`:
  die Seite, auf der Anwender ihr Benutzerprofil ändern können

      {{{registerUserPrefs()}}}

- `login_error`:
  die Seite, auf der Anwender über ein fehl geschlagenes Login informiert werden

- `loggedout`:
  die Seite, die angezeigt wird nachdem sich ein Anwender ausgeloggt hat

- `loggedin`:
  die Seite, die angezeigt wird nachchdem sich ein Anwender eingeloggt hat

- `access_error`:
  die Seite, die angezeigt wird, wenn Anwender eine Seite aufrufen, für die sie kein Zugriffsrecht haben

Bitte beachten Sie, dass die Behandlung und Erkennung dieser Seiten derzeit
etwas locker gehandhabt wird (so spielt es z.B. keine Rolle, auf welcher
Menüebene sich diese befinden), was sich vermutlich in Zukunft ändern wird.

## Einschränkungen

Wenn irgend eine CMSimple_XH-Seite unbeabsichtigt eine Überschrift hat, die
für eine der [Register_XH-Spezialseiten](#spezialseiten) reserviert
ist, könnte das Plugin nicht richtig funktionieren.

In Abhängigkeit der PHP ini Einstellungen `max_input_vars`,
`suhosin.post.max_vars` und `suhosin.request.max_vars`
gibt es eine Obergrenze für die Höchstanzahl von Benutzern, die im
Plugin-Back-End verwaltet werden können. In der Standardkonfiguration von PHP
sind höchstens 124 Benutzer erlaubt. Das Plugin überprüft diese
Grenze, und verhindert das Hinzufügen weiterer Benutzer. Wenn die Obergrenze
überschritten wurde, müssen Sie die Benutzer manuell in
`user.csv` verwalten.

## Problembehebung

Melden Sie Programmfehler und stellen Sie Supportanfragen entweder auf
[Github](https://github.com/cmb69/register_xh/issues)
oder im [CMSimple\_XH Forum](https://cmsimpleforum.com/).

## Lizenz

Register_XH ist Freeware.

Copyright © 2007 [Carsten Heinelt](http://cmsimple.heinelt.eu/)  
Copyright © 2010-2012 [Gert Ebersbach](https://www.ge-webdesign.de/)  
Copyright © 2012-2021 Christoph M. Becker

Slovakische Übersetzung © 2012 Dr. Martin Sereday<br>
Tschechische Übersetzung © 2012 Josef Němec<br>
Dänische Übersetzung © 2012 Jens Maegard<br>
Russische Übersetzung © 2012 Lubomyr Kydray

## Danksagung

Register wurde 2007 von [Carsten Heinelt](http://cmsimple.heinelt.eu/) auf Basis von
Michael Svarrers [Memberpages Plugin](https://cmsimplewiki-com.keil-portal.de/doku.php?id=plugins:memberpages) entwickelt.
2010 gab er [Gert Ebersbach](https://www.ge-webdesign.de/) die
Erlaubnis es an CMSimple_XH anzupassen und es weiter zu verbessern. Das
Plugin wurde dann als Register_mod_XH verbreitet. 2012 stellte Gert
Ebersbach die Entwicklung ein und gab mir die Erlaubnis das Plugin zu
pflegen und zu verbreiten. *Vielen Dank an Carsten Heinelt und Gert
Ebersbach für ihre gute Arbeit und die Erlaubnis das Plugin weiterhin
pflegen zu dürfen!*

Das Plugin-Logo wurde von Wendell Fernandes entworfen.
Vielen Dank für die Veröffentlichung als Freeware.

Vielen Dank an die Community im [CMSimple_XH-Forum](https://www.cmsimpleforum.com/)
für Anregungen, Vorschläge und das Testen.
Besonders möchte ich *Holger* für das Finden eines
schwerwiegenden Makels, und für seinen Vorschlag, die Benutzerverwaltung zu
verbessern, *kmsmei* für das Berichten einer Sicherheitslücke, und natürlich
*Joe* für viele gute Vorschläge danken.

Zu guter Letzt vielen Dank an [Peter Harteg](https://www.harteg.dk/), den „Vater“ von CMSimple,
und allen Entwicklern von [CMSimple_XH](http://www.cmsimple-xh.org/)
ohne die dieses phantastische CMS nicht existieren würde.
