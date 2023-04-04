rem �J�����g�f�B���N�g�������݂̏ꏊ�Ɉړ�
cd /d %~dp0
rem �����R�[�h�̐ݒ�
chcp 932

rem �f�[�^�t�H���_
set zip_dir=C:\CityGML-validation-function\Data
set importCopyTemp_dir=C:\bat\importCopyTemp
set importConfig_dir=C:\importConfig

set errorlog_dir=%errorlog_dir%

rem ���t��YYYYMMDDHHMMSS�ɂ���
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem �t�@�C����
set filename=%1
if %errorlevel%==1 goto err1

rem �_�u���N�H�[�e�[�V�������폜
set filename=%filename:"=%
if %errorlevel%==1 goto err2

rem ������ID
set cityCode=%2
set cityCode=%cityCode:"=%
if %errorlevel%==1 goto err3




rem ���[�J���ւ̃R�s�[��̃p�X
set copyTempPath=%importCopyTemp_dir%\%cityCode%\%filename%

rem �����̂��Ƃɓǂݍ���config�t�@�C����؂�ւ���
set configPath=%importConfig_dir%\project_%cityCode%.xml

rem ���[�J����GML�t�@�C�����R�s�[����
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
echo �t�@�C�����ݒ�G���[�B�Ǘ��҂֘A�����ĉ�����> %errorlog_dir%\imp_%cityCode%.txt
exit /b

:err2
echo �t�@�C�����ύX�G���[�B�Ǘ��҂֘A�����ĉ������B >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err3
echo ������ID�ݒ�G���[�B�Ǘ��҂֘A�����Ă�������  >%errorlog_dir%\imp_%cityCode%.txt
exit /b

:err4
echo GML�t�@�C���R�s�[�G���[�B�Ǘ��҂֘A�����Ă��������B >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err5
echo ��ƃt�H���_�ړ��G���[�B�ēx���s���Ă��������B >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err6
echo Import�G���[�B�Ǘ��҂֘A�����Ă��������B >"%errorlog_dir%\imp_%cityCode%.txt"

:err7
echo �ꎞ��ƃt�@�C���폜�G���[�B >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err8
echo ����P�[�X�B >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

:err9
:JAVAerror
echo javaerror�B >"%errorlog_dir%\imp_%cityCode%.txt"
exit /b

