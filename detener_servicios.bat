@echo off
chcp 65001 >nul
title ERP LeonPlast - Detener Servicios
color 0C

echo ===============================================================================
echo                LEON PLAST ERP - DETENER SERVICIOS ACTIVOS
echo ===============================================================================
echo.

set "PORT=8001"

echo Liberando puerto %PORT% (Servidor Laravel)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :%PORT% ^| findstr LISTENING') do (
    echo  - Deteniendo proceso PID: %%a
    taskkill /f /pid %%a >nul 2>&1
)

echo Liberando puerto 5173 (Vite Dev Server)...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :5173 ^| findstr LISTENING') do (
    echo  - Deteniendo proceso PID: %%a
    taskkill /f /pid %%a >nul 2>&1
)

echo.
echo [OK] Servicios detenidos correctamente.
timeout /t 3 >nul
exit /b 0
