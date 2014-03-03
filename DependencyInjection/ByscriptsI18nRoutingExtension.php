<?php

namespace Byscripts\Bundle\I18nRoutingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ByscriptsI18nRoutingExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        // If autoPrefix is enabled...
        if($config['autoPrefix']) {
            foreach ($config['locales'] as $locale => $localeConfig) {
                if (null === $localeConfig['prefix']) {
                    // ...then set the locale as prefix if none is already set
                    $config['locales'][$locale]['prefix'] = '/' . $locale;
                }
            }
        }

        // Be sure that at least the app locale is configured
        $appLocale = $container->getParameter('locale');
        if(!array_key_exists($appLocale, $config['locales'])) {
            throw new \InvalidArgumentException(sprintf(
                '[ByscriptsI18nRouting] The app locale for this application is "%s" but it is missing in configured locale(s): "%s".',
                $appLocale,
                implode(', ', array_keys($config['locales']))
            ));
        }

        $container->setParameter('byscripts.i18n_routing.locales', $config['locales']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->addClassesToCompile(array(
                'Byscripts\\Bundle\\I18nRoutingBundle\\Routing\\DelegatingLoader',
                'Byscripts\\Bundle\\I18nRoutingBundle\\Routing\\Router',
                'Byscripts\\Bundle\\I18nRoutingBundle\\Routing\\YamlFileLoader',
            ));
    }
}
