@echo off
WMIC /NODE:"DBServer"  /USER:"USERNAME" /PASSWORD: "PASSWORD"    PROCESS CALL CREATE 'cmd.exe /c F:\bat\import.bat %1 %2'