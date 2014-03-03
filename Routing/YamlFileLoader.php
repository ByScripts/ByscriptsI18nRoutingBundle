<?php

namespace Byscripts\Bundle\I18nRoutingBundle\Routing;


use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader as SymfonyYamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class YamlFileLoader extends SymfonyYamlFileLoader
{
    /**
     * @var array Locales configuration
     */
    private $localesConfiguration = array();

    /**
     * @var string The application locale
     */
    private $appLocale;

    /**
     * @param FileLocatorInterface $locator              FileLocator service
     * @param array                $localesConfiguration Locales configuration
     * @param string               $appLocale            Application locale
     */
    public function __construct(FileLocatorInterface $locator, $localesConfiguration, $appLocale)
    {
        parent::__construct($locator);

        // Be assured that the locales without host are defined at last
        // to avoid any matching conflict
        uasort(
            $localesConfiguration,
            function ($locale) {
                return empty($locale['host']) ? 1 : 0;
            }
        );

        $this->localesConfiguration = $localesConfiguration;
        $this->appLocale            = $appLocale;
    }

    // Check if the resource is supported
    public function supports($resource, $type = null)
    {
        return
            'byscripts_i18n_routing' === $type
            && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Parses an import and adds the routes in the resource to the RouteCollection.
     *
     * @param RouteCollection $collection
     * @param array           $config
     * @param string          $path
     * @param string          $file
     */
    protected function parseImport(RouteCollection $collection, array $config, $path, $file)
    {
        // Propagate the type, if not set, to avoid retyping it each time
        if (!array_key_exists('type', $config)) {
            $config['type'] = 'byscripts_i18n_routing';
        }

        // Parse the import
        parent::parseImport($collection, $config, $path, $file);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param RouteCollection $collection
     * @param string          $name
     * @param array           $config
     * @param string          $path
     */
    protected function parseRoute(RouteCollection $collection, $name, array $config, $path)
    {
        // Normalize the configuration
        $config = $this->normalizeConfig($config);

        // Create a route with the original name and set host to "_" be sure it'll never match
        // This route is added mainly for IDEs which propose auto-completion on route names
        $collection->add($name, new Route($config['path'][$this->appLocale], array(), array(), array(), '_'));

        foreach ($this->localesConfiguration as $locale => $localeConfiguration) {

            // Compose a route name based on locale
            $i18nRouteName = $name . '#i18n#' . $locale;

            // Get the configured path
            $i18nRoutePath = $config['path'][$locale];

            // Get the "defaults" array and add it the locale
            $i18nRouteDefaults            = $this->getIfExists('defaults', $config, array());
            $i18nRouteDefaults['_locale'] = $locale;

            // Get the "requirements" array
            $i18nRouteRequirements = $this->getIfExists('requirements', $config, array());

            // Get the "options" array
            $i18nRouteOptions = $this->getIfExists('options', $config, array());

            // Add the prefix to options
            $i18nRouteOptions['byscripts.i18n_routing.prefix'] = $localeConfiguration['prefix'];

            // Get the user configured host for this locale
            $i18nRouteHost = $this->getIfExists('host', $config, $localeConfiguration['host']);

            // Get the "schemes" array
            $i18nRouteSchemes = $this->getIfExists('schemes', $config, array());

            // Get the "methods" array
            $i18nRouteMethods = $this->getIfExists('methods', $config, array());

            // Get the route "condition"
            $i18nRouteCondition = $this->getIfExists('condition', $config);

            // Build the route
            $i18nRoute = new Route(
                $i18nRoutePath,
                $i18nRouteDefaults,
                $i18nRouteRequirements,
                $i18nRouteOptions,
                $i18nRouteHost,
                $i18nRouteSchemes,
                $i18nRouteMethods,
                $i18nRouteCondition
            );

            // Add the localized route to the collection
            $collection->add($i18nRouteName, $i18nRoute);
        }
    }

    /**
     * Normalize the route configuration
     * Assure that $config['path'] is an array of locale configurations
     *
     * @param array $config
     *
     * @return array
     */
    private function normalizeConfig($config)
    {
        $availableLocales = array_keys($this->localesConfiguration);
        $defaultPath      = null;

        if (!is_array($config['path'])) {

            // If path is a string, then use it as default for all locale

            $defaultPath    = $config['path'];
            $config['path'] = array();

        } elseif (array_key_exists('*', $config['path'])) {

            // Else if path is an array and has a "*" key, use it as default
            // for all non defined locales

            $defaultPath = $config['path']['*'];
            unset($config['path']['*']);
        }

        // Generate an array with locales as keys and default path as value (array_fill_keys)
        // then merge it with paths configured by user (array_merge)
        // and finally remove empty paths (array_filter)
        $config['path'] = array_filter(
            array_merge(
                array_fill_keys($availableLocales, $defaultPath),
                $config['path']
            )
        );

        return $config;
    }

    /**
     * Returns array value if key exists, else returns default value
     *
     * @param string $key     The array key to retrieve
     * @param array  $array   The array to search in
     * @param mixed  $default The default value to return if key doesn't exist
     *
     * @return mixed
     */
    private function getIfExists($key, $array, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    /**
     * Validates the route configuration
     *
     * @param array  $config
     * @param string $name
     * @param string $path
     *
     * @throws \InvalidArgumentException
     */
    protected function validate($config, $name, $path)
    {
        parent::validate($config, $name, $path);

        // Parent validation is sufficient for resources
        if (array_key_exists('resource', $config)) {
            return;
        }

        // Normalize the configuration
        $config = $this->normalizeConfig($config);

        // Check if there is missing locales in the path definition
        $diff = array_diff_key($this->localesConfiguration, $config['path']);

        if (!empty($diff)) {
            throw new \InvalidArgumentException(sprintf(
                'Path configuration is incorrect for route "%s". Missing locale(s): "%s".',
                $name,
                implode(', ', array_keys($diff))
            ));
        }

        // Check if a path is defined for a non-existent locale
        $diff = array_diff_key($config['path'], $this->localesConfiguration);

        if (!empty($diff)) {
            throw new \InvalidArgumentException(sprintf(
                'Path configuration is incorrect for route "%s". Non-existent locale(s) in configuration: "%s".',
                $name,
                implode(', ', array_keys($diff))
            ));
        }

        // Check for duplicate paths with the same host and prefix
        $duplicates = array();

        foreach ($config['path'] as $locale => $path) {
            $key = sprintf(
                'Path: %s, Host: %s, Prefix: %s',
                $path,
                $this->localesConfiguration[$locale]['host'] ? : 'Any',
                $this->localesConfiguration[$locale]['prefix'] ? : 'None'
            );

            $duplicates[$key][] = $locale;
        }

        $errors = array();

        foreach ($duplicates as $configuration => $locales) {

            // If there is only one locale for this configuration, it's ok.
            if (1 === count($locales)) {
                continue;
            }

            // Else, more than one locale as the same configuration
            $errors[] = sprintf(
                'The route "%s" has the same configuration (%s) for multiple locales (%s).',
                $name,
                $configuration,
                implode(', ', $locales)
            );
        }

        // If there is errors
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode("\n\n", $errors));
        }
    }
}