<?php

namespace Byscripts\Bundle\I18nRoutingBundle;

use Byscripts\Bundle\I18nRoutingBundle\DependencyInjection\Compiler\ByscriptsI18nCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ByscriptsI18nRoutingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ByscriptsI18nCompilerPass());
    }
}
