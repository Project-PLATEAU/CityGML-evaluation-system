@echo off
rem 文字コードをUTF-8に指定
chcp 932
rem カレントディレクトリをbat実行フォルダへ移動
cd /d %~dp0

rem 日付をYYYYMMDDHHMMSSにする
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem CLI出力用のパス文字列
set output_dir=*****:/*****/htdocs/iUR_Data/
set temp_dir=C:/CityGML-validation-function/Data/

REM 親からの引数（ファイル名）
set FILENAME=%1
:ダブルクォーテーションを削除
set FILENAME=%FILENAME:"=%
if %errorlevel%== 1 goto ERR

REM 親からの引数（EPSGコード）
REM set EPSGCOORD="EPSG:6677"
set EPSGCOORD=%2
if %errorlevel%== 1 goto ERR

REM 親からの引数（自治体コード）
set CITYCOORD=%3
:ダブルクォーテーションを削除
set CITYCOORD=%CITYCOORD:"=%
if %errorlevel%== 1 goto ERR

rem 対象ファイルのパス
set target_file=%temp_dir%\%CITYCOORD%\OriginalData\3DBuildings\

rem ローカルへのコピー先のパス
set copyTempPath=C:\bat\topologicalcopyTemp\%CITYCOORD%\

rem ローカルにGMLファイルをコピーする
robocopy %target_file% %copyTempPath% "%FILENAME%"  /R:6 /W:10
if %errorlevel%== 8 goto ERR

rem 管理用フラグ
set fileflg=file

rem zip解凍用のフォルダを初期化する
set ziptemp=%copyTempPath%ziptemp
echo "解凍先:%ziptemp%"
echo "フォルダを削除"
rd /s /q %ziptemp%

rem zipの場合解凍をする
echo "%FILENAME%" | find ".zip" >NUL
if not ERRORLEVEL 1 ( GOTO UNZIP ) ELSE ( GOTO ZIPCHECK )

:UNZIP
  powershell -command "Expand-Archive -Path %copyTempPath%%FILENAME% -DestinationPath %ziptemp%"
  echo "ZIPを解凍しました:%ziptemp%"
  set fileflg=zip
  GOTO ZIPCHECK

:ZIPCHECK

rem ローカルにCityGMLValidation.fmwファイルをコピーする
robocopy C:\bat\ C:\bat\topologicalLog\%CITYCOORD% CityGMLValidation.fmw  /R:6 /W:10
if %errorlevel%== 8 goto ERR

set WORKSPACE_FILE=C:\bat\topologicalLog\%CITYCOORD%\CityGMLValidation.fmw

set urbanObject=C:\bat\schemas\iur\uro\2.0\urbanObject.xsd
set LOGFOLDER=C:\bat\topologicalLog\%CITYCOORD%\%ts%_%FILENAME%
set LOGFILE=%FILENAME%.txt

set MAX_PROC=6

mkdir %LOGFOLDER%
mkdir %LOGFOLDER%\logs
mkdir %LOGFOLDER%\logs2
mkdir %LOGFOLDER%\logs2\invalid

IF %fileflg%==zip ( 
  echo "ZIP処理"
  "C:\Program Files\FME\fme.exe" %WORKSPACE_FILE% --INPUT "%ziptemp%[\**\*.gml]" --ADE_XSD_DOC "%urbanObject%" --SRS_AXIS "2<comma>1<comma>3" --COORD "EPSG:6676" --OVER_CHECK "ALL" --OVERLAP "5" --TOLERANCE "0.001" --BOUNDEDBY "Yes" --PREC "3" --PLAN_ANG "20" --PLAN_DIST "0.03" --OUTPUT "%LOGFOLDER%\logs2\output.json" --LOG_FILE "%LOGFOLDER%\logs\%LOGFILE%"
  if %errorlevel%== 1 goto ERR2

) ELSE ( 
  echo "FILE処理"
  "C:\Program Files\FME\fme.exe" %WORKSPACE_FILE% --INPUT "%copyTempPath%%FILENAME%" --ADE_XSD_DOC "%urbanObject%" --SRS_AXIS "2<comma>1<comma>3" --COORD "EPSG:6676" --OVER_CHECK "ALL" --OVERLAP "5" --TOLERANCE "0.001" --BOUNDEDBY "Yes" --PREC "3" --PLAN_ANG "20" --PLAN_DIST "0.03" --OUTPUT "%LOGFOLDER%\logs2\output.json" --LOG_FILE "%LOGFOLDER%\logs\%LOGFILE%"
  if %errorlevel%== 1 goto ERR2
)

rem error.txtが存在するならlogs内エラーと判定してエラー処理を行う
if exist %LOGFOLDER%\error\error.txt (
    rem logsフォルダ内に"ERROR"という文字列があった場合の処理
    rem エラー判定のためZIPファイル作成してWebサーバに移動
    powershell Compress-Archive -Path %LOGFOLDER%\ -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
    robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10

    echo logs ERROR >"%output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
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
echo "GOTO:ERRORZIP"
powershell Compress-Archive -Path %LOGFOLDER%\logs2\invalid -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10
goto LOGMOVE

:LOGMOVE
echo "GOTO:LOGMOVE"
rem FEMの出力したログを保持しておく
rem move /y %LOGFOLDER%\logs\%LOGFILE% %LOGFOLDER%\%LOGFILE%
rem robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%LOGFILE%" /R:6 /W:10
echo %LOGFOLDER%\logs\%LOGFILE%
echo %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\
echo "%LOGFILE%"

robocopy %LOGFOLDER%\logs\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%LOGFILE%" /R:6 /W:10
if %errorlevel%== 8 goto ERR

:FILEDELETE
echo "FILEDELETE"
rem ローカルにコピーしたGMLファイルを消す
del /q "%copyTempPath%"
if %errorlevel%== 1 goto ERR

rem ローカルにコピーしたFMWファイルを消す
del "%WORKSPACE_FILE%"
if %errorlevel%== 1 goto ERR

echo "正常完了"
exit /B

:ERR
:異常終了
echo error >"%output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
exit /B

:ERR2
:異常終了
rem エラー判定のためZIPファイル作成してWebサーバに移動
mkdir %LOGFOLDER%\error
echo 位相検証処理起動エラー >%LOGFOLDER%\error\error.txt
powershell Compress-Archive -Path %LOGFOLDER%\error -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10

echo fme.exe NG >"%output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
exit /B