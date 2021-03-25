@echo off
REM APサーバーのtest.batを実行してtest.batと同じフォルダにtest.txtを出力する。一番最後の'%1'が(ファイル名)、'%2'が(自治体ID)でtest.txtに出力される。
WMIC /NODE:'DBServer' /USER:'USERNAME' /PASSWORD: 'PASSWORD'  PROCESS CALL CREATE 'cmd.exe /c F:\bat\validate.bat %1 %2'
