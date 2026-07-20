@echo off
title MancoSync - Stop
echo Menghentikan layanan MancoSync (port 8686, 8787, 8000, 8080)...
for %%P in (8686 8787 8000 8080) do (
  for /f "tokens=5" %%A in ('netstat -ano ^| findstr :%%P ^| findstr LISTENING') do (
    taskkill /F /PID %%A >nul 2>&1 && echo   - port %%P (PID %%A) dihentikan.
  )
)
echo.
echo Selesai. (Database Postgres/Redis/MongoDB tetap jalan sebagai service.)
timeout /t 2 /nobreak >nul
