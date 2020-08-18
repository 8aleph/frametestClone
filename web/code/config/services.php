<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Application config
 */
$definition = (new Definition)->setAutowired(true)->setPublic(false);
$loader->registerClasses($definition, 'Example\\', '../src/*', '../src/{Controller,Model,View,Tests,routes.php}');

$definition = (new Definition)->setAutowired(true)->setShared(false)->setPublic(true);
$loader->registerClasses($definition, 'Example\\Controller\\', '../src/Controller/*');

$definition = (new Definition)->setAutowired(true)->setShared(false)->setPublic(true);
$loader->registerClasses($definition, 'Example\\Model\\', '../src/Model/*');

$definition = (new Definition)->setAutowired(true)->setShared(false)->setPublic(true);
$loader->registerClasses($definition, 'Example\\View\\', '../src/View/*');

$definition = (new Definition)->setAutowired(true)->setShared(false)->setPublic(true);
$loader->registerClasses($definition, 'Example\\Tests\\', '../tests/*', '../tests/{Database,Traits,Unit,Functional,Bootstrap.php}');

/**
 * Framework config
 */
$definition = (new Definition)->setAutowired(true)->setPublic(false);
$loader->registerClasses(
	$definition,
	'Mini\\',
	'../mini/*',
	'../mini/{Controller,Database,Exception,File,Http,Log,Model,Util,View,helpers.php}'
);

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\Controller\\', '../mini/Controller/*');

$definition = new Definition('Mini\Database\MySqlManager', [
	[
		'host'    => getenv('DB_HOST'),
		'port'    => getenv('DB_PORT'),
		'user'    => getenv('DB_USER'),
		'pass'    => getenv('DB_PASS'),
		'schema'  => getenv('DB_SCHEMA'),
		'charset' => getenv('DB_CHARSET'),
		'sockets' => [
			'rw' => null,
			'ro' => null
		]
	]
]);
$definition->setPublic(true);
$container->setDefinition('Mini\Database\Database', $definition);

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\Exception\\', '../mini/Exception/*');

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\File\\', '../mini/File/*');

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\Http\\', '../mini/Http/*', '../mini/Http/{CsvResponse.php,Exception/*}');

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\Log\\', '../mini/Log/*');

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\Model\\', '../mini/Model/*');

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\Util\\', '../mini/Util/*');

$definition = (new Definition)->setAutowired(true)->setPublic(true);
$loader->registerClasses($definition, 'Mini\\View\\', '../mini/View/*');

// Twig/view setup
$container->register(Mini\View\TwigEnvironmentFactory::class);
$container->register(Twig\Environment::class, Twig\Environment::class)
	->setFactory(new Reference(Mini\View\TwigEnvironmentFactory::class));
$container->register('Mini\View\Renderer', Mini\View\TwigRenderer::class)->setAutowired(true)->setPublic(true);
