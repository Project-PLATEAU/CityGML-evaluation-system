@echo off
REM AP�T�[�o�[��test.bat�����s����test.bat�Ɠ����t�H���_��test.txt���o�͂���B��ԍŌ��'%1'��(�t�@�C����)�A'%2'��(������ID)��test.txt�ɏo�͂����B
WMIC /NODE:'DBServer' /USER:'USERNAME' /PASSWORD: 'PASSWORD'  PROCESS CALL CREATE 'cmd.exe /c F:\bat\validate.bat %1 %2'
