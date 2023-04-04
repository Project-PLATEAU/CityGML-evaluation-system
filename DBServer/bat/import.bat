rem カレントディレクトリを現在の場所に移動
cd /d %~dp0
rem 文字コードの設定
chcp 932

rem データフォルダ
set zip_dir=C:\CityGML-validation-function\Data
set importCopyTemp_dir=C:\bat\importCopyTemp
set importConfig_dir=C:\importConfig

set errorlog_dir=%errorlog_dir%

rem 日付をYYYYMMDDHHMMSSにする
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem ファイル名
set filename=%1
if %errorlevel%==1 goto err1

rem ダブルクォーテーションを削除
set filename=%filename:"=%
if %errorlevel%==1 goto err2

rem 自治体ID
set cityCode=%2
set cityCode=%cityCode:"=%
if %errorlevel%==1 goto err3




rem ローカルへのコピー先のパス
set copyTempPath=%importCopyTemp_dir%\%cityCode%\%filename%

rem 自治体ごとに読み込むconfigファイルを切り替える
set configPath=%importConfig_dir%\project_%cityCode%.xml

rem ローカルにGMLファイルをコピーする
robocopy %zip_dir%\%cityCode%\OriginalData\3DBuildings\ %importCopyTemp_dir%\%cityCode%\ "%filename%"  /R:18 /W:10  2>>"%errorlog_dir%\imp_%cityCode%_error.txt"
if %errorlevel%==8 goto err4

set PGPATH=C:\Program Files\PostgreSQL\14\bin\

cd /d "C:\CityGML-validation-function\3DCityDB-Importer-Exporter\lib" 2>>"%errorlog_dir%\imp_%cityCode%_error.txt"
if %errorlevel%==1 goto err5

SET vfilename="impexp-client-4.3.0-rc1.jar"
IF EXIST %vfilename% (
 cd /d "C:\CityGML-validation-function\3DCityDB-Importer-Exporter" 2>>"%errorlog_dir%\imp_%cityCode%_error.txt" 
 java -Xmx4096m -jar lib/%vfilename% --config=%configPath% import "%copyTempPath%" > "C:\bat\importlog\%cityCode%_%filename%_%ts%.txt" 2>&1
) else (
 timeout /t 3
 goto ERR6
)

del "%copyTempPath%" 2>>"%errorlog_dir%\%cityCode%_error.txt"
if %errorlevel%==1 goto err7

exit /b

if %errorlevel%==1 goto err8
:err1
echo ファイル名設定エラー。管理者へ連絡して下さい> %errorlog_dir%\imp_%cityCode%.txt
exit /b

:err2
echo ファイル名変更エラー。管理者へ連絡して下さい。 >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err3
echo 自治体ID設定エラー。管理者へ連絡してください  >%errorlog_dir%\imp_%cityCode%.txt
exit /b

:err4
echo GMLファイルコピーエラー。管理者へ連絡してください。 >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err5
echo 作業フォルダ移動エラー。再度実行してください。 >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err6
echo Importエラー。管理者へ連絡してください。 >"%errorlog_dir%\imp_%cityCode%.txt"

:err7
echo 一時作業ファイル削除エラー。 >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err8
echo 特殊ケース。 >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err9
:JAVAerror
echo javaerror。 >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

