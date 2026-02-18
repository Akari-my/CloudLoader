<?php
declare(strict_types=1);

namespace Mellooh\CloudLoader\core;

use DevTools\FolderPluginLoader;
use Mellooh\CloudLoader\CloudLoader;
use Mellooh\CloudLoader\config\CloudLoaderConfig;
use Mellooh\CloudLoader\log\LoggerProxy;
use Mellooh\CloudLoader\module\DependencyResolver;
use Mellooh\CloudLoader\module\LoadReport;
use Mellooh\CloudLoader\module\ModuleInfo;
use Mellooh\CloudLoader\module\ModuleLoader;
use Mellooh\CloudLoader\module\ModuleRegistry;
use Mellooh\CloudLoader\module\ModuleScanResult;
use Mellooh\CloudLoader\module\ModuleScanner;
use Mellooh\CloudLoader\module\ResolutionResult;
use Mellooh\CloudLoader\module\StagingArea;
use pocketmine\command\CommandSender;

final class CloudModuleManager{

    private LoggerProxy $log;
    private ModuleRegistry $registry;

    private ?ModuleScanResult $lastScan = null;
    private ?ResolutionResult $lastResolution = null;
    private ?LoadReport $lastReport = null;

    private bool $bootResetDone = false;

    public function __construct(
        private CloudLoader $plugin,
        private CloudLoaderConfig $settings
    ){
        $this->log = new LoggerProxy($this->plugin->getLogger());
        $this->registry = new ModuleRegistry($this->plugin->getDataFolder() . "modules.yml");
    }

    public function reloadModules(): void{
        $pm = $this->plugin->getServer()->getPluginManager();
        $folderLoader = new FolderPluginLoader($this->plugin->getServer()->getLoader());

        $scanner = new ModuleScanner($this->log, $this->settings->logs(), $folderLoader);
        $scan = $scanner->scan($this->getModulesDir());
        $this->lastScan = $scan;

        $errorCount = 0;
        foreach($scan->errors as $error){
            $errorCount++;
            if($this->settings->logs()->errors){
                $this->log->error($error);
            }
        }

        $discovered = count($scan->modules);

        $allowedModules = [];
        $skippedDisabled = 0;

        foreach($scan->modules as $name => $module){
            if(!$this->registry->isAllowed($name)){
                $skippedDisabled++;
                continue;
            }
            $allowedModules[$name] = $module;
        }

        $alreadyPresent = [];
        foreach($allowedModules as $name => $_m){
            if($pm->getPlugin($name) !== null){
                $alreadyPresent[$name] = true;
            }
        }

        $resolver = new DependencyResolver($pm, $this->log, $this->settings->logs());
        $resolution = $resolver->resolve($allowedModules);
        $this->lastResolution = $resolution;

        $staging = new StagingArea($this->plugin->getDataFolder() . ".staging" . DIRECTORY_SEPARATOR, $this->log);

        if($this->settings->stagingResetOnStart() && !$this->bootResetDone){
            $staging->reset();
            $this->bootResetDone = true;
        }else{
            @mkdir($staging->path(), 0777, true);
        }

        $loader = new ModuleLoader(
            $pm,
            $this->log,
            $this->settings->logs(),
            $staging,
            $this->settings->stagingStrategy()
        );

        $staging->reset();
        $staged = $loader->stageAll($resolution, $alreadyPresent);
        $loader->loadFromStaging($staging->path());
        $enabled = $loader->enableOrdered($resolution);

        $skippedMissingDeps = count($resolution->missingDependencies);
        $skippedCycles = count($resolution->cycleNodes);

        $this->lastReport = new LoadReport(
            $discovered,
            count($allowedModules),
            count($alreadyPresent),
            $staged,
            $enabled,
            $skippedDisabled,
            $skippedMissingDeps,
            $skippedCycles,
            $errorCount
        );

        $this->printSummary($resolution);
    }

    private function printSummary(ResolutionResult $resolution): void{
        if($this->lastReport === null){
            return;
        }

        $r = $this->lastReport;

        $this->log->info("CloudLoader summary: discovered={$r->discovered}, allowed={$r->allowed}, already_present={$r->alreadyPresent}, staged={$r->staged}, enabled={$r->loadedEnabled}, skipped_disabled={$r->skippedDisabled}, skipped_missing_deps={$r->skippedMissingDeps}, skipped_cycles={$r->skippedCycles}, errors={$r->errors}");

        if($this->settings->logs()->skipped){
            foreach($resolution->missingDependencies as $moduleName => $missing){
                $this->log->warning("Skipped $moduleName: missing dependencies: " . implode(", ", $missing));
            }
            if(count($resolution->cycleNodes) > 0){
                $this->log->warning("Dependency cycle detected among: " . implode(", ", $resolution->cycleNodes));
            }
        }

        if($this->settings->logs()->debug){
            $wm = $this->registry->whitelistMode() ? "on" : "off";
            $this->log->info("CloudLoader registry: whitelist_mode=$wm, enabled=[" . implode(", ", $this->registry->enabledList()) . "], disabled=[" . implode(", ", $this->registry->disabledList()) . "]");
        }
    }

