$file = Get-ChildItem $location *task.ps1 -Recurse | Select-Object FullName -ExpandProperty FullName
$taskArg = "-file $($file)"
$location = Get-Location
$password = Read-Host -Prompt "Input password"

$action = New-ScheduledTaskAction -Execute "powershell" -Argument $taskArg -WorkingDirectory $location
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 1)
Register-ScheduledTask -TaskName "santa-schedule" -Trigger $trigger -Action $action -User $env:UserName -Password $password | Out-Null