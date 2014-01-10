<?php
namespace AC\WebServicesBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class ACWebServicesExtension extends Extension
{
    public function load(array $appConfig, ContainerBuilder $container)
    {
        //process config from app and bundle defaults
        $config = $this->processConfiguration(new Configuration(), $appConfig);
        $negotiation = $config['negotiation'];
        $serializer = $config['serializer'];
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        //convert the input format config into the actual structure used by the negotiator
        //... yeah, I'm not sure I should do this here
        $negotiationInputMap = array();
        foreach ($negotiation['input_format_types'] as $format => $types) {
            foreach ($types as $type) {
                $negotiationInputMap[$type] = $format;
            }
        }

        //load serializer overrides
        if ($serializer['enabled'] && $serializer['allow_deserialize_into_target']) {
            $loader->load('config.serializer.yml');
        }

        //set processed config values in the container based on processed values
        $container->setParameter('ac_web_services.path_config', $config['paths']);
        $container->setParameter('ac_web_services.response_format_headers', $config['response_format_headers']);
        $container->setParameter('ac_web_services.serializable_formats', $config['serializable_formats']);
        $container->setParameter('ac_web_services.negotiation.input_format_types', $negotiationInputMap);
        $container->setParameter('ac_web_services.negotiation.response_format_priorities', $negotiation['response_format_priorities']);
        $container->setParameter('ac_web_services.negotiation.response_language_priorities', $negotiation['response_language_priorities']);
        $container->setParameter('ac_web_services.negotiation.response_charset_priorities', $negotiation['response_charset_priorities']);
        $container->setParameter('ac_web_services.negotiation.response_encoding_priorities', $negotiation['response_encoding_priorities']);
        $container->setParameter('ac_web_services.negotiation.response_additional_negotiation_formats', $negotiation['response_additional_negotiation_formats']);

        //load services
        $loader->load('config.yml');
    }
}
