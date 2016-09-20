<?php

namespace Alpixel\Bundle\LoggingBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Parser;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AlpixelLoggingExtension extends Extension
{
    private $showInDev;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('alpixel_logging.token', $config['slack']['token']);
        $container->setParameter('alpixel_logging.bot_name', $config['slack']['bot_name']);
        $container->setParameter('alpixel_logging.channel', $config['slack']['channel']);
        $this->showInDev = $config['slack']['debug'];


        //Defining monolog options
        $parser = new Parser();
        $config = $parser->parse(file_get_contents(__DIR__.'/../Resources/config/monolog.yml'));
        $monologConfig = ['handlers' => []];

        $debugEnv = ($container->getParameter('kernel.debug') === true);

        if (($debugEnv === false || ($debugEnv === true && $this->showInDev === true))) {
            $monologConfig['handlers'] = $config['common'];
        }

        if ($debugEnv === true) {
            $monologConfig['handlers'] = array_merge($monologConfig['handlers'], $config['debug']);
        } else {
            $monologConfig['handlers'] = array_merge($monologConfig['handlers'], $config['no_debug']);
        }

        $container->prependExtensionConfig('monolog', $monologConfig);

        //Loading services.yml
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }


}
