<?php

namespace Byscripts\Bundle\I18nRoutingBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader as SymfonyDelegatingLoader;

class DelegatingLoader
{
    /**
     * @var SymfonyDelegatingLoader
     */
    private $originalDelegatingLoader;

    function __construct(SymfonyDelegatingLoader $originalDelegatingLoader)
    {
        $this->originalDelegatingLoader = $originalDelegatingLoader;
    }

    public function load($resource, $type = null)
    {
        $collection = $this->originalDelegatingLoader->load($resource, $type);

        // Add the prefix (if any) to each route of the collection
        foreach ($collection->all() as $route) {
            $route->setPath($route->getOption('byscripts.i18n_routing.prefix') . $route->getPath());
        }

        return $collection;
    }
}