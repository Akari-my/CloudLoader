<?php

namespace Mellooh\CloudLoader\module;

use RuntimeException;

class PluginYmlReader{

    public function read(string $pluginYmlPath): array{
        $data = @yaml_parse_file($pluginYmlPath);
        if(!is_array($data)){
            throw new RuntimeException("Invalid plugin.yml: $pluginYmlPath");
        }
        return $data;
    }
}