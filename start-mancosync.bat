@echo off
rem MancoSync Launcher (.bat) — hanya meneruskan ke launcher VBScript,
rem supaya yang tersisa cuma SATU window Windows Terminal (4 tab),
rem tanpa window console tambahan. Window .bat ini menutup sendiri.
start "" wscript.exe "%~dp0start-mancosync.vbs"
exit
