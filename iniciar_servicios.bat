@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title ERP LeonPlast - Gestor de Servicios
color 0A

:: Ubicarse siempre en la carpeta del proyecto
cd /d "%~dp0"

set "PORT=8001"
set "HOST=0.0.0.0"

cls
echo ===============================================================================
echo                LEON PLAST ERP - INICIADOR DE SERVICIOS
echo ===============================================================================
echo.
echo  Ubicación: %~dp0
echo  Puerto HTTP: %PORT% (Accesible desde red local: http://192.168.10.88:%PORT%)
echo.
echo ===============================================================================
echo  VERIFICACIÓN DE DEPENDENCIAS:
echo ===============================================================================

:: 1. Verificar PHP
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo  [X] ERROR: No se encontró 'php' en el PATH del sistema.
    echo      Asegúrate de tener PHP instalado o Laragon en ejecución.
    pause
    exit /b 1
) else (
    echo  [OK] PHP detectado correctamente.
)

:: 2. Verificar Node.js y NPM
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo  [AVISO] ADVERTENCIA: No se encontró 'npm' en el PATH. Vite no podrá iniciar.
) else (
    echo  [OK] Node.js y NPM detectados correctamente.
)

:: 3. Verificar MySQL (Puerto 3306)
netstat -ano | findstr :3306 >nul 2>&1
if %errorlevel% neq 0 (
    echo  [AVISO] ADVERTENCIA: No se detectó MySQL activo en el puerto 3306.
    echo      Por favor, asegúrate de haber iniciado MySQL en Laragon.
) else (
    echo  [OK] Base de datos MySQL detectada en puerto 3306.
)

echo.
echo ===============================================================================
echo  SELECCIONE EL MODO DE INICIO (Inicio automático en 6 segundos):
echo ===============================================================================
echo.
echo  [1] INICIAR TODO (Servidor Web + Colas + Vite Hot-Reload)  -- [RECOMENDADO]
echo  [2] INICIAR TODO EN VENTANAS SEPARADAS (3 terminales independientes)
echo  [3] INICIAR SOLO SERVIDOR WEB (php artisan serve en :%PORT%)
echo  [4] LIMPIAR CACHÉS DE LARAVEL (optimize:clear)
echo  [5] DETENER SERVICIOS ACTIVOS (Liberar puerto %PORT%)
echo  [6] SALIR
echo.

choice /c 123456 /n /t 6 /d 1 /m "Seleccione una opción [1-6] (Por defecto 1): "
set "OPCION=!errorlevel!"

if "!OPCION!"=="1" goto iniciar_todo
if "!OPCION!"=="2" goto iniciar_separadas
if "!OPCION!"=="3" goto iniciar_solo_web
if "!OPCION!"=="4" goto limpiar_cache
if "!OPCION!"=="5" goto detener_servicios
if "!OPCION!"=="6" goto salir

:iniciar_todo
cls
echo ===============================================================================
echo   INICIANDO SERVICIOS (CONCURRENTEMENTE)
echo ===============================================================================
echo.
echo   * Servidor Laravel  : http://localhost:%PORT% ^| http://192.168.10.88:%PORT%
echo   * Cola de Trabajo   : php artisan queue:listen
echo   * Compilador Vite   : npm run dev
echo.
echo   [Presiona CTRL + C para detener todos los servicios a la vez]
echo ===============================================================================
echo.

npx concurrently -k -c "#10b981,#8b5cf6,#f59e0b" -n "LARAVEL,QUEUE,VITE" "php artisan serve --host=%HOST% --port=%PORT%" "php artisan queue:listen --tries=1" "npm run dev"
goto fin

:iniciar_separadas
cls
echo ===============================================================================
echo   INICIANDO EN VENTANAS INDEPENDIENTES
echo ===============================================================================
echo.
echo   Abriendo Servidor Web...
start "ERP LeonPlast - Servidor Web (:8001)" cmd /k "cd /d "%~dp0" && color 0A && php artisan serve --host=%HOST% --port=%PORT%"

echo   Abriendo Worker de Colas...
start "ERP LeonPlast - Colas de Base de Datos" cmd /k "cd /d "%~dp0" && color 0D && php artisan queue:listen --tries=1"

echo   Abriendo Vite Dev Server...
start "ERP LeonPlast - Vite Dev Server" cmd /k "cd /d "%~dp0" && color 0E && npm run dev"

echo.
echo   [OK] Las 3 ventanas han sido iniciadas exitosamente.
echo   Puedes minimizar esta consola.
echo.
pause
goto fin

:iniciar_solo_web
cls
echo ===============================================================================
echo   INICIANDO SOLO SERVIDOR WEB
echo ===============================================================================
echo.
echo   * URL Local      : http://localhost:%PORT%
echo   * URL Red Local  : http://192.168.10.88:%PORT%
echo.
echo   [Presiona CTRL + C para detener el servidor]
echo.
php artisan serve --host=%HOST% --port=%PORT%
goto fin

:limpiar_cache
cls
echo ===============================================================================
echo   LIMPIANDO Y OPTIMIZANDO CACHÉS DE LARAVEL
echo ===============================================================================
echo.
php artisan optimize:clear
echo.
echo [OK] Cachés de vistas, rutas y configuración limpiadas.
pause
goto fin

:detener_servicios
cls
echo ===============================================================================
echo   DETENIENDO SERVICIOS EN EL PUERTO %PORT%
echo ===============================================================================
echo.
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :%PORT% ^| findstr LISTENING') do (
    echo Terminando proceso PID: %%a ...
    taskkill /f /pid %%a >nul 2>&1
)
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :5173 ^| findstr LISTENING') do (
    echo Terminando proceso Vite PID: %%a ...
    taskkill /f /pid %%a >nul 2>&1
)
echo.
echo [OK] Procesos detenidos y puertos liberados.
pause
goto fin

:fin
:salir
exit /b 0
