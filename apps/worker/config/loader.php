<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->tasksDir,
        $config->application->modelsDir,
        $config->application->modelsBaseDir,
        $config->application->libraryDir,
        $config->application->pluginsDir,

        $config->project->libraryDir,
        $config->project->modelsDir,
        $config->project->modelsBaseDir,
    ]
);

//require $config->project->vendorDir . 'autoload.php';

$loader->register();
