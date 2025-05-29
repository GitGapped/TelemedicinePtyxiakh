<?php
$taskName = "AppointmentReminderTask";

// Remove the scheduled task using schtasks command
$command = 'schtasks /delete /tn "' . $taskName . '" /f';
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    echo "Successfully removed the appointment reminder scheduled task.\n";
} else {
    echo "Failed to remove scheduled task. Error code: " . $returnVar . "\n";
    echo "Please make sure you're running this script with administrator privileges.\n";
} 