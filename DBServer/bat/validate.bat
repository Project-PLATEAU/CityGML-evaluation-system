rem �J�����g�f�B���N�g�������݂̏ꏊ�Ɉړ�
cd /d %~dp0
chcp 932

rem ���t��YYYYMMDDHHMMSS�ɂ���
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem �t�@�C����
set filename=%1
if %errorlevel%== 1 goto ERR1

rem �_�u���N�H�[�e�[�V�������폜
set filename=%filename:"=%
if %errorlevel%== 1 goto ERR2

rem ������ID
set cityCode=%2
set cityCode=%cityCode:"=%
if %errorlevel%== 1 goto ERR3

rem ���J�ݒ�
set Release_Status=private

rem 2022 �����̃p�X�͗v����
set output_dir=C:\Apache24\htdocs\iUR_Data
set zip_dir=C:\CityGML-validation-function\Data

rem ���ؑΏ�ZIP�̃t���p�X
set target_zip=%zip_dir%\%cityCode%\OriginalData\3DBuildings\%filename%
if %errorlevel%== 1 goto ERR4

rem ���[�J���ւ̃R�s�[��̃p�X
set copyTempPath=C:\bat\copyTemp\%cityCode%\%filename%

rem ���[�J����GML�t�@�C�����R�s�[����
rem copy /Y "%target_zip%" "%copyTempPath%" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
robocopy %zip_dir%\%cityCode%\OriginalData\3DBuildings\ C:\bat\copyTemp\%cityCode%\ "%filename%"  /R:18 /W:10  2>>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 8 goto ERR5

cd /d "C:\CityGML-validation-function\3DCityDB-Importer-Exporter\lib" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 1 goto ERR6

SET vfilename="impexp-client-4.3.0-rc1.jar"
IF EXIST %vfilename% (
 cd /d "C:\CityGML-validation-function\3DCityDB-Importer-Exporter" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
 java  -Xmx4096m -jar lib/%vfilename% --config=C:\validateConfig\project.xml validate "%copyTempPath%" > "C:\tmp\%cityCode%\%filename%.txt" 2>&1
) else (
 timeout /t 3
 goto ERR5
)
copy "C:\tmp\%cityCode%\%filename%.txt" "C:\bat\validatelog\%cityCode%_%filename%_%ts%.txt" /Y
if %errorlevel%== 1 goto ERR11
robocopy C:\tmp\%cityCode%\ %output_dir%\%cityCode%\ValidateLog\ "%filename%.txt" /MOV /R:18 /W:10 1>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 8 goto ERR7

rem ���[�J���ɃR�s�[����GML�t�@�C��������
del "%copyTempPath%" 2>>"C:\bat\errorLog\%cityCode%_error.txt"
if %errorlevel%== 1 goto ERR8

exit /B

if %errorlevel%== 1 goto ERR9
:ERR1
:�ُ�I��
echo �t�@�C�����ݒ�G���[�B�Ǘ��҂֘A�����ĉ������B >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR2
:�ُ�I��
echo �t�@�C�����ύX�G���[�B�Ǘ��҂֘A�����ĉ������B >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR3
:�ُ�I��
echo ������ID�ݒ�G���[�B�Ǘ��҂֘A�����Ă��������B >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR4
:�ُ�I��
echo ZIP�t�@�C�����ݒ�G���[�B�Ǘ��҂֘A�����Ă��������B >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR5
:�ُ�I��
echo �t�@�C���`�F�b�N�Ɏ��s���܂����B�ēx���s���Ă��������B >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR6
:�ُ�I��
echo ��ƃt�H���_�ړ��G���[�B�Ǘ��҂֘A�����Ă��������B >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"

:ERR7
:�ُ�I��
echo Validate�G���[�B�Ǘ��҂֘A�����Ă��������B >"C:\bat\errorLog\%cityCode%_test.txt"
move /y "C:\bat\errorLog\%cityCode%_test.txt" "%output_dir%%cityCode%\ValidateLog\%filename%.txt"
exit /B

:ERR8
:�ُ�I��
echo �ꎞ��ƃt�@�C���폜�G���[�B >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR9
:�ُ�I��
echo ����P�[�X�B >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR10
:JAVAerror
echo javaerror�B >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B

:ERR11
:�ُ�I��
echo ���؃��O�����O�ۑ��t�H���_�ɃR�s�[���s�B >"C:\bat\errorLog\%cityCode%_test.txt"
exit /B