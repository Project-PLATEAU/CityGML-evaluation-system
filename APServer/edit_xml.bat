rem 文字コードをUTF-8に指定
chcp 932
rem カレントディレクトリをbat実行フォルダへ移動
cd /d %~dp0

rem 設定フォルダ
set template_dir=C:\bat\template
set temp_dir=C:\CityGML-validation-function\Data
set tiles_dir=C:\Apache24\htdocs\map
set copytemp=C:\bat\copyTemp
set xml_dir=C:\bat\xml

rem 日付をYYYYMMDDHHMMSSにする
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem テンプレートXMLのパス
set temp_file_DEM=%template_dir%\template_dem.xml
set temp_file_2D=%template_dir%\template_2d.xml
set temp_file_3D_LOD1=%template_dir%\template_3d_LOD1.xml
set temp_file_3D_LOD2=%template_dir%\template_3d_LOD2.xml
set temp_file_3D_LOD2_Surface=%template_dir%\template_3d_LOD2_Surface.xml
set temp_file_3D_LOD3=%template_dir%\template_3d_LOD3.xml
set temp_file_3D_LOD4=%template_dir%\template_3d_LOD4.xml
set temp_file_3D_LODALL=%template_dir%\template_3d_LODALL.xml

rem 親からの引数（自治体コード）
set cityCode=%2


rem 親からの引数（ファイル名）
set filename=%1
set filename=%filename:"=%


rem親からの引数（ファイルタイプ）
set filetype=%3

rem リネーム後のXMLフルパス
set xml_name=%xml_dir%\%cityCode%_%filename%_%ts%.xml

rem ダブルクォーテーションを削除
set xml_name=%xml_name:"=%

rem 自治体ごとのCLIの実行結果出力先
set output3DTilespath="%tiles_dir%\%cityCode%\private\datasource-data\%filename%"
:ダブルクォーテーションを削除
set output3DTilespath=%output3DTilespath:"=%

rem 対象ファイルのパス
set target_file=%temp_dir%\%cityCode%\OriginalData\3DBuildings

rem ローカルへのコピー先のパス
set copyTempPath="%copytemp%\%cityCode%\%filename%"

rem ローカルにGMLファイルをコピーする
rem copy /Y "%target_file%\%filename%" %copyTempPath%
robocopy %target_file%\ %copytemp%\%cityCode%\ "%filename%"  /R:6 /W:10
if %errorlevel%== 8 (
    set errorDetail=CityGMLをAPサーバにコピー失敗
    goto ERR
)

if %filetype%==DEM  goto dem
if %filetype%==2D  goto 2d
if %filetype%==3D_LOD1  goto 3d_LOD1
if %filetype%==3D_LOD2  goto 3d_LOD2
if %filetype%==3D_LOD2_Surface  goto 3d_LOD2_Surface
if %filetype%==3D_LOD3  goto 3d_LOD3
if %filetype%==3D_LOD4  goto 3d_LOD4
if %filetype%==3D_ALL  goto 3D_ALL

:dem
set temp_file=%temp_file_DEM%
goto end

:2d
set temp_file=%temp_file_2D%
goto end

:3d_LOD1
set temp_file=%temp_file_3D_LOD1%
goto end

:3d_LOD2
set temp_file=%temp_file_3D_LOD2%
goto end

:3d_LOD2_Surface
set temp_file=%temp_file_3D_LOD2_Surface%
goto end

:3d_LOD3
set temp_file=%temp_file_3D_LOD3%
goto end

:3d_LOD4
set temp_file=%temp_file_3D_LOD4%
goto end

:3D_ALL
set temp_file=%temp_file_3D_LODALL%
goto end

:end
rem テンプレートをコピーすると同時にリネーム
copy %temp_file% "%xml_name%"
if %errorlevel%== 1 (
    set errorDetail=変換用コンフィグXMLのコピー＆リネームに失敗
    goto ERR
)

set java_dir=C:\Program Files\Eclipse Adoptium\jdk-11.0.16.8-hotspot
set x3dm_generator=C:\Program Files\vcs\tools\x3dm-generator\x3dm-generator.jar
set adePlugins=C:\Program Files\vcs\adePlugins
set convertlog1=C:\bat\ConvertLog\
set convertlog2=C:\bat\oldConvertLog\
set convertlog3=C:\bat\ConvertLog\convertResult\
set convertlog4=C:\bat\ConvertLog\batResult\
set create_xml=C:\bat\create_xml.ps1

powershell -File "%create_xml%" "%copytemp%\%cityCode%" "%filename%" "%xml_name%" %filetype% %cityCode% ;exit $LASTEXITCODE

