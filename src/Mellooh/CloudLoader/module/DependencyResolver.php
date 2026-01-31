<?php

namespace Mellooh\CloudLoader\module;

use Mellooh\CloudLoader\log\LoggerProxy;
use Mellooh\CloudLoader\log\LogSettings;
use pocketmine\plugin\PluginManager;

class DependencyResolver{

    public function __construct(private PluginManager $pluginManager, private LoggerProxy $logger, private LogSettings $logs){}

    public function resolve(array $modulesByName): ResolutionResult{
        $missing = [];
        $eligible = $modulesByName;

        foreach($modulesByName as $name => $module){
            $missingHere = $this->missingExternalHardDeps($module, $modulesByName);
            if($missingHere !== []){
                $missing[$name] = $missingHere;
                unset($eligible[$name]);
            }
        }

        $changed = true;
        while($changed){
            $changed = false;

            foreach($eligible as $name => $module){
                $missingHere = [];
                foreach($module->depend as $dep){
                    if(isset($eligible[$dep])){
                        continue;
                    }
                    $p = $this->pluginManager->getPlugin($dep);
                    if($p !== null && $p->isEnabled()){
                        continue;
                    }
                    $missingHere[] = $dep;
                }

                if($missingHere !== []){
                    $missing[$name] = array_values(array_unique(array_merge($missing[$name] ?? [], $missingHere)));
                    unset($eligible[$name]);
                    $changed = true;
                }
            }
        }

        $loadBeforeMap = [];
        foreach($eligible as $aName => $a){
            foreach($a->loadBefore as $target){
                if(isset($eligible[$target])){
                    $loadBeforeMap[$target][] = $aName;
                }
            }
        }

        $edges = [];
        $inDegree = [];

        foreach($eligible as $name => $_module){
            $edges[$name] = [];
            $inDegree[$name] = 0;
        }

        foreach($eligible as $name => $module){
            $deps = [];

            foreach($module->depend as $d){
                if(isset($eligible[$d])){
                    $deps[] = $d;
                }
            }

            foreach($module->softDepend as $s){
                if(isset($eligible[$s])){
                    $deps[] = $s;
                }
            }

            foreach(($loadBeforeMap[$name] ?? []) as $lb){
                if(isset($eligible[$lb])){
                    $deps[] = $lb;
                }
            }

            $deps = array_values(array_unique($deps));

            foreach($deps as $dep){
                $edges[$dep][] = $name;
                $inDegree[$name]++;
            }
        }

        $queue = [];
        foreach($inDegree as $name => $deg){
            if($deg === 0){
                $queue[] = $name;
            }
        }

        $order = [];
        while($queue !== []){
            $n = array_shift($queue);
            $order[] = $eligible[$n];
            foreach($edges[$n] as $m){
                $inDegree[$m]--;
                if($inDegree[$m] === 0){
                    $queue[] = $m;
                }
            }
        }

        $cycleNodes = [];
        foreach($inDegree as $name => $deg){
            if($deg > 0){
                $cycleNodes[] = $name;
            }
        }

        if($this->logs->loadOrder){
            $names = array_map(static fn(ModuleInfo $m) => $m->name, $order);
            $this->logger->info("load order: " . implode(" -> ", $names));
        }

        return new ResolutionResult($order, $missing, $cycleNodes);
    }

    private function missingExternalHardDeps(ModuleInfo $module, array $modulesByName): array{
        $missing = [];
        foreach($module->depend as $dep){
            if(isset($modulesByName[$dep])){
                continue;
            }
            $p = $this->pluginManager->getPlugin($dep);
            if($p !== null && $p->isEnabled()){
                continue;
            }
            $missing[] = $dep;
        }
        return array_values(array_unique($missing));
    }
}