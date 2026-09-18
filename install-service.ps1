# This script creates a Windows Service for Laravel backend
# Run as Administrator: Right-click PowerShell and "Run as Administrator"

$serviceName = "LaravelBackendService"
$serviceDisplayName = "Laravel Backend Server"
$serviceDescription = "Runs Laravel backend for Inventory Management System"
$phpPath = (Get-Command php).Source
$laravelPath = "D:\sem4_project\backend"
$port = 8000

# Create a wrapper script that the service will run
$wrapperScript = @"
Set-Location -Path '$laravelPath'
& '$phpPath' artisan serve --host=127.0.0.1 --port=$port
"@

$wrapperScriptPath = "$laravelPath\service-wrapper.ps1"
$wrapperScript | Out-File -FilePath $wrapperScriptPath -Encoding UTF8

# Install NSSM (Non-Sucking Service Manager) is recommended
# Download from: https://nssm.cc/download

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Laravel Service Setup Instructions" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "To create a Windows service for Laravel, you need NSSM:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Download NSSM from: https://nssm.cc/download" -ForegroundColor Green
Write-Host "2. Extract nssm.exe to: C:\nssm\" -ForegroundColor Green
Write-Host "3. Run these commands as Administrator:" -ForegroundColor Green
Write-Host ""
Write-Host "   cd C:\nssm" -ForegroundColor White
Write-Host "   .\nssm.exe install $serviceName powershell.exe" -ForegroundColor White
Write-Host "   .\nssm.exe set $serviceName AppParameters '-ExecutionPolicy Bypass -File `"$wrapperScriptPath`"'" -ForegroundColor White
Write-Host "   .\nssm.exe set $serviceName AppDirectory `"$laravelPath`"" -ForegroundColor White
Write-Host "   .\nssm.exe set $serviceName DisplayName `"$serviceDisplayName`"" -ForegroundColor White
Write-Host "   .\nssm.exe set $serviceName Description `"$serviceDescription`"" -ForegroundColor White
Write-Host "   .\nssm.exe set $serviceName Start SERVICE_AUTO_START" -ForegroundColor White
Write-Host "   .\nssm.exe start $serviceName" -ForegroundColor White
Write-Host ""
Write-Host "Wrapper script created at: $wrapperScriptPath" -ForegroundColor Cyan
