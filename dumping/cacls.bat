@echo off
echo Y | %SystemRoot%\system32\cacls.exe C:\Cubit\data /E /T /G postgres:F > c:\pglog.txt
