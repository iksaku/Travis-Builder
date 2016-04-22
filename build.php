<?php
if(PHP_VERSION_ID < 70000){
    echo "You must use PHP 7 (or higher) to run this script.";
    exit(7);
}

function info(string $message, int $prefix = 0){
    $tag = ["Info", "Warning", "Error"];
    if(!isset($tag[$prefix])){
        $prefix = 1;
    }
    echo("\n" . $tag[$prefix] . $message);
}

function do_command(string $command): bool{
    exec($command, $output, $status);
    return $status < 0;
}

function get_base(string $string): string{
    exec("basename " . $string, $output);
    return $output;
}

if(getenv("TRAVIS_PULL_REQUEST") !== false){
    info("Pull Request detected! Quitting...");
    exit(0);
}

define("REPO", getenv("DEPLOY_REPO") ?? getenv("TRAVIS_REPO_SLUG"));
define("BRANCH", getenv("DEPLOY_BRANCH") ?? "travis-build");
define("TOKEN", getenv("DEPLOY_TOKEN") ?? false);

# Mess with Build tags
$name_tags = [
    "@number" => "TRAVIS_BUILD_NUMBER",
    "@commit" => "TRAVIS_COMMIT"
];
$build_name = getenv("BUILD_NAME") ?? get_base(REPO);
foreach($name_tags as $k => $v){
    if(!empty(getenv($v))){
        str_replace($k, $v, $build_name);
    }
}
if(substr($build_name, -5, 5) !== ".phar"){
    $build_name .= ".phar";
}
define("BUILD_NAME", $build_name);

# Get back to workflow...
if(!TOKEN){
    info("No \"Token\" provided, \"Build\" will not be deployed", 1);
}else{
    info("Build will deploy to repo: " . REPO . ", branch: " . BRANCH . ". Unless token is invalid...");
}
info("Preparing Build environment...");
@mkdir("build");
# Move files to build
foreach(["resources", "src", "LICENSE", "plugin.yml", "README.md"] as $f){
    if(is_dir($f) or file_exists($f)){
        do_command("mv $f build/$f");
    }
}
# Download DevTools to build the PHAR
if(!do_command("curl -sL https://github.com/PocketMine/DevTools/releases/download/v1.11.0/DevTools_v1.11.0.phar -o DevTools.phar")){
    info("Couldn't download DevTools. We sorry...", 2);
    exit(1);
}
# Build...
if(!do_command("php -dphar.readonly=0 DevTools.phar --make build --out " . BUILD_NAME)){
    info("Something went wrong while Building. Sorry! :(", 2);
    exit(1);
}

info("Deploying...");
foreach([
    "git init",
    "git remote add origin https://" . TOKEN . "@github.com/" . REPO,
    "git fetch --all",
    "git config.user.name \"TravisBuilder (By @iksaku)\"",
    "git config.user.email \"iksaku@me.com\"",
    "git add " . BUILD_NAME,
    "git commit -m \"New Build! Revision: " . getenv("TRAVIS_COMMIT") . "\"",
    "git push --force --quiet origin HEAD:" . BRANCH,
        ] as $cmd){
    if(!do_command($cmd)){
        info("Something went wrong while deploying. Is your Token/Information still valid?", 2);
        exit(1);
    }
}

info("Successfully Deployed build. Enjoy!");