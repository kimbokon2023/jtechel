@echo off
echo 서버 배포 스크립트
echo ==================

echo 1. 로컬에서 서버로 파일 복사 중...
echo 주의: 이 작업은 서버의 파일을 덮어씁니다!

pause

echo 2. 서버 백업 생성 중...
REM 서버 백업 명령 (FTP 또는 SSH 사용)
REM ftp -s:backup_commands.txt

echo 3. 파일 업로드 중...
REM FTP 업로드 명령
REM ftp -s:upload_commands.txt

echo 4. 배포 완료!
echo 서버에서 테스트해보세요.

pause
