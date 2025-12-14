<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\App;
use Slim\Factory\AppFactory;

function createApp(): App
{
    $containerBuilder = new ContainerBuilder();
    $container = $containerBuilder->build();
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    // Base path (adjust if not in document root)
    // $app->setBasePath('/');

    return $app;
}
