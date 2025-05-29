<?php
// Path to the batch file
$batchFilePath = realpath(__DIR__ . '/../send_reminders.bat');
$taskName = "AppointmentReminderTask";
$taskDescription = "Sends appointment reminders 24 hours before scheduled appointments";

// Create the XML for the scheduled task
$xml = '<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.2" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <RegistrationInfo>
    <Description>' . $taskDescription . '</Description>
  </RegistrationInfo>
  <Triggers>
    <TimeTrigger>
      <Repetition>
        <Interval>PT1H</Interval>
        <StopAtDurationEnd>false</StopAtDurationEnd>
      </Repetition>
      <StartBoundary>' . date('Y-m-d\TH:i:s') . '</StartBoundary>
      <Enabled>true</Enabled>
    </TimeTrigger>
  </Triggers>
  <Principals>
    <Principal id="Author">
      <LogonType>InteractiveToken</LogonType>
      <RunLevel>LeastPrivilege</RunLevel>
    </Principal>
  </Principals>
  <Settings>
    <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
    <DisallowStartIfOnBatteries>false</DisallowStartIfOnBatteries>
    <StopIfGoingOnBatteries>false</StopIfGoingOnBatteries>
    <AllowHardTerminate>true</AllowHardTerminate>
    <StartWhenAvailable>true</StartWhenAvailable>
    <RunOnlyIfNetworkAvailable>false</RunOnlyIfNetworkAvailable>
    <IdleSettings>
      <StopOnIdleEnd>false</StopOnIdleEnd>
      <RestartOnIdle>false</RestartOnIdle>
    </IdleSettings>
    <AllowStartOnDemand>true</AllowStartOnDemand>
    <Enabled>true</Enabled>
    <Hidden>false</Hidden>
    <RunOnlyIfIdle>false</RunOnlyIfIdle>
    <WakeToRun>false</WakeToRun>
    <ExecutionTimeLimit>PT1H</ExecutionTimeLimit>
    <Priority>7</Priority>
  </Settings>
  <Actions Context="Author">
    <Exec>
      <Command>' . $batchFilePath . '</Command>
    </Exec>
  </Actions>
</Task>';

// Save the XML to a temporary file
$xmlFile = tempnam(sys_get_temp_dir(), 'task');
file_put_contents($xmlFile, $xml);

// Create the scheduled task using schtasks command
$command = 'schtasks /create /tn "' . $taskName . '" /xml "' . $xmlFile . '" /f';
exec($command, $output, $returnVar);

// Clean up the temporary XML file
unlink($xmlFile);

if ($returnVar === 0) {
    echo "Successfully created scheduled task for appointment reminders.\n";
    echo "The task will run every hour to check for appointments that are 24 hours away.\n";
} else {
    echo "Failed to create scheduled task. Error code: " . $returnVar . "\n";
    echo "Please make sure you're running this script with administrator privileges.\n";
} 