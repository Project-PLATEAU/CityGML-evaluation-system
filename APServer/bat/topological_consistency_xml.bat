@echo off
rem 文字コードをUTF-8に指定
chcp 65001
rem カレントディレクトリをbat実行フォルダへ移動
cd /d %~dp0

rem 日付をYYYYMMDDHHMMSSにする
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem CLI出力用のパス文字列
set output_dir=u:\
set temp_dir=W:\

REM 親からの引数（ファイル名）
set FILENAME=%1
:ダブルクォーテーションを削除
set FILENAME=%FILENAME:"=%
if errorlevel 1 goto ERR

REM 親からの引数（EPSGコード）
REM set EPSGCOORD="EPSG:6677"
set EPSGCOORD=%2
if errorlevel 1 goto ERR

REM 親からの引数（自治体コード）
set CITYCOORD=%3
:ダブルクォーテーションを削除
set CITYCOORD=%CITYCOORD:"=%
if errorlevel 1 goto ERR

rem 対象ファイルのパス
set target_file=%temp_dir%%CITYCOORD%\OriginalData\3DBuildings\

rem ローカルへのコピー先のパス
set copyTempPath=F:\bat\topologicalcopyTemp\%CITYCOORD%\

rem ローカルにGMLファイルをコピーする
robocopy %target_file% %copyTempPath% "%FILENAME%"  /R:6 /W:10
if errorlevel 8 goto ERR

rem ローカルにrunner.fmwファイルをコピーする
robocopy F:\bat\ F:\bat\topologicalLog\%CITYCOORD% runner.fmw  /R:6 /W:10
if errorlevel 8 goto ERR

set SOURCE=F:\bat\topologicalLog\%CITYCOORD%\runner.fmw

rem ローカルにCityGMLValidation.fmwファイルをコピーする
robocopy F:\bat\ F:\bat\topologicalLog\%CITYCOORD% CityGMLValidation.fmw  /R:6 /W:10
if errorlevel 8 goto ERR

set WORKSPACE_FILE=F:\bat\topologicalLog\%CITYCOORD%\CityGMLValidation.fmw

set LOGFOLDER=F:\bat\topologicalLog\%CITYCOORD%\%ts%_%FILENAME%
set LOGFILE=%FILENAME%.txt

set MAX_PROC=6

mkdir %LOGFOLDER%
mkdir %LOGFOLDER%\logs
mkdir %LOGFOLDER%\logs2

"C:\*****\FME\fme.exe" %SOURCE% --INPUT %copyTempPath%%FILENAME% --ADE_XSD_DOC "" --SRS_AXIS "2<comma>1<comma>3" --COORD %EPSGCOORD% --OVER_CHECK "LoD2" --OVERLAP "5" --TOLERANCE "0.001" --BOUNDEDBY "Yes" --PREC "8" --PLAN_ANG "20" --PLAN_DIST "0.03" --OUTPUT %LOGFOLDER%\logs2 --MAX_PROC %MAX_PROC% --WORKSPACE_FILE %WORKSPACE_FILE% --LOG %LOGFOLDER%\logs >"%LOGFOLDER%\execLog.txt" 2>&1
if errorlevel 1 goto ERR2

rem logsフォルダ内に"ERROR"という文字列があるか判定し、あればerrorフォルダとerror.txtを作成するパワーシェルを呼ぶ
powershell -File topological_consistency_error_check.ps1 "%LOGFOLDER%"

rem error.txtが存在するならlogs内エラーと判定してエラー処理を行う
if exist %LOGFOLDER%\error\error.txt (
    rem logsフォルダ内に"ERROR"という文字列があった場合の処理
    rem エラー判定のためZIPファイル作成してWebサーバに移動
    powershell Compress-Archive -Path %LOGFOLDER%\error -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
    robocopy %LOGFOLDER%\ %output_dir%%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10

    echo logs ERROR >"%output_dir%%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
    rem ローカルにコピーしたファイルの削除を呼ぶ
    goto FILEDELETE
)

rem errorフォルダにファイルがあったらzip化してWebサーバーに送る
for /f %%a in ('dir /b "%LOGFOLDER%\logs2\invalid\*.json"') do (
 goto ERRORZIP
 )
goto LOGMOVE

:ERRORZIP
:ZIP作成
powershell Compress-Archive -Path %LOGFOLDER%\logs2\invalid -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
robocopy %LOGFOLDER%\ %output_dir%%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10
goto LOGMOVE

:LOGMOVE
rem runnner.logを保持しておく
move /y F:\bat\topologicalLog\%CITYCOORD%\runner.log %LOGFOLDER%\%LOGFILE%
robocopy %LOGFOLDER%\ %output_dir%%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%LOGFILE%" /R:6 /W:10
if errorlevel 8 goto ERR

:FILEDELETE
rem ローカルにコピーしたGMLファイルを消す
del /q "%copyTempPath%"
if errorlevel 1 goto ERR

rem ローカルにコピーしたFMWファイルを消す
del "%SOURCE%"
if errorlevel 1 goto ERR

rem ローカルにコピーしたFMWファイルを消す
del "%WORKSPACE_FILE%"
if errorlevel 1 goto ERR

exit /B

:ERR
:異常終了
echo error >"%output_dir%%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
exit /B

:ERR2
:異常終了
rem エラー判定のためZIPファイル作成してWebサーバに移動
mkdir %LOGFOLDER%\error
echo 位相検証処理起動エラー >%LOGFOLDER%\error\error.txt
powershell Compress-Archive -Path %LOGFOLDER%\error -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
robocopy %LOGFOLDER%\ %output_dir%%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10

echo fme.exe NG >"%output_dir%%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
exit /B