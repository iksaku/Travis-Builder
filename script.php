<?php
$deployBranch = !getenv("DEPLOY_BRANCH") ? getenv("TRAVIS_BRANCH") : getenv("DEPLOY_BRANCH");
$token = getenv("TOKEN");
putenv("TOKEN=''");
$pullRequest = getenv("TRAVIS_PULL_REQUEST") === false;
$pluginFolder = substr(getcwd(), strrpos(getcwd(), "/") + 1);
chdir("..");
createDir("plugins");
exec("mv $pluginFolder plugins/");
copy("plugins/$pluginFolder/travis/TravisBuilder.php", "plugins/TravisBuilder.php");

function info($echo, $type = 0){
	switch($type){
		case 0:
		default:
			$type = "Info";
			break;
		case 1:
			$type = "Warning";
			break;
		case 2:
			$type = "Error";
			break;
	}
	echo("[$type] $echo\n");
}
function createDir($dir){
	if(!is_dir($dir)){
		mkdir($dir, 0777, true);
	}
}
function pm_version(){ // TODO: Fetch from github and jenkins :3
	$v = getenv("PM_VERSION");
	switch(strtolower("$v")){ // To string...
		case "stable":
		case "beta":
		case "development":
			break;
		default:
			$v = "stable";
			break;
	}
	return $v;
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
exec("pecl install channel://pecl.php.net/pthreads-2.0.10 && pecl install channel://pecl.php.net/weakref-0.2.6 && echo | pecl install channel://pecl.php.net/yaml-1.1.1");
info("Starting PocketMine-MP...");
/*$server = proc_open(PHP_BINARY . " ./PocketMine-MP.phar --no-wizard --disable-readline", [ // How to? just upload your preferred PMMP phar to use ;D
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "w"]
], $pipes);
while(!feof($pipes[1])){
	echo fgets($pipes[1]);
}
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
info("PocketMine-MP stopped: " . proc_close($server));*/
if(!getenv("PHAR_CREATED")){
	info("Plugin PHAR was not created!");
	exit(1);
}
info("Plugin PHAR successfully created!");

if(is_dir("build") && !$pullRequest && $token !== false){
	info("Preparing to deploy...");
	chdir("build/");
	exec("git init");
	exec("git remote add origin https://$TOKEN@github.com/" . getenv("TRAVIS_REPO_SLUG"));
	exec("git fetch origin $deployBranch");
	exec("git config user.name \"iksaku's BuilderBot\"");
	exec("git config user.email \"iksakuBuilder@bot-travis.ci\"");
	exec("git add .");
	info("Creating commit...");
	exec("git commit -m \"New Build! Revision: " . getenv("TRAVIS_COMMIT") . "\"");
	info("Pushing commit...");
	exec("git push --force --quiet origin HEAD:$deployBranch");
	info("Build successfully uploaded!");
}

exit(0);
