@echo off
REM AP�T�[�o�[��test.bat�����s����test.bat�Ɠ����t�H���_��test.txt���o�͂���B��ԍŌ��'%1'��(�t�@�C����)�A'%2'��(EPSG)��test.txt�ɏo�͂����B
WMIC /NODE:'APServer' /USER:'USERNAME' /PASSWORD: 'PASSWORD'  PROCESS CALL CREATE 'cmd.exe /c F:\bat\topological_consistency_xml.bat %1 %2 %3'
