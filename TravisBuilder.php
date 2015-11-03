<?php
/**
 * @name TravisBuilder
 * @main TravisBuilder\Loader
 * @version 1.0.0
 * @api 1.12.0
 * @description Makes PHAR files out of DevTools inside a Travis build
 * @author iksaku
 */
 
namespace TravisBuilder{

    use pocketmine\plugin\PluginBase;
    use pocketmine\scheduler\PluginTask;

    class Loader extends PluginBase{
        public function onEnable(){
            $plugin = array_diff($this->getServer()->getPluginManager()->getPlugins(), ["TravisBuilder"])[0];
            if(count($plugin) < 1){
                echo "[Error] No plugin found! Aborting...";
                $this->getServer()->forceShutdown();
            }else{
                /** @var PluginBase $plugin */
                $plugin = $this->getServer()->getPluginManager()->getPlugin($plugin);
                $this->getServer()->getScheduler()->scheduleDelayedTask(new BuilderTask($this, $plugin), 20);
            }
        }

        /**
         * @param PluginBase $plugin
         * @return string
         */
        public function pharName(PluginBase $plugin){
            $name = getenv("BUILD_NAME");
            if(!$name){
                return $plugin->getName();
            }
            return $plugin->getName();
        }
    }

    class BuilderTask extends PluginTask{
        /** @var Loader */
        private $core;
        /** @var string */
        public $plugin;

        /**
         * @param Loader $core
         * @param PluginBase $plugin
         */
        public function __construct(Loader $core, PluginBase $plugin){
            parent::__construct($plugin);
            $this->core = $core;
            $this->plugin = $plugin;
        }

        /**
         * @return Loader
         */
        public function getPlugin(){
            return $this->core;
        }

        public function onRun($currentTick){
            $description = $this->plugin->getDescription();
            $pluginPath = $this->getPlugin()->getServer()->getPluginPath() . $this->plugin->getDataFolder() . "/";
            $pharPath = getenv("PHAR_PATH") . "/" . $description->getName() . ".phar";
            if(!is_dir($pharPath)){
                mkdir($pharPath);
            }
            $phar = new \Phar($pharPath);
            $phar->setMetadata([
                "name" => $description->getName(),
                "version" => $description->getVersion(),
                "main" => $description->getMain(),
                "api" => $description->getCompatibleApis(),
                "depend" => $description->getDepend(),
                "description" => $description->getDescription(),
                "authors" => $description->getAuthors(),
                "website" => $description->getWebsite(),
                "creationDate" => time()
            ]);
            $phar->setStub('<?php echo "PocketMine-MP plugin ' . $description->getName() . ' v' . $description->getVersion() . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}}__HALT_COMPILER();');
            $phar->setSignatureAlgorithm(\Phar::SHA1);
            $phar->startBuffering();
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pluginPath)) as $file){
                $path = ltrim(str_replace(["\\", $pluginPath], ["/", ""], $file), "/");
                if(($path{0} === "." or strpos($path, "/.") !== false) or $file === "plugin.yml" or (strpos($path, "resources") !== false or strpos($path, "src"))){
                    continue;
                }
                $phar->addFile($file, $path);
                echo "[Info] Adding $path";
            }
            foreach($phar as $file => $finfo){
                /** @var \PharFileInfo $finfo */
                if($finfo->getSize() > (1024 * 512)){
                    $finfo->compress(\Phar::GZ);
                }
            }
            $phar->stopBuffering();
            putenv("PHAR_CREATED=true");
            echo "[Info] PHAR file successfully created! Stopping server...";
            $this->getPlugin()->getServer()->shutdown();
            return true;
        }
    }
}