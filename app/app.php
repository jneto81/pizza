<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Igorw\Silex\ConfigServiceProvider;
use Pizza\Controller\OrderController;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ValidatorServiceProvider;

// load composer autoload
if (!$loader = @include dirname(__DIR__) . '/vendor/autoload.php')
{
    die("curl -s http://getcomposer.org/installer | php; php composer.phar install");
}

// annotation registry
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

// intl
if (!function_exists('intl_get_error_code')) {
    require_once $strRootDir . '/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php';
    $loader->add('', $strRootDir . '/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs');
}

// new application
$app = new Application();
$app['root_dir'] = dirname(__DIR__);
$app['app_dir'] = $app['root_dir'] . '/app';
$app['cache_dir'] = $app['app_dir'] . '/cache';
$app['src_dir'] = $app['root_dir'] . '/src';

// load config
$app->register(new ConfigServiceProvider($app['app_dir'] . '/config.yml'));

// set error reporting
error_reporting(!$app['debug'] ? E_ALL ^ E_NOTICE : E_ALL);

// register doctrine dbal
$app->register(new DoctrineServiceProvider, array(
    "db.options" => $app['doctrine']['dbal']
));

// register doctrine orm
$app->register(new DoctrineOrmServiceProvider, array(
    "orm.proxies_dir" => $app['cache_dir'] . '/doctrine/proxies',
    "orm.em.options" => array(
        "mappings" => array(
            array(
                "type" => "annotation",
                "namespace" => "Pizza\\Model",
                "path" => $app['src_dir']."/Pizza/Model",
                "use_simple_annotation_reader" => false,
            ),
        ),
    ),
));

// register form factory
$app->register(new FormServiceProvider(), array(
    'form.secret' => $app['secret']
));

// register validator
$app->register(new ValidatorServiceProvider());

// register url generator
$app->register(new UrlGeneratorServiceProvider());

// register translation
$app->register(new TranslationServiceProvider());

// register twig
$app->register(new TwigServiceProvider(), array(
    'twig.path' => $app['src_dir'] . '/Pizza/View',
    'twig.options' => array('cache' => $app['cache_dir'] . '/twig')
));

// add routes
$app->mount('/', new OrderController());

// return the app
return $app;