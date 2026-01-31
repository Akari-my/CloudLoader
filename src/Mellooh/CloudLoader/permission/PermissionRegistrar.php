<?php
declare(strict_types=1);

namespace cloudloader\CloudLoader\permission;

use Mellooh\CloudLoader\log\LoggerProxy;
use Mellooh\CloudLoader\log\LogSettings;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use ReflectionClass;

final class PermissionRegistrar{

    public function __construct(
        private LoggerProxy $logger,
        private LogSettings $logs
    ){}

    public function register(array $permissions): int{
        $count = 0;

        foreach($permissions as $name => $info){
            if(!is_string($name) || $name === ""){
                continue;
            }

            if(PermissionManager::getInstance()->getPermission($name) !== null){
                continue;
            }

            $desc = "Permission registered by CloudLoader";
            $default = $this->parseDefault("op");
            $children = [];

            if(is_array($info)){
                if(isset($info["description"]) && is_string($info["description"])){
                    $desc = $info["description"];
                }
                if(isset($info["default"]) && is_string($info["default"])){
                    $default = $this->parseDefault($info["default"]);
                }
                if(isset($info["children"]) && is_array($info["children"])){
                    foreach($info["children"] as $child => $allowed){
                        if(is_string($child) && $child !== ""){
                            $children[$child] = (bool)$allowed;
                        }
                    }
                }
            }

            $permission = $this->newPermission($name, $desc, $default, $children);
            PermissionManager::getInstance()->addPermission($permission);
            $count++;

            if($this->logs->permissionsRegistered){
                $this->logger->info("Registered permission: $name");
            }
        }

        return $count;
    }

    private function parseDefault(string $value): string{
        $v = strtolower(trim($value));
        return match($v){
            "true", "all" => defined(Permission::class . "::DEFAULT_TRUE") ? Permission::DEFAULT_TRUE : "true",
            "false" => defined(Permission::class . "::DEFAULT_FALSE") ? Permission::DEFAULT_FALSE : "false",
            "notop", "not_op", "not-op", "nonop", "non-op" => defined(Permission::class . "::DEFAULT_NOT_OP") ? Permission::DEFAULT_NOT_OP : "notop",
            default => defined(Permission::class . "::DEFAULT_OP") ? Permission::DEFAULT_OP : "op"
        };
    }

    private function newPermission(string $name, string $desc, string $default, array $children): Permission{
        $rc = new ReflectionClass(Permission::class);
        $ctor = $rc->getConstructor();
        $argc = $ctor?->getNumberOfParameters() ?? 0;

        $perm = match(true){
            $argc >= 4 => new Permission($name, $desc, $default, $children),
            $argc === 3 => new Permission($name, $desc, $default),
            default => new Permission($name, $desc)
        };

        if(method_exists($perm, "setDefault")){
            $perm->setDefault($default);
        }

        if($children !== []){
            if(method_exists($perm, "addChild")){
                foreach($children as $child => $allowed){
                    $perm->addChild($child, (bool)$allowed);
                }
            }elseif(method_exists($perm, "setChildren")){
                $perm->setChildren($children);
            }
        }

        return $perm;
    }
}