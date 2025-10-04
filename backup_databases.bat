@echo off
REM 데이터베이스 백업 스크립트
set DATE=%date:~0,4%%date:~5,2%%date:~8,2%
set BACKUP_DIR=C:\backup

REM 백업 디렉토리 생성
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo 백업 시작: %DATE%

REM 개별 데이터베이스 백업
echo jtechel 데이터베이스 백업 중...
C:\xampp\mysql\bin\mysqldump.exe -u root jtechel > "%BACKUP_DIR%\jtechel_%DATE%.sql"

REM 다른 회사 데이터베이스들도 추가 가능
REM C:\xampp\mysql\bin\mysqldump.exe -u root company_b > "%BACKUP_DIR%\company_b_%DATE%.sql"

REM 전체 데이터베이스 백업 (주간)
if %date:~6,1%==0 (
    echo 전체 데이터베이스 백업 중...
    C:\xampp\mysql\bin\mysqldump.exe --all-databases > "%BACKUP_DIR%\full_backup_%DATE%.sql"
)

echo 백업 완료: %DATE%
pause
