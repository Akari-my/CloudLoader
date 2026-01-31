<?php

namespace Mellooh\CloudLoader\log;

class LoggerProxy{

    public function __construct(
        private object $logger
    ){}

    public function info(string $message): void{
        $this->logger->info($message);
    }

    public function warning(string $message): void{
        $this->logger->warning($message);
    }

    public function error(string $message): void{
        $this->logger->error($message);
    }

    public function debug(string $message): void{
        if(method_exists($this->logger, "debug")){
            $this->logger->debug($message);
        }
    }
}