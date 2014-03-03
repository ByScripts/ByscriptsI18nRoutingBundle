<?php

namespace Byscripts\Bundle\I18nRoutingBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ByscriptsI18nCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if(!$container->hasDefinition('byscripts.i18n_routing.router')) {
            return;
        }

        // Save the original router
        $container->setDefinition(
            'byscripts.i18n_routing.router.original',
            $container->findDefinition('router')->setPublic(false)
        );

        // Save the original routing.loader
        $container->setDefinition(
            'byscripts.i18n_routing.routing.loader.original',
            $container->findDefinition('routing.loader')->setPublic(false)
        );

        // Redefine router and routing.loader
        $container->addAliases(
            array(
                'router'         => 'byscripts.i18n_routing.router',
                'routing.loader' => 'byscripts.i18n_routing.routing.loader'
            )
        );
    }
}