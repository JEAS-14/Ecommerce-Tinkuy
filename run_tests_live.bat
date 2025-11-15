@echo off
REM =============================================
REM  Script: run_tests_live.bat
REM  Ejecuta todos los tests con marcas de tiempo
REM  Uso: doble click o desde CMD
REM  Opcional: agrega --testdox si quieres salida legible
REM =============================================

set PHPUNIT_CMD=vendor\bin\phpunit
set MODE=%1
if "%MODE%"=="" set MODE=normal

:begin
cls
echo =============================================
echo  FECHA/HORA: %date% %time%
echo  MODO: %MODE%
echo =============================================

if "%MODE%"=="testdox" (
  %PHPUNIT_CMD% --testdox
) else if "%MODE%"=="coverage" (
  %PHPUNIT_CMD% --coverage-text
) else (
  %PHPUNIT_CMD%
)

echo.
echo (Presiona CTRL+C para salir) Reiniciando en 10s...
timeout /t 10 > nul
goto begin
