<?php
$deployBranch = !getenv("DEPLOY_BRANCH") ? getenv("TRAVIS_BRANCH") : getenv("DEPLOY_BRANCH");
$token = getenv("TOKEN");
putenv("TOKEN=''");
$pullRequest = getenv("TRAVIS_PULL_REQUEST") === false;
$travisDir = rtrim(getenv("TRAVIS_BUILD_DIR"), "/");
$rootDir = explode("/", $travisDir);
    array_pop($rootDir);
    $rootDir = implode("/", $rootDir);
$serverDir = "$rootDir/server";
$pharPath = "$rootDir/build";
putenv("PHAR_PATH=$pharPath");

function info($echo, $type = 0){
    switch($type){
        case 0:
        default:
            $type = "Info";
            break;
        case 1:
            $type = "Warning";
            break;
    }
    echo("[$type] $echo\n");
}
function createDir($dir){
    if(!is_dir($dir)){
        mkdir($dir);
    }
}

if($pullRequest){
    info("'Pull Request' detected, build will not be deployed.");
}else{
    info("'$deployBranch' is the target Deploy-Branch");
}
if(!$token){
    info("No 'GitHub Token' provided, build will not be deployed.", 1);
}

info("Setting up environment...");
chdir($rootDir);
createDir("$serverDir/");
createDir("$pharPath/");
chdir("$serverDir/");
createDir("$serverDir/plugins/");
exec("cp -r $travisDir/travis/TravisBuilder.php $serverDir/plugins/");
$pl = getenv("TRAVIS_REPO_SLUG");
    $pl = explode("/", $pl);
    $pl = array_pop($pl);
print_r(array_pop(explode("/", getenv("TRAVIS_REPO_SLUG"))) . "\n");
exec("cp -r $travisDir/ $serverDir/plugins/$pl/");
exec("curl -sL get.pocketmine.net | bash -s - -v " . (getenv("PM_VERSION") !== false ? getenv("PM_VERSION") : "stable"));

info("Starting PocketMine-MP...");
$server = proc_open(PHP_BINARY . "PocketMine-MP.phar --no-wizard --disable-readline", [
    0 => ["pipe" => "w"],
], $pipes);
while(!feof($pipes[0])){
    echo fgets($pipes[0]);
}
fclose($pipes[0]);
info("PocketMine-MP stopped: " . proc_close($server));
if(!getenv("PHAR_CREATED")){
    echo "[Error] Plugin PHAR was not created!";
    exit(1);
}
info("Plugin PHAR successfully created!");

if(is_dir($pharPath) && !$pullRequest && $token !== false){
    info("Preparing to deploy...");
    chdir("$pharPath/");
    exec("git init");
    exec("git remote add origin https://$TOKEN@github.com/" . getenv("TRAVIS_REPO_SLUG"));
    exec("git fetch origin");
    exec("git config user.name \"iksaku's BuilderBot\"");
    exec("git config user.email \"iksaku_Bot@travis.ci\"");
    exec("git add .");
    info("Creating commit...");
    exec("git commit -m \"New Build! Revision: " . getenv("TRAVIS_COMMIT") . "\"");
    info("Pushing commit...");
    exec("git push --force --quiet origin HEAD:$deployBranch");
    info("Build successfully uploaded!");
}

exit(0);