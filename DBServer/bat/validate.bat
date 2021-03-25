@echo off
rem UTF-8
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

:公開設定
set Release_Status=private

set output_dir=v:\
set zip_dir=w:\

:検証対象ZIPのフルパス
set target_zip=%zip_dir%%cityCode%\OriginalData\3DBuildings\%filename%
if errorlevel 1 goto ERR4

rem ローカルへのコピー先のパス
set copyTempPath=F:\bat\copyTemp\%cityCode%\%filename%

rem ローカルにGMLファイルをコピーする
rem copy /Y "%target_zip%" "%copyTempPath%" 2>>"\\DBServer\F$\bat\errorLog\%cityCode%_error.txt"
robocopy %zip_dir%%cityCode%\OriginalData\3DBuildings\ F:\bat\copyTemp\%cityCode%\ "%filename%"  /R:18 /W:10  2>>"\\DBServer\F$\bat\errorLog\%cityCode%_error.txt"
if errorlevel 8 goto ERR5

cd /d "C:\*****\virtualcityDATABASE-Importer-Exporter-ADE\lib" 2>>"\\DBServer\F$\bat\errorLog\%cityCode%_error.txt"
if errorlevel 1 goto ERR6

SET vfilename="vcdb-impexp-client-4.2.0-b5.jar"
IF EXIST %vfilename% (
 cd /d "C:\*****\virtualcityDATABASE-Importer-Exporter-ADE" 2>>"\\DBServer\F$\bat\errorLog\%cityCode%_error.txt"
 java  -Xmx4096m -jar lib/vcdb-impexp-client-4.2.0-b5.jar -shell  -validate "%copyTempPath%" -config F:\validateConfig\project.xml > "F:\tmp\%cityCode%\%filename%.txt" 2>&1
) else (
 timeout /t 3
 goto ERR5
)
copy "F:\tmp\%cityCode%\%filename%.txt" "F:\bat\validatelog\%cityCode%_%filename%_%ts%.txt" /Y
if errorlevel 1 goto ERR11
robocopy F:\tmp\%cityCode%\ %output_dir%%cityCode%\ValidateLog\ "%filename%.txt" /MOV /R:18 /W:10 1>"\\DBServer\F$\bat\errorLog\%cityCode%_error.txt"
if errorlevel 8 goto ERR7

rem ローカルにコピーしたGMLファイルを消す
del "%copyTempPath%" 2>>"\\DBServer\F$\bat\errorLog\%cityCode%_error.txt"
if errorlevel 1 goto ERR8

exit /B

if errorlevel 1 goto ERR9
:ERR1
:異常終了
echo ファイル名設定エラー。管理者へ連絡して下さい。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
move /y "\\DBServer\F$\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR2
:異常終了
echo ファイル名変更エラー。管理者へ連絡して下さい。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
move /y "\\DBServer\F$\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR3
:異常終了
echo 自治体ID設定エラー。管理者へ連絡してください。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
move /y "\\DBServer\F$\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR4
:異常終了
echo ZIPファイル名設定エラー。管理者へ連絡してください。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
move /y "\\DBServer\F$\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR5
:異常終了
echo ファイルチェックに失敗しました。再度実行してください。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
move /y "\\DBServer\F$\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR6
:異常終了
echo 作業フォルダ移動エラー。管理者へ連絡してください。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
move /y "\\DBServer\F$\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"

:ERR7
:異常終了
echo Validateエラー。管理者へ連絡してください。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
move /y "\\DBServer\F$\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR8
:異常終了
echo 一時作業ファイル削除エラー。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR9
:異常終了
echo 特殊ケース。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR10
:JAVAerror
echo javaerror。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR11
:異常終了
echo 検証ログをログ保存フォルダにコピー失敗。 >"\\DBServer\F$\bat\errorLog\%cityCode%_test.txt"
exit /B