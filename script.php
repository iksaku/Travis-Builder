<?php
$token = getenv("TOKEN");
$buildName = getenv("BUILD_NAME");
$pullRequest = getenv("TRAVIS_PULL_REQUEST") !== false;
if(!$token){
    echo "[Warning] No 'GitHub Token' provided, build will not be deployed.";
}
echo "[Info] 'Build Name' variable was " . (!$buildName ? "not" : "") . " provided, '" . (!$buildName ? "DevTools" : $buildName) . "' name will be applied to file.";
if($pullRequest){
    echo "[Info] 'Pull Request' detected, build will not be deployed.";
}
echo "Setting up environment...";
// TODO

if(!$pullRequest && $token !== false){
    // Move into 'DevTools' directory...
    // Run 'deploy.sh'
}