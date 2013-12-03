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
        $loader->load('config.yml');

        //process config from app
        $config = $this->processConfiguration(new Configuration(), $configs);

        //merge default format headers w/ user defined ones
        $formatHeaders = array_merge($container->getParameter('ac_web_services.default_format_headers'), $config['response_format_headers']);

        //set config values in the container based on processed values
        $container->setParameter('ac_web_services.paths', $config['paths']);
        $container->setParameter('ac_web_services.response_format_headers', $formatHeaders);
    }
}
