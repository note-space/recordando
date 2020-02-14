this_exe = "php.exe"
this_port = "8040"
this_program = "recordando.php"
prog_folder = "windows_engine"
sub_folder = "app"

' first check to see if PHP local server is already running; if so, don't start it up again
php_check = "n"
Set service = GetObject ("winmgmts:")
For Each Process in Service.InstancesOf ("Win32_Process")
 If Process.Name = this_exe Then
  php_check = "y"
 End If
 If Process.Name = this_exe & " *32" Then
  php_check = "y"
 End If
Next

' here we start up the PHP local server, with a few options
If php_check = "n" Then
 Set shell_one = WScript.CreateObject("WScript.Shell")
 this_dir = shell_one.CurrentDirectory 
 this_app = chr(34) & this_dir & "\" & prog_folder & "\" & this_exe & chr(34)
 local_server = " --no-php-ini --server localhost:" & this_port
 doc_root = " --docroot " & chr(34) & this_dir & chr(34)
 ext1 = " --define extension=" & chr(34) & this_dir & "\" & prog_folder & "\php_pdo_sqlite.dll" & chr(34) 
 shell_one.Run this_app & local_server & doc_root & ext1 , 0 , False
End If

' wait a second (can be changed, if needed) before opening the browser 
WScript.Sleep 1000

' here we open the program in a browser window
Set shell_two = WScript.CreateObject("WScript.Shell")
shell_two.Run "http://localhost:" & this_port & "/" & sub_folder & "/" & this_program , 1 , True
