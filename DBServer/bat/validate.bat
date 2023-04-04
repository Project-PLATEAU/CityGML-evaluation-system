rem カレントディレクトリを現在の場所に移動
cd /d %~dp0
chcp 932

rem 日付をYYYYMMDDHHMMSSにする
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem ファイル名
set filename=%1
if %errorlevel%== 1 goto ERR1

rem ダブルクォーテーションを削除
set filename=%filename:"=%
if %errorlevel%== 1 goto ERR2

rem 自治体ID
set cityCode=%2
set cityCode=%cityCode:"=%
if %errorlevel%== 1 goto ERR3

rem 公開設定
set Release_Status=private

rem 2022 ここのパスは要調整
set output_dir=C:\Apache24\htdocs\iUR_Data
set zip_dir=C:\CityGML-validation-function\Data

rem 検証対象ZIPのフルパス
set target_zip=%zip_dir%\%cityCode%\OriginalData\3DBuildings\%filename%
if %errorlevel%== 1 goto ERR4

rem ローカルへのコピー先のパス
set copyTempPath=C:\bat\copyTemp\%cityCode%\%filename%

rem ローカルにGMLファイルをコピーする
rem copy /Y "%target_zip%" "%copyTempPath%" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
robocopy %zip_dir%\%cityCode%\OriginalData\3DBuildings\ C:\bat\copyTemp\%cityCode%\ "%filename%"  /R:18 /W:10  2>>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 8 goto ERR5

cd /d "C:\CityGML-validation-function\3DCityDB-Importer-Exporter\lib" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 1 goto ERR6

SET vfilename="impexp-client-4.3.0-rc1.jar"
IF EXIST %vfilename% (
 cd /d "C:\CityGML-validation-function\3DCityDB-Importer-Exporter" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
 java  -Xmx4096m -jar lib/%vfilename% --config=C:\validateConfig\project.xml validate "%copyTempPath%" > "C:\tmp\%cityCode%\%filename%.txt" 2>&1
) else (
 timeout /t 3
 goto ERR5
)
copy "C:\tmp\%cityCode%\%filename%.txt" "C:\bat\validatelog\%cityCode%_%filename%_%ts%.txt" /Y
if %errorlevel%== 1 goto ERR11
robocopy C:\tmp\%cityCode%\ %output_dir%\%cityCode%\ValidateLog\ "%filename%.txt" /MOV /R:18 /W:10 1>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 8 goto ERR7

rem ローカルにコピーしたGMLファイルを消す
del "%copyTempPath%" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 1 goto ERR8

exit /B

if %errorlevel%== 1 goto ERR9
:ERR1
:異常終了
echo ファイル名設定エラー。管理者へ連絡して下さい。 >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR2
:異常終了
echo ファイル名変更エラー。管理者へ連絡して下さい。 >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR3
:異常終了
echo 自治体ID設定エラー。管理者へ連絡してください。 >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR4
:異常終了
echo ZIPファイル名設定エラー。管理者へ連絡してください。 >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR5
:異常終了
echo ファイルチェックに失敗しました。再度実行してください。 >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR6
:異常終了
echo 作業フォルダ移動エラー。管理者へ連絡してください。 >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"

:ERR7
:異常終了
echo Validateエラー。管理者へ連絡してください。 >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR8
:異常終了
echo 一時作業ファイル削除エラー。 >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR9
:異常終了
echo 特殊ケース。 >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR10
:JAVAerror
echo javaerror。 >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR11
:異常終了
echo 検証ログをログ保存フォルダにコピー失敗。 >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B