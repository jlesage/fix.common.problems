#!/usr/bin/php
<?PHP

###############################################################
#                                                             #
# Fix Common Problems copyright 2015-2016, Andrew Zawadzki    #
#                                                             #
###############################################################


require_once("/usr/local/emhttp/plugins/fix.common.problems/include/paths.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/fix.common.problems/include/tests.php");

exec("mkdir -p ".$fixPaths['tempFiles']);

##################################################################################################################
#                                                                                                                #
# Global variables.  All test functions are standalone, but for ease of use, the following globals are available #
#                                                                                                                #
##################################################################################################################

if ( $argv[1] == "troubleshoot" ) {
  $troubleshooting = true;
  logger("Fix Common Problems: Troubleshooting scan running");
  $uptime = exec("uptime");
  logger("Fix Common Problems: Uptime: $uptime");
  unset($output);
  exec("free",$output);
  foreach ($output as $line) {
    logger("Fix Common Problems: $line");
  }

  logger("Fix Common Problems: ps aux output (only CPU % > 0)");  
  unset($output);
  exec("ps aux",$output);
  logger($output[0]);
  unset($output[0]);

  foreach ($output as $line) {
    $statusLine = preg_replace('!\s+!', ' ', $line);
    $test = explode(" ",$statusLine);

    if ( $test[2] > 0 ) {
      logger("Fix Common Problems: $line");
    }
  }


  
} else {
  $disableNotifications = $argv[1];
}

$autoUpdateOverride              = is_file("/boot/config/plugins/fix.common.problems/autoupdate-warning");
$developerMode                   = is_file("/boot/config/plugins/fix.common.problems/developer");
$communityApplicationsInstalled  = is_file("/var/log/plugins/community.applications.plg");
$dockerRunning                   = is_dir("/var/lib/docker/tmp");

$fixSettings = readJsonFile($fixPaths['settings']);
$ignoreList = readJsonFile($fixPaths['ignoreList']);

if ( ! $fixSettings['notifications'] ) { $fixSettings['notifications'] = "all"; }
if ( ! $fixSettings['disableSpinUp'] ) { $fixSettings['disableSpinUp'] = "false"; }

# download updated appfeed if necessary

if ( is_file($fixPaths['templates']) ) {
  $templates = readJsonFile($fixPaths['templates']);
  $updatedTime = $templates['last_updated_timestamp'];
  $tempFile = randomFile("/tmp/fix.common.problems");
  download_url($fixPaths['application-feed-last-updated'],$tempFile);
  $newList = readJsonFile($tempFile);
  @unlink($tempFile);
  if ( $newList['last_updated_timestamp'] != $templates['last_updated_timestamp'] ) {
    download_url($fixPaths['application-feed'],$fixPaths['templates']);
    $templates = readJsonFile($fixPaths['templates']);
  }
} else {
  download_url($fixPaths['application-feed'],$fixPaths['templates']);
  $templates = readJsonFile($fixPaths['templates']);
}


# start main

if ( $troubleshooting ) {
  varLogFilled();
  rootfsFull();
} else {
  isArrayStarted();
  impliedArrayFilesOnCache();
  cacheOnlyFilesOnArray();
  arrayOnlyFilesOnCache();
  pluginUpdateCheck();
  dockerUpdateCheck();
  autoUpdateCheck();
  sameShareDifferentCase();
  powerdownInstalled();
  outsideCommunication();   
  writeToDriveTest();
  dockerImageOnDiskShare();
  dockerAppdataCacheOnly();
  disabledDisksPresent();
  missingDisksPresent();
  readErrorsPresent();      
  fileSystemErrors();
  SSDinArray();
  pluginsUpToDate();
  incompatiblePackagesPresent();
  dockerUpToDate();
  dockerConfigUserShare();
  varLogFilled();
  dockerImageFull();
  rootfsFull();
  dateTimeOK();
  scheduledParityChecks();
  shareIncludeExcludeSet();
  shareIncludeExcludeSameDisk();
  UDmountedSlaveMode();
  supportedFileSystemCheck();
  FTPrunning();
  checkNotifications();
  blacklistedPluginsInstalled();
  unknownPluginInstalled();
  dockerAppsChangedPorts();
  illegalShareName();
#  HPApresent();
  flashDriveFull();
  cacheFloorTests();
  sharePermission();
  uncleanReboot();
}

###################################################################
#                                                                 #
# Execute any custom scripts at /boot/fix.common.problems/scripts #
#                                                                 #
###################################################################

$allScripts = array_diff(scandir("/boot/config/plugins/fix.common.problems/scripts"),array(".",".."));

foreach ($allScripts as $script) {
  if ( $script == "sample.php" ) { 
    continue;
  }
  if ( is_executable("/boot/config/plugins/fix.common.problems/scripts/$script") ) {
    exec("/boot/config/plugins/fix.common.problems/scripts/$script");
  }
}


if ( ! $errors && ! $warnings && ! $otherWarnings ) {
  @unlink($fixPaths['errors']);
} else {
  $allErrors['errors'] = $errors;
  $allErrors['warnings'] = $warnings;
  $allErrors['other'] = $otherWarnings;
  $allErrors['ignored'] = $ignored;
  
  writeJsonFile($fixPaths['errors'],$allErrors);
  if ( $errors ) {
    foreach ($errors as $error) {
      $message .= "**** ".strip_tags($error['error'])." ****   ";
    }
  }
  if ( $warnings ) {
    foreach ($warnings as $warning) {
      $message .= "**** ".strip_tags($warning['error'])." ****   ";
    }
  }
  if ( ! $disableNotifications ) {
    if ( $errors ) {
      if ( $fixSettings['notifications'] != "disabled" ) {
        notify("Fix Common Problems","Errors have been found with your server.","Investigate at Settings / User Utilities / Fix Common Problems",$message,"alert");
      }
    } else {
      if ( $warnings ) {
        if ($fixSettings['notifications'] != "errors" ) {
          notify("Fix Common Problems","Warnings have been found with your server.","Investigate at Settings / User Utilities / Fix Common Problems",$message,"warning");
        } 
      }
    }
  }
}    
?>