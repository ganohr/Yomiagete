@echo off
setlocal

for /f "delims=" %%a in (version.txt) do (
	set "version=%%a"
)

echo VERSION
echo %version%

set "outpath=.\trunk\%version%"
mkdir %outpath%\

rem copy *.md %outpath%\
copy *.txt %outpath%\
copy *.css %outpath%\
copy *.js %outpath%\
copy *.php %outpath%\
del %outpath%\debug*.php
del %outpath%\version.txt

cd %outpath%

set "zipfile=..\..\release\yomiagete-%version%.zip"
del %zipfile%

powershell compress-archive .\* %zipfile% -Force

set "basefile=..\..\release\yomiagete.zip"
del %basefile%

copy %zipfile% %basefile%

endlocal
pause
echo on
