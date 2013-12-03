<?php
namespace AC\WebServicesBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class ACWebServicesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        //load services
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        //process config from app
        $config = $this->processConfiguration(new Configuration(), $configs);

        //set config values in the container based on processed values
        foreach ($config as $key => $val) {
            $container->setParameter('ac_web_services.'.$key, $val);
        }
    }
}
