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
    echo("\n[" . $tag[$prefix] . "] " . $message);
}

function do_command(string $command): bool{
    exec($command, $output, $status);
    return $status < 1;
}

function get_base(string $string): string{
    $parts = explode("/", $string);
    return $parts[0];
}

function validEnv(string $var){
    if(!getenv($var) || getenv($var) === null || strlen(getenv($var)) > 0){
        return false;
    }
    return getenv($var);
}

function ensureEnv(string $default, string $otherwise): string{
    return validEnv($default) !== false ? $default : $otherwise;
}

if(getenv("TRAVIS_PULL_REQUEST") !== "false"){
    info("Pull Request detected! Quitting...");
    exit(0);
}


$repo = ensureEnv("DEPLOY_REPO", "TRAVIS_REPO_SLUG");
$branch = ensureEnv("DEPLOY_REPO", "travis-build");
$token = ensureEnv("DEPLOY_TOKEN", false);
var_dump([$repo, $branch, $token]);

# Mess with Build tags
$name_tags = [
    "@number" => "TRAVIS_BUILD_NUMBER",
    "@commit" => "TRAVIS_COMMIT"
];
$build_name = get_base(ensureEnv("BUILD_NAME", $repo));
foreach($name_tags as $k => $v){
    if(!empty(getenv($v))){
        str_replace($k, $v, $build_name);
    }
}
if(substr($build_name, -5, 5) !== ".phar"){
    $build_name .= ".phar";
}
echo "\n\n" . $build_name . "\n\n";

# Get back to workflow...
if(!$token){
    info("No \"Token\" provided, \"Build\" will not be deployed", 1);
}else{
    info("Build will deploy to repo: " . $repo . ", branch: " . $branch . ". Unless token is invalid...");
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
if(!do_command("php -dphar.readonly=0 DevTools.phar --make build --out " . $build_name)){
    info("Something went wrong while Building. Sorry! :(", 2);
    exit(1);
}

info("Deploying...");
foreach([
    "git init",
    "git remote add origin https://" . $token . "@github.com/" . $repo,
    "git fetch --all",
    "git config.user.name \"TravisBuilder (By @iksaku)\"",
    "git config.user.email \"iksaku@me.com\"",
    "git add " . $build_name,
    "git commit -m \"New Build! Revision: " . getenv("TRAVIS_COMMIT") . "\"",
    "git push --force --quiet origin HEAD:" . $branch,
        ] as $cmd){
    if(!do_command($cmd)){
        info("Something went wrong while deploying. Is your Token/Information still valid?", 2);
        exit(1);
    }
}

info("Successfully Deployed build. Enjoy!");