cd "%java_dir%"

rem set LF=^ｆの後には1行空白行を入れること
setlocal enabledelayedexpansion
set LF=^

if %filetype%==DEM  goto DEM2D
if %filetype%==2D  goto DEM2D
if %filetype%==3D_LOD1  goto LOD1
if %filetype%==3D_LOD2  goto LOD2
if %filetype%==3D_LOD2_Surface  goto LOD2_Surface
if %filetype%==3D_LOD3  goto LOD3
if %filetype%==3D_LOD4  goto LOD4
if %filetype%==3D_ALL  goto LODALL

:DEM2D
rem DEMや2Dの場合
call java.exe -Djava.awt.headless=true -Xmx8192m -jar "%x3dm_generator%" create -c "%xml_name%" -o "%output3DTilespath%" -t "%copytemp%\%cityCode%\temp" -plugins "%adePlugins%" >"%convertlog1%%cityCode%_%filename%.txt" 2>&1
copy "%convertlog1%%cityCode%_%filename%.txt" "%convertlog2%%cityCode%_%filename%_%ts%.txt" /Y
if %errorlevel%== 1 (
    set errorDetail=変換ログの過去ログ保管先へのコピー失敗
    goto ERR
)
robocopy %convertlog1% %convertlog3% "%cityCode%_%filename%.txt" /MOV /R:6 /W:10

goto SUCCESS

:LOD1
rem 3D_LOD1の場合
call java.exe -Djava.awt.headless=true -Xmx8192m -jar "%x3dm_generator%" create -c "%xml_name%" -o "%output3DTilespath%_LOD1" -t "%copytemp%\%cityCode%\temp" -plugins "%adePlugins%" >"%convertlog1%%cityCode%_%filename%_LOD1.txt" 2>&1
copy "%convertlog1%%cityCode%_%filename%_LOD1.txt" "%convertlog2%%cityCode%_%filename%_%ts%_LOD1.txt" /Y
if %errorlevel%== 1 (
    set errorDetail=変換ログの過去ログ保管先へのコピー失敗
    goto ERR
)
robocopy %convertlog1% %convertlog3% "%cityCode%_%filename%_LOD1.txt" /MOV /R:6 /W:10

goto SUCCESS_LOD1

:LOD2
rem 3D_LOD2の場合
call java.exe -Djava.awt.headless=true -Xmx8192m -jar "%x3dm_generator%" create -c "%xml_name%" -o "%output3DTilespath%_LOD2" -t "%copytemp%\%cityCode%\temp" -plugins "%adePlugins%" >"%convertlog1%%cityCode%_%filename%_LOD2.txt" 2>&1
copy "%convertlog1%%cityCode%_%filename%_LOD2.txt" "%convertlog2%%cityCode%_%filename%_%ts%_LOD2.txt" /Y
if %errorlevel%== 1 (
    set errorDetail=変換ログの過去ログ保管先へのコピー失敗
    goto ERR
)
robocopy %convertlog1% %convertlog3% "%cityCode%_%filename%_LOD2.txt" /MOV /R:6 /W:10

goto SUCCESS_LOD2

:LOD2_Surface
rem 3D_LOD2_Surfaceの場合
call java.exe -Djava.awt.headless=true -Xmx8192m -jar "%x3dm_generator%" create -c "%xml_name%" -o "%output3DTilespath%_LOD2_Surface" -t "%copytemp%\%cityCode%\temp" -plugins "%adePlugins%" >"%convertlog1%%cityCode%_%filename%_LOD2_Surface.txt" 2>&1
copy "%convertlog1%%cityCode%_%filename%_LOD2_Surface.txt" "%convertlog2%%cityCode%_%filename%_%ts%_LOD2_Surface.txt" /Y
if %errorlevel%== 1 (
    set errorDetail=変換ログの過去ログ保管先へのコピー失敗
    goto ERR
)
robocopy %convertlog1% %convertlog3% "%cityCode%_%filename%_LOD2_Surface.txt" /MOV /R:6 /W:10

goto SUCCESS_LOD2_Surface

:LOD3
rem 3D_LOD3の場合
call java.exe -Djava.awt.headless=true -Xmx8192m -jar "%x3dm_generator%" create -c "%xml_name%" -o "%output3DTilespath%_LOD3" -t "%copytemp%\%cityCode%\temp" -plugins "%adePlugins%" >"%convertlog1%%cityCode%_%filename%_LOD3.txt" 2>&1
copy "%convertlog1%%cityCode%_%filename%_LOD3.txt" "%convertlog2%%cityCode%_%filename%_%ts%_LOD3.txt" /Y
if %errorlevel%== 1 (
    set errorDetail=変換ログの過去ログ保管先へのコピー失敗
    goto ERR
)
robocopy %convertlog1% %convertlog3% "%cityCode%_%filename%_LOD3.txt" /MOV /R:6 /W:10

