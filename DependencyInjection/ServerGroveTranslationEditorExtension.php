<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Definition;


class ServerGroveTranslationEditorExtension extends \Symfony\Component\HttpKernel\DependencyInjection\Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');

        $configuration   = new Configuration();
        $config          = $this->processConfiguration($configuration, $configs);
        $parameterDomain = 'server_grove_translation_editor';

        $container->setParameter($parameterDomain . '.storage.type', $config['storage']['type']);
        $container->setParameter($parameterDomain . '.storage.manager', $config['storage']['manager']);
    }
}
