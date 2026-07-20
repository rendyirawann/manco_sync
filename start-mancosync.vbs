' ============================================================
'  MancoSync Launcher (VBScript)
'  Membuka SATU window Windows Terminal berisi 4 tab
'  (Web / Film / Rust / Reverb) + browser, TANPA window
'  console tambahan. Double-click file ini.
' ============================================================
Option Explicit
Dim sh
Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "c:\xampp\htdocs\myProject\manco-sync"

' 1) Bersihkan config saja (cepat, TIDAK menghapus cache portal biar
'    tetap warm antar-restart). Hidden + tunggu selesai.
sh.Run "cmd /c php artisan config:clear", 0, True

' 2) Satu window Windows Terminal, 4 tab (dipisah dengan ; )
'    CATATAN: JANGAN taruh --title sebelum new-tab pertama; --title bukan opsi
'    global wt, jadi wt akan mengira "new-tab" adalah program (error 0x80070002).
Dim cmd
cmd = "wt new-tab --title ""Web :8686"" -d ""c:\xampp\htdocs\myProject\manco-sync"" cmd /k ""php artisan serve --port=8686""" & _
  " ; new-tab --title ""Film :8787"" -d ""c:\xampp\htdocs\myProject\tmdb-embed-api"" cmd /k ""npm start""" & _
  " ; new-tab --title ""Rust :8000"" -d ""c:\xampp\htdocs\myProject\manco-sync\manco-rust"" cmd /k ""cargo run""" & _
  " ; new-tab --title ""Reverb :8080"" -d ""c:\xampp\htdocs\myProject\manco-sync"" cmd /k ""php artisan reverb:start --host=127.0.0.1 --port=8080"""
sh.Run cmd, 1, False

' 3) Tunggu web server siap, lalu buka browser (tanpa console)
WScript.Sleep 6000
sh.Run "http://127.0.0.1:8686/", 1, False
