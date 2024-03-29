<?php

namespace Byscripts\Bundle\I18nRoutingBundle\Routing;


use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class Router implements RouterInterface
{
    /**
     * @var RouterInterface
     */
    private $originalRouter;

    /**
     * @var RequestStack
     */
    private $requestStack;

    function __construct(RouterInterface $originalRouter, RequestStack $requestStack)
    {
        $this->originalRouter = $originalRouter;
        $this->requestStack   = $requestStack;
    }

    public function generateSwitch($locale, $referenceType = self::ABSOLUTE_PATH)
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->generate(
            $request->attributes->get('_route') . '#i18n#' . $locale,
            $request->attributes->get('_route_params'),
            $referenceType
        );
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * Parameters that reference placeholders in the route pattern will substitute them in the
     * path or host. Extra params are added as query string to the URL.
     *
     * When the passed reference type cannot be generated for the route because it requires a different
     * host or scheme than the current one, the method will return a more comprehensive reference
     * that includes the required params. For example, when you call this method with $referenceType = ABSOLUTE_PATH
     * but the route requires the https scheme whereas the current scheme is http, it will instead return an
     * ABSOLUTE_URL with the https scheme and the current host. This makes sure the generated URL matches
     * the route in any case.
     *
     * If there is no route with the given name, the generator must throw the RouteNotFoundException.
     *
     * @param string         $name          The name of the route
     * @param mixed          $parameters    An array of parameters
     * @param Boolean|string $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     *
     * @throws RouteNotFoundException              If the named route doesn't exist
     * @throws MissingMandatoryParametersException When some parameters are missing that are mandatory for the route
     * @throws InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     *
     * @api
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $i18nTryName = $name;

        // If route name is not already localized, then append the current locale
        if (!strpos($name, '#i18n#')) {
            $i18nTryName = $name . '#i18n#' . $this->getContext()->getParameter('_locale');
        }

        try {

            // First, try the localized route

            return $this->originalRouter->generate($i18nTryName, $parameters, $referenceType);

        } catch (RouteNotFoundException $e) {

            // If $name is already localized... game over.

            if($i18nTryName === $name) {
                throw $e;
            }

            // Then try the standard one

            return $this->originalRouter->generate($name, $parameters, $referenceType);
        }
    }

    /**
     * Gets the request context.
     *
     * @return RequestContext The context
     *
     * @api
     */
    public function getContext()
    {
        return $this->originalRouter->getContext();
    }

    /**
     * Sets the request context.
     *
     * @param RequestContext $context The context
     *
     * @api
     */
    public function setContext(RequestContext $context)
    {
        return $this->originalRouter->setContext($context);
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function getRouteCollection()
    {
        return $this->originalRouter->getRouteCollection();
    }

    /**
     * Tries to match a URL path with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param string $pathInfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     *
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     *
     * @api
     */
    public function match($pathInfo)
    {
        $routeParams = $this->originalRouter->match($pathInfo);

        // Remove the locale part if any
        $routeParams['_route'] = strstr($routeParams['_route'], '#i18n#', true);

        return $routeParams;
    }
}