<?php

spl_autoload_register(
    function ($fqn) {
        $classPath = explode('\\', $fqn);
        if ($classPath[0] !== 'Vinculado') {
            return;
        }
        // Remove the top level namespace
        array_shift($classPath);
        $baseFilePath = __DIR__;
        $filePath = $baseFilePath;

        foreach ($classPath as $index => $directoryOrFile) {
            if (count($classPath) > ($index + 1)) {
                $filePath .= '/' . strtolower($directoryOrFile);
            } else {
                $filePath .= '/' . $directoryOrFile . '.php';
            }
        }

        require_once $filePath;
    }
);
