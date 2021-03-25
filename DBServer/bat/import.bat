@echo off
rem カレントディレクトリを現在の場所に移動
cd /d %~dp0
chcp 65001

rem 日付をYYYYMMDDHHMMSSにする
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

:ファイル名
set filename=%1
if errorlevel 1 goto ERR1

:ダブルクォーテーションを削除
set filename=%filename:"=%
if errorlevel 1 goto ERR2

:自治体ID
set cityCode=%2
set cityCode=%cityCode:"=%
if errorlevel 1 goto ERR3

set zip_dir=w:\

rem ローカルへのコピー先のパス
set copyTempPath=F:\bat\importCopyTemp\%cityCode%\%filename%

rem 自治体ごとに読み込むconfigファイルを切り替える
set configPath=F:\importConfig\project_%cityCode%.xml

rem ローカルにGMLファイルをコピーする
robocopy %zip_dir%%cityCode%\OriginalData\3DBuildings\ F:\bat\importCopyTemp\%cityCode%\ "%filename%"  /R:18 /W:10  2>>"\\DBServer\F$\bat\errorLog\imp_%cityCode%_error.txt"
if errorlevel 8 goto ERR4

REM PostgreSQLのインストールパスのbinディレクトリ
set PGPATH=C:\*****\PostgreSQL\12\bin\

cd /d "C:\*****\virtualcityDATABASE-Importer-Exporter-ADE\lib" 2>>"\\DBServer\F$\bat\errorLog\imp_%cityCode%_error.txt"
if errorlevel 1 goto ERR5

SET vfilename="vcdb-impexp-client-4.2.0-b5.jar"
IF EXIST %vfilename% (
 cd /d "C:\*****\virtualcityDATABASE-Importer-Exporter-ADE" 2>>"\\DBServer\F$\bat\errorLog\imp_%cityCode%_error.txt" 
 java  -Xmx4096m -jar lib/vcdb-impexp-client-4.2.0-b5.jar -shell  -import "%copyTempPath%" -config %configPath% > "F:\bat\importlog\%cityCode%_%filename%_%ts%.txt" 2>&1
) else (
 timeout /t 3
 goto ERR6
)

rem ローカルにコピーしたGMLファイルを消す
del "%copyTempPath%" 2>>"\\DBServer\F$\bat\errorLog\%cityCode%_error.txt"
if errorlevel 1 goto ERR7

exit /B

if errorlevel 1 goto ERR8
:ERR1
echo ファイル名設定エラー。管理者へ連絡して下さい。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B

:ERR2
echo ファイル名変更エラー。管理者へ連絡して下さい。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B

:ERR3
echo 自治体ID設定エラー。管理者へ連絡してください。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B

:ERR4
echo GMLファイルコピーエラー。管理者へ連絡してください。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B

:ERR5
echo 作業フォルダ移動エラー。再度実行してください。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B

:ERR6
echo Importエラー。管理者へ連絡してください。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"

:ERR7
echo 一時作業ファイル削除エラー。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B


:ERR8
echo 特殊ケース。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B

:ERR9
:JAVAerror
echo javaerror。 >"\\DBServer\F$\bat\errorLog\imp_%cityCode%.txt"
exit /B
