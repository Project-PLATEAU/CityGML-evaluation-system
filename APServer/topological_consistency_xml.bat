@echo off
rem �����R�[�h��UTF-8�Ɏw��
chcp 932
rem �J�����g�f�B���N�g����bat���s�t�H���_�ֈړ�
cd /d %~dp0

rem ���t��YYYYMMDDHHMMSS�ɂ���
set ts=%time: =0%
set ts=%date:~0,4%%date:~5,2%%date:~8,2%%ts:~0,2%%ts:~3,2%%ts:~6,2%

rem CLI�o�͗p�̃p�X������
set output_dir=*****:/*****/htdocs/iUR_Data/
set temp_dir=C:/CityGML-validation-function/Data/

REM �e����̈����i�t�@�C�����j
set FILENAME=%1
:�_�u���N�H�[�e�[�V�������폜
set FILENAME=%FILENAME:"=%
if %errorlevel%== 1 goto ERR

REM �e����̈����iEPSG�R�[�h�j
REM set EPSGCOORD="EPSG:6677"
set EPSGCOORD=%2
if %errorlevel%== 1 goto ERR

REM �e����̈����i�����̃R�[�h�j
set CITYCOORD=%3
:�_�u���N�H�[�e�[�V�������폜
set CITYCOORD=%CITYCOORD:"=%
if %errorlevel%== 1 goto ERR

rem �Ώۃt�@�C���̃p�X
set target_file=%temp_dir%\%CITYCOORD%\OriginalData\3DBuildings\

rem ���[�J���ւ̃R�s�[��̃p�X
set copyTempPath=C:\bat\topologicalcopyTemp\%CITYCOORD%\

rem ���[�J����GML�t�@�C�����R�s�[����
robocopy %target_file% %copyTempPath% "%FILENAME%"  /R:6 /W:10
if %errorlevel%== 8 goto ERR

rem �Ǘ��p�t���O
set fileflg=file

rem zip�𓀗p�̃t�H���_������������
set ziptemp=%copyTempPath%ziptemp
echo "�𓀐�:%ziptemp%"
echo "�t�H���_���폜"
rd /s /q %ziptemp%

rem zip�̏ꍇ�𓀂�����
echo "%FILENAME%" | find ".zip" >NUL
if not ERRORLEVEL 1 ( GOTO UNZIP ) ELSE ( GOTO ZIPCHECK )

:UNZIP
  powershell -command "Expand-Archive -Path %copyTempPath%%FILENAME% -DestinationPath %ziptemp%"
  echo "ZIP���𓀂��܂���:%ziptemp%"
  set fileflg=zip
  GOTO ZIPCHECK

:ZIPCHECK

rem ���[�J����CityGMLValidation.fmw�t�@�C�����R�s�[����
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
  echo "ZIP����"
  "C:\Program Files\FME\fme.exe" %WORKSPACE_FILE% --INPUT "%ziptemp%[\**\*.gml]" --ADE_XSD_DOC "%urbanObject%" --SRS_AXIS "2<comma>1<comma>3" --COORD "EPSG:6676" --OVER_CHECK "ALL" --OVERLAP "5" --TOLERANCE "0.001" --BOUNDEDBY "Yes" --PREC "3" --PLAN_ANG "20" --PLAN_DIST "0.03" --OUTPUT "%LOGFOLDER%\logs2\output.json" --LOG_FILE "%LOGFOLDER%\logs\%LOGFILE%"
  if %errorlevel%== 1 goto ERR2

) ELSE ( 
  echo "FILE����"
  "C:\Program Files\FME\fme.exe" %WORKSPACE_FILE% --INPUT "%copyTempPath%%FILENAME%" --ADE_XSD_DOC "%urbanObject%" --SRS_AXIS "2<comma>1<comma>3" --COORD "EPSG:6676" --OVER_CHECK "ALL" --OVERLAP "5" --TOLERANCE "0.001" --BOUNDEDBY "Yes" --PREC "3" --PLAN_ANG "20" --PLAN_DIST "0.03" --OUTPUT "%LOGFOLDER%\logs2\output.json" --LOG_FILE "%LOGFOLDER%\logs\%LOGFILE%"
  if %errorlevel%== 1 goto ERR2
)

rem error.txt�����݂���Ȃ�logs���G���[�Ɣ��肵�ăG���[�������s��
if exist %LOGFOLDER%\error\error.txt (
    rem logs�t�H���_����"ERROR"�Ƃ��������񂪂������ꍇ�̏���
    rem �G���[����̂���ZIP�t�@�C���쐬����Web�T�[�o�Ɉړ�
    powershell Compress-Archive -Path %LOGFOLDER%\ -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
    robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10

    echo logs ERROR >"%output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
    rem ���[�J���ɃR�s�[�����t�@�C���̍폜���Ă�
    goto FILEDELETE
)

rem error�t�H���_�Ƀt�@�C������������zip������Web�T�[�o�[�ɑ���
for /f %%a in ('dir /b "%LOGFOLDER%\logs2\invalid\*.json"') do (
 goto ERRORZIP
 )
goto LOGMOVE

:ERRORZIP
:ZIP�쐬
echo "GOTO:ERRORZIP"
powershell Compress-Archive -Path %LOGFOLDER%\logs2\invalid -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10
goto LOGMOVE

:LOGMOVE
echo "GOTO:LOGMOVE"
rem FEM�̏o�͂������O��ێ����Ă���
rem move /y %LOGFOLDER%\logs\%LOGFILE% %LOGFOLDER%\%LOGFILE%
rem robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%LOGFILE%" /R:6 /W:10
echo %LOGFOLDER%\logs\%LOGFILE%
echo %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\
echo "%LOGFILE%"

robocopy %LOGFOLDER%\logs\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%LOGFILE%" /R:6 /W:10
if %errorlevel%== 8 goto ERR

:FILEDELETE
echo "FILEDELETE"
rem ���[�J���ɃR�s�[����GML�t�@�C��������
del /q "%copyTempPath%"
if %errorlevel%== 1 goto ERR

rem ���[�J���ɃR�s�[����FMW�t�@�C��������
del "%WORKSPACE_FILE%"
if %errorlevel%== 1 goto ERR

echo "���튮��"
exit /B

:ERR
:�ُ�I��
echo error >"%output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
exit /B

:ERR2
:�ُ�I��
rem �G���[����̂���ZIP�t�@�C���쐬����Web�T�[�o�Ɉړ�
mkdir %LOGFOLDER%\error
echo �ʑ����؏����N���G���[ >%LOGFOLDER%\error\error.txt
powershell Compress-Archive -Path %LOGFOLDER%\error -DestinationPath %LOGFOLDER%\%FILENAME%_errors.zip -Force
robocopy %LOGFOLDER%\ %output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\ "%FILENAME%_errors.zip" /MOV /R:6 /W:10

echo fme.exe NG >"%output_dir%\%CITYCOORD%\ValidateTopologicalConsistencyLog\%LOGFILE%"
exit /B