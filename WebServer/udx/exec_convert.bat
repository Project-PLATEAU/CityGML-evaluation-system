@echo off
WMIC /NODE:"APserver" /USER:"USERNAME" /PASSWORD: "PASSWORD"  PROCESS CALL CREATE 'cmd.exe /c F:\bat\edit_xml.bat %1 %2 %3'