    public function list(CommandSender $sender): void{
        $scan = $this->lastScan;
        if($scan === null){
            $sender->sendMessage("No scan data yet. Run /cloudloader reload");
            return;
        }

        $pm = $this->plugin->getServer()->getPluginManager();

        $names = array_keys($scan->modules);
        sort($names, SORT_STRING);

        $missing = $this->lastResolution?->missingDependencies ?? [];
        $cycles = array_flip($this->lastResolution?->cycleNodes ?? []);

        $sender->sendMessage("Modules: " . count($names));
        foreach($names as $name){
            $state = "FOUND";

            if(!$this->registry->isAllowed($name)){
                $state = "DISABLED";
            }elseif(isset($missing[$name])){
                $state = "MISSING_DEPS";
            }elseif(isset($cycles[$name])){
                $state = "CYCLE";
            }else{
                $p = $pm->getPlugin($name);
                if($p !== null){
                    $state = $p->isEnabled() ? "LOADED" : "PRESENT";
                }else{
                    $state = "PENDING";
                }
            }

            $sender->sendMessage("$name: $state");
        }
    }

    public function status(CommandSender $sender, string $name): void{
        $scan = $this->lastScan;
        if($scan === null){
            $sender->sendMessage("No scan data yet. Run /cloudloader reload");
            return;
        }

        $module = $scan->modules[$name] ?? null;
        if(!$module instanceof ModuleInfo){
            $sender->sendMessage("Module not found: $name");
            return;
        }

        $pm = $this->plugin->getServer()->getPluginManager();

        $sender->sendMessage("Name: " . $module->name);
        $sender->sendMessage("Path: " . $module->path);
        $sender->sendMessage("StagingPath: " . $this->plugin->getDataFolder() . ".staging/" . $module->name);
        $sender->sendMessage("Allowed: " . ($this->registry->isAllowed($name) ? "yes" : "no"));
        $sender->sendMessage("Depend: " . implode(", ", $module->depend));
        $sender->sendMessage("SoftDepend: " . implode(", ", $module->softDepend));
        $sender->sendMessage("LoadBefore: " . implode(", ", $module->loadBefore));

        $p = $pm->getPlugin($name);
        if($p !== null){
            $sender->sendMessage("Loaded: yes, enabled=" . ($p->isEnabled() ? "yes" : "no"));
        }else{
            $sender->sendMessage("Loaded: no");
        }

        $missing = $this->lastResolution?->missingDependencies[$name] ?? null;
        if(is_array($missing) && $missing !== []){
            $sender->sendMessage("SkippedReason: missing deps: " . implode(", ", $missing));
        }

        $cycles = $this->lastResolution?->cycleNodes ?? [];
        if(in_array($name, $cycles, true)){
            $sender->sendMessage("SkippedReason: dependency cycle");
        }
    }

    public function graph(CommandSender $sender): void{
        $scan = $this->lastScan;
        if($scan === null){
            $sender->sendMessage("No scan data yet. Run /cloudloader reload");
            return;
        }

        $names = array_keys($scan->modules);
        sort($names, SORT_STRING);

        foreach($names as $name){
            $m = $scan->modules[$name];
            $deps = array_values(array_unique(array_merge($m->depend, $m->softDepend)));
            sort($deps, SORT_STRING);
            $sender->sendMessage($name . " -> " . ($deps === [] ? "(none)" : implode(", ", $deps)));
        }
    }

    public function enableModule(CommandSender $sender, string $name): void{
        $this->registry->enable($name);

        $pm = $this->plugin->getServer()->getPluginManager();
        $p = $pm->getPlugin($name);

        if($p !== null){
            if(!$p->isEnabled()){
                $pm->enablePlugin($p);
                $sender->sendMessage("Enabled plugin: $name");
            }else{
                $sender->sendMessage("Already enabled: $name");
            }
        }else{
            $sender->sendMessage("Enabled in registry: $name. Run /cloudloader reload");
        }
    }

    public function disableModule(CommandSender $sender, string $name): void{
        $this->registry->disable($name);

        $pm = $this->plugin->getServer()->getPluginManager();
        $p = $pm->getPlugin($name);

        if($p !== null && $p->isEnabled()){
            $pm->disablePlugin($p);
            $sender->sendMessage("Disabled plugin: $name (restart recommended)");
            return;
        }

        $sender->sendMessage("Disabled in registry: $name");
    }

    public function getModulesDir(): string{
        $dir = trim($this->settings->dir(), "/\\");
        return $this->plugin->getDataFolder() . $dir . DIRECTORY_SEPARATOR;
    }

    public function getSettings(): CloudLoaderConfig{
        return $this->settings;
    }
}