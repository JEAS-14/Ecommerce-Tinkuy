@echo off
setlocal ENABLEDELAYEDEXPANSION
REM =============================================
REM  Script: test_asistente_ia.bat
REM  Pruebas rápidas del endpoint de Asistente IA (DeepSeek)
REM  Intenta en puertos 80, 8080, 8086 y 8000.
REM  Si no hay servidor, sugiere iniciar PHP embebido.
REM =============================================

set BASE1=http://localhost/Ecommerce-Tinkuy/public
set BASE2=http://localhost:8080/Ecommerce-Tinkuy/public
set BASE3=http://localhost:8086/Ecommerce-Tinkuy/public
set BASE4=http://localhost:8000

echo =============================================
echo  PRUEBA DEL ASISTENTE IA - TINKUY
echo =============================================
echo.

call :probe "%BASE1%" OK1
if /I "!OK1!"=="ok" set BASE=%BASE1%& goto run

call :probe "%BASE2%" OK2
if /I "!OK2!"=="ok" set BASE=%BASE2%& goto run

call :probe "%BASE3%" OK3
if /I "!OK3!"=="ok" set BASE=%BASE3%& goto run

call :probe "%BASE4%" OK4
if /I "!OK4!"=="ok" set BASE=%BASE4%& goto run

echo ❌ No se pudo conectar en puertos 80, 8080, 8086 ni 8000.
echo.
echo Si quieres probar sin XAMPP, abre otra ventana y ejecuta:
echo   "C:\xampp\php\php.exe" -S localhost:8000 -t public
echo Luego vuelve a ejecutar este script.
echo.
goto end

:run
echo ✅ Usando base: %BASE%
echo.
REM Chequeo previo: si no hay API key (500), activar demo automático
set QS=
call :post_code "%BASE%/deepseek_search.php" "{\"query\":\"chompa de alpaca\"}" CODECHK
if /I "!CODECHK!"=="500" (
  echo ⚠️  No hay API key detectada. Activando Modo demo automaticamente.
  set QS=?demo=1
)
echo.
echo [1/3] POST valido (debe ser 200)
echo --------------------------------------------------
curl -i -X POST "%BASE%/deepseek_search.php%QS%" ^
  -H "Content-Type: application/json" ^
  -d "{\"query\":\"chompa de alpaca\"}"

echo.
echo [2/3] POST con query vacio (debe ser 400)
echo --------------------------------------------------
curl -i -X POST "%BASE%/deepseek_search.php%QS%" ^
  -H "Content-Type: application/json" ^
  -d "{\"query\":\"\"}"

echo.
echo [3/3] GET no permitido (debe ser 405)
echo --------------------------------------------------
curl -i -X GET "%BASE%/deepseek_search.php%QS%"

echo.
echo =============================================
echo  FIN DE PRUEBAS
echo =============================================
echo.
echo Tips:
echo - Si ves 500 y mensaje de API key, define OPENROUTER_API_KEY en Apache.
echo - Verifica que Apache este en verde en XAMPP.
echo - Tambien puedes usar: "C:\xampp\php\php.exe" -S localhost:8000 -t public
echo.
goto end

:probe
set URL=%~1
for /f "delims=" %%A in ('curl -s -o nul -w "%%{http_code}" "%URL%/deepseek_search.php"') do set CODE=%%A
REM Esperamos 405 si el endpoint es alcanzable por GET
if "!CODE!"=="405" (set %2=ok) else (set %2=fail)
exit /b

:post_code
set URL=%~1
set DATA=%~2
for /f "delims=" %%A in ('curl -s -o nul -w "%%{http_code}" -X POST "%URL%" -H "Content-Type: application/json" -d "%DATA%"') do set CODE=%%A
set %3=!CODE!
exit /b

:end
endlocal
pause
