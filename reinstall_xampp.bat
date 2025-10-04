@echo off
echo XAMPP 재설치 스크립트
echo =====================

echo 1. XAMPP 서비스 중지 중...
net stop apache2.4 2>nul
net stop mysql 2>nul

echo 2. XAMPP 서비스 제거 중...
sc delete apache2.4 2>nul
sc delete mysql 2>nul

echo 3. XAMPP 폴더 삭제 중...
if exist "C:\xampp" (
    rmdir /s /q "C:\xampp"
    echo XAMPP 폴더가 삭제되었습니다.
) else (
    echo XAMPP 폴더가 존재하지 않습니다.
)

echo 4. 완료! 이제 새 XAMPP를 설치하세요.
echo    다운로드: https://www.apachefriends.org/download.html
echo    PHP 7.4 또는 8.0 버전을 권장합니다.

pause
