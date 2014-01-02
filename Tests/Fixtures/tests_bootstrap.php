<?php

//copied from https://raw.github.com/nelmio/NelmioApiDocBundle/master/Tests/bootstrap.php

function includeIfExists($file)
{
    if (file_exists($file)) {
        return include $file;
    }
}

if ((!$loader = includeIfExists(__DIR__.'/../../vendor/autoload.php')) && (!$loader = includeIfExists(__DIR__.'/../../../../../../autoload.php'))) {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

if (class_exists('Doctrine\Common\Annotations\AnnotationRegistry')) {
    \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
}

//hack to clear cache directories before running test scripts
/*
$tmpDir = sys_get_temp_dir().'/ACWebServicesBundleTests';
if (file_exists($tmpDir)) {
    foreach (new DirectoryIterator($tmpDir) as $fileInfo) {
        if (!$fileInfo->isDot()) {
            unlink($fileInfo->getPathname());
        }
    }
}
*/
