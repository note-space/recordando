## recordando
recordando is a small PHP program to edit a collection of notes, and to organize those notes in the simplest way.
<br />
It shows an array of cards; any card can be set as a "portal" to lead to another array.
<br />
![an array of cards, with a portal to another array](./docs/screenshot1.png)
<br />
Also, it has a calculating function that works within a block of text.
<br />
![the text edit screen, with calculation](./docs/screenshot2.png)
<br />
It is designed to run as a desktop application installed locally on any Windows, Macintosh or Linux personal computer or laptop, but not on any smartphone or tablet type system.
<br />
The notes are saved in a sqlite file on the local computer; nothing is on the internet .. though you could put the files in a Dropbox (or similar) folder, to share between your computers.
<br />
The download includes a small PHP "engine" for use on Windows; Macintosh has PHP built in, and Linux users can install it from repository.
<br />
The PHP engine is just 3 files from php-5.4.45-Win32-VC9-x86.zip at https://windows.php.net/downloads/releases/archives.
<br />
(The 5.4 version was chosen because its Visual C runtime requirement is compatiable with older PCs). 
<br />
There is a Visual Basic .vbs file for Windows that starts PHP with its built-in server option, hides the terminal window, and opens the .php file in a browser window.
<br />
And, for any OS, there is an .htm file that displays a command for the user to paste into a terminal window that they open for themselves.  After that, they click on a link to open the .php file in the browser.
<br />
![to start the program on any OS](./docs/screenshot3.png)
<br />
![running in the Terminal window](./docs/screenshot4.png)
<br />
![the journal screen](./docs/screenshot5.png)
<br />
'recordando' in Spanish translates to 'remembering' in English
<br />
for questions or comments, you can contact the author at tomhyde2@gmail.com
<br />

