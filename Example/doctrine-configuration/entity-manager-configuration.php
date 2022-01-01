<?php

/**
 * This entity manager configuration works with doctrine 2.1.x and 2.2.x
 * versions. Regarding AnnotationDriver setup it most probably will be changed into
 * xml. Because annotation driver fails to read other classes in same namespace
 */
// connection args, modify at will
$dbConfig = [
    'host' => '',
    'port' => 3306,
    'user' => '',
    'password' => '',
    'dbname' => '',
    'driver' => '',
];

$dirConf = [
    'doctrine_proxy_dir' => '',
    'vendor_dir' => '',
    'doctrine_cache_dir' => '',
];

$entityDirs = [
    'full filesystem path of entities 1',
    'full filesystem path of entities 2',
];




$proxyDir = $dirConf['doctrine_proxy_dir'];
if (!file_exists($dirConf['vendor_dir'].'/autoload.php')) {
    die('cannot find vendors, read README.md how to use composer');
}
// First of all autoloading of vendors
$loader = require $dirConf["vendor_dir"].'/autoload.php';

// gedmo extensions
$loader->add('Gedmo', $dirConf["vendor_dir"].'/gedmo/doctrine-extensions/lib');

// autoloader for Entity namespace
foreach ($entityDirs as $namespace => $dir){
    $loader->add($namespace, $dir);
}

// ensure standard doctrine annotations are registered
Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    $dirConf["vendor_dir"].'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);

// Second configure ORM
// globally used cache driver, in production use APC or memcached
//$cache = new Doctrine\Common\Cache\ArrayCache();
$cache = new Doctrine\Common\Cache\PhpFileCache($dirConf['doctrine_cache_dir']);

// standard annotation reader
$annotationReader = new Doctrine\Common\Annotations\AnnotationReader();
$cachedAnnotationReader = new Doctrine\Common\Annotations\CachedReader(
    $annotationReader, // use reader
    $cache // and a cache driver
);
// create a driver chain for metadata reading
$driverChain = new Doctrine\Persistence\Mapping\Driver\MappingDriverChain();
// load superclass metadata mapping only, into driver chain
// also registers Gedmo annotations.NOTE: you can personalize it
Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
    $driverChain, // our metadata driver chain, to hook into
    $cachedAnnotationReader // our cached annotation reader
);

// now we want to register our application entities,
// for that we need another metadata driver used for Entity namespace
$annotationDriver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
    $cachedAnnotationReader, // our cached annotation reader
    $entityDirs // paths to look in
);
// NOTE: driver for application Entity can be different, Yaml, Xml or whatever
// register annotation driver for our application Entity fully qualified namespace
foreach ($entityDirs as $namespace => $dir){
    $driverChain->addDriver($annotationDriver, $namespace);
}

// general ORM configuration
$paths = $entityDirs;
$isDevMode = true;
$config = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, null, null, false);
$config->setProxyDir($proxyDir);
$config->setProxyNamespace('Proxy');
$config->setAutoGenerateProxyClasses(false); // this can be based on production config.
// register metadata driver
$config->setMetadataDriverImpl($driverChain);
// use our allready initialized cache driver
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);
$config->setProxyDir($proxyDir);

// Third, create event manager and hook prefered extension listeners
$evm = new Doctrine\Common\EventManager();
// gedmo extension listeners


//Gedmo addons START
//
// timestampable
//$timestampableListener = new Gedmo\Timestampable\TimestampableListener();
//$timestampableListener->setAnnotationReader($cachedAnnotationReader);
//$evm->addEventSubscriber($timestampableListener);

//uploadable
//$uploadableListener = new Gedmo\Uploadable\UploadableListener();
//$uploadableListener->setAnnotationReader($cachedAnnotationReader);
//$uploadableListener->setDefaultPath($dirConf['attachment_location']);
//$evm->addEventSubscriber($uploadableListener);
//
//Gedmo addons END

// mysql set names UTF-8 if required
$evm->addEventSubscriber(new Doctrine\DBAL\Event\Listeners\MysqlSessionInit());

//add UUID generator
//\Doctrine\DBAL\Types\Type::addType('uuid', Ramsey\Uuid\Doctrine\UuidType::class);

// Finally, create entity manager
return \Doctrine\ORM\EntityManager::create($dbConfig, $config, $evm);
