@echo off
REM Sobe o backend Laravel (wpp-trello-copilot) com um unico comando.
REM Faz o setup inicial automaticamente na primeira execucao (composer install,
REM .env, chave da aplicacao, banco SQLite, migrations e assets do Tailwind).

setlocal
set SCRIPT_DIR=%~dp0
set CORE_DIR=%SCRIPT_DIR%core

cd /d "%CORE_DIR%"

if not exist ".env" (
    echo ==^> Criando .env a partir de .env.example
    copy ".env.example" ".env" >nul
)

if not exist "vendor" (
    echo ==^> Instalando dependencias PHP ^(composer install^)
    call composer install --no-interaction
)

findstr /b "APP_KEY=base64" .env >nul 2>&1
if errorlevel 1 (
    echo ==^> Gerando APP_KEY
    call php artisan key:generate
)

if not exist "database\database.sqlite" (
    echo ==^> Criando banco SQLite
    type nul > "database\database.sqlite"
)

echo ==^> Rodando migrations
call php artisan migrate --force

if not exist "node_modules" (
    echo ==^> Instalando dependencias JS ^(npm install^)
    call npm install
)

if not exist "public\build" (
    echo ==^> Compilando assets ^(npm run build^)
    call npm run build
)

echo ==^> Subindo servidor em http://localhost:8000 ^(Ctrl+C para encerrar^)
call php artisan dev

endlocal
