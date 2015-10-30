<?php
$deployBranch = !getenv("DEPLOY_BRANCH") ? getenv("TRAVIS_BRANCH") : getenv("DEPLOY_BRANCH");
$token = getenv("TOKEN");
putenv("TOKEN=''");
$buildName = getenv("BUILD_NAME");
$pullRequest = getenv("TRAVIS_PULL_REQUEST") !== false;
if($pullRequest){
    echo "[Info] 'Pull Request' detected, build will not be deployed.";
}else{
    echo "[Info] '$deployBranch' is the target 'Deploy Branch'";
}
if(!$token){
    echo "[Warning] No 'GitHub Token' provided, build will not be deployed.";
}
echo "[Info] '" . (!$buildName ? "DevTools" : $buildName) . "' is the assigned 'Build Name' for the file.";
echo "Setting up environment...";
/*TODO:
 * Setup environment
 *  - Download and run PocketMine-MP
 *  - Install DevTools
 * Exit script if PocketMine-MP Crashes
 * ...
 */

if(!$pullRequest && $token !== false){
    echo "Preparing to deploy...";
    // Move into 'DevTools' directory...
    exec("git init");
    exec("git remote add origin https://$TOKEN@github.com/" . getenv("TRAVIS_REPO_SLUG" . ".git"));
    exec("git fetch origin");
    exec("git config user.name \"LegendsOfMCPE-Bot\"");
    exec("git config user.email \"LoM_Bot@travis.ci\"");
    exec("git add -A");
    echo "Creating commit...";
    exec("git commit -m \"New Build! Revision: " . getenv("TRAVIS_COMMIT") . "\"");
    echo "Pushing commit...";
    exec("git push --force --quiet origin HEAD:$deployBranch");
    echo "Build successfully uploaded!";
}