goto SUCCESS_LOD3

:LOD4
rem 3D_LOD4の場合
call java.exe -Djava.awt.headless=true -Xmx8192m -jar "%x3dm_generator%" create -c "%xml_name%" -o "%output3DTilespath%_LOD4" -t "%copytemp%\%cityCode%\temp" -plugins "%adePlugins%" >"%convertlog1%%cityCode%_%filename%_LOD4.txt" 2>&1
copy "%convertlog1%%cityCode%_%filename%_LOD4.txt" "%convertlog2%%cityCode%_%filename%_%ts%_LOD4.txt" /Y
if %errorlevel%== 1 (
    set errorDetail=変換ログの過去ログ保管先へのコピー失敗
    goto ERR
)
robocopy %convertlog1% %convertlog3% "%cityCode%_%filename%_LOD4.txt" /MOV /R:6 /W:10

goto SUCCESS_LOD4

:LODALL
rem 3D_ALLの場合
call java.exe -Djava.awt.headless=true -Xmx8192m -jar "%x3dm_generator%" create -c "%xml_name%" -o "%output3DTilespath%_LODALL" -t "%copytemp%\%cityCode%\temp" -plugins "%adePlugins%" >"%convertlog1%%cityCode%_%filename%_LODALL.txt" 2>&1
copy "%convertlog1%%cityCode%_%filename%_LODALL.txt" "%convertlog2%%cityCode%_%filename%_%ts%_LODALL.txt" /Y
if %errorlevel%== 1 (
    set errorDetail=変換ログの過去ログ保管先へのコピー失敗
    goto ERR
)
robocopy %convertlog1% %convertlog3% "%cityCode%_%filename%_LODALL.txt" /MOV /R:6 /W:10

goto SUCCESS_LODALL

:ERR
rem 3Dの場合はファイルタイプ毎のエラーファイルを出力させる
if %filetype%==3D_LOD1  goto ERR_LOD1
if %filetype%==3D_LOD2  goto ERR_LOD2
if %filetype%==3D_LOD2_Surface  goto ERR_LOD2_Surface
if %filetype%==3D_LOD3  goto ERR_LOD3
if %filetype%==3D_LOD4  goto ERR_LOD4
if %filetype%==3D_ALL  goto ERR_LODALL

rem 2DやDEMの場合はそのまま出力する
rem if %filetype%==DEM  goto ERR_DEM
rem if %filetype%==2D  goto ERR_2D

echo error：%errorDetail%>"%convertlog4%%cityCode%_%filename%.txt"
exit /B

:ERR_LOD1
echo error：%errorDetail%>"%convertlog4%%cityCode%_%filename%_LOD1.txt"
exit /B

:ERR_LOD2
echo error：%errorDetail%>"%convertlog4%%cityCode%_%filename%_LOD2.txt"
exit /B

:ERR_LOD2_Surface
echo error：%errorDetail%>"%convertlog4%%cityCode%_%filename%_LOD2_Surface.txt"
exit /B

:ERR_LOD3
echo error：%errorDetail%>"%convertlog4%%cityCode%_%filename%_LOD3.txt"
exit /B

:ERR_LOD4
echo error：%errorDetail%>"%convertlog4%%cityCode%_%filename%_LOD4.txt"
exit /B

:ERR_LODALL
echo error：%errorDetail%>"%convertlog4%%cityCode%_%filename%_LODALL.txt"
exit /B

:SUCCESS
echo success >"%convertlog4%%cityCode%_%filename%.txt"
exit /B

:SUCCESS_LOD1
echo success >"%convertlog4%%cityCode%_%filename%_LOD1.txt"
exit /B

:SUCCESS_LOD2
echo success >"%convertlog4%%cityCode%_%filename%_LOD2.txt"
exit /B

:SUCCESS_LOD2_Surface
echo success >"%convertlog4%%cityCode%_%filename%_LOD2_Surface.txt"
exit /B

:SUCCESS_LOD3
echo success >"%convertlog4%%cityCode%_%filename%_LOD3.txt"
exit /B

:SUCCESS_LOD4
echo success >"%convertlog4%%cityCode%_%filename%_LOD4.txt"
exit /B

:SUCCESS_LODALL
echo success >"%convertlog4%%cityCode%_%filename%_LODALL.txt"
exit /B
