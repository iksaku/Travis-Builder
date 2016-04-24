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

function do_command($command): bool{
    if(!is_array($command)){
        $command = [$command];
    }
    foreach($command as $cmd){
        exec($cmd, $output, $code);
        if($code > 0){
            return false;
        }
    }
    return true;
}

function get_base(string $string, bool $last = true): string{
    $parts = explode("/", $string);
    return $last ? $parts[count($parts) + 1] : $parts[0];
}

function validEnv(string $var){
    if(!getenv($var) || getenv($var) === null || strlen(getenv($var)) < 1){
        return null;
    }
    return getenv($var);
}

if(getenv("TRAVIS_PULL_REQUEST") !== "false"){
    info("Pull Request detected! Quitting...");
    exit(0);
}

$repo = validEnv("DEPLOY_REPO") ?? getenv("TRAVIS_REPO_SLUG");
$branch = validEnv("DEPLOY_REPO") ?? "travis-build";
$token = validEnv("DEPLOY_TOKEN") ?? false;

# Mess with Build tags
$name_tags = [
    "@number" => "TRAVIS_BUILD_NUMBER",
    "@commit" => "TRAVIS_COMMIT"
];
$build_name = get_base(validEnv("BUILD_NAME") ?? $repo);
foreach($name_tags as $k => $v){
    if(!empty(getenv($v))){
        str_replace($k, $v, $build_name);
    }
}
if(substr($build_name, -5, 5) !== ".phar"){
    $build_name .= ".phar";
}

# Get back to workflow...
if(!$token){
    info("No \"Token\" provided, \"Build\" will not be deployed", 1);
}else{
    info("Build will deploy to repo: " . $repo . ", branch: " . $branch . ". Unless token is invalid...");
}
info("Preparing Build environment...");
@mkdir("build");
# Move files to build
$files = ["resources", "src", "LICENSE", "plugin.yml", "README.md"];
foreach($files as $k => $f){
    if(is_dir($f) or file_exists($f)){
        do_command("mv $f build/$f");
    }else{
        unset($files[$k]);
    }
}
# Download DevTools to build the PHAR
if(!do_command("curl -sL https://github.com/PocketMine/DevTools/releases/download/v1.11.0/DevTools_v1.11.0.phar -o DevTools.phar")){
    info("Couldn't download DevTools. We sorry...", 2);
    exit(1);
}
# Build...
if(!do_command("php -dphar.readonly=0 DevTools.phar --make build --out " . $build_name) && !file_exists($build_name)){
    info("Something went wrong while Building. Sorry! :(", 2);
    exit(1);
}
info("PHAR successfully built!");

if($token !== false){
    info("Deploying...");
    foreach($files as $f){
        do_command("mv build/$f $f");
    }
    if(!do_command([
        "git remote set-url origin https://" . $token . "@github.com/" . $repo . ".git",
        "git fetch --all",
        "git pull --all",
        "git config user.name \"TravisBuilder (By @iksaku)\"",
        "git config user.email \"iksaku@me.com\"",
        "git config push.default simple",
        "git checkout -b " . $branch,
    ])){
        info("Something went wrong while configuring Git Repo", 2);
        exit(1);
    }
    exec("git ls-files", $output);
    foreach($output as $f){
        if(is_file($f) and strpos($f, "/") !== false){
            $f = explode("/", $f)[0];
        }
        if($f !== ".gitignore" and $f !== "test.php"){
            exec("git rm -rf " . $f);
        }
    }
    exec("git ls-files", $output);
    if(!do_command([
        "git add " . $build_name,
        "git commit -m \"(" . getenv("TRAVIS_BUILD_NUMBER") . ") New Build! Revision: " . getenv("TRAVIS_COMMIT") . "\"",
        "git push",
    ])){
        info("Something went wrong while deploying. Is your Token/Information still valid?", 2);
        exit(1);
    }

    info("Successfully Deployed build. Enjoy!");
}