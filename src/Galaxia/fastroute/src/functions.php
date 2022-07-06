<?php
declare(strict_types=1);

namespace Galaxia\FastRoute;

use Galaxia\G;
use LogicException;
use RuntimeException;
use function assert;
use function file_exists;
use function file_put_contents;
use function function_exists;
use function is_array;
use function var_export;

if (! function_exists('Galaxia\FastRoute\simpleDispatcher')) {
    /**
     * @param array<string, string> $options
     */
    function simpleDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
    {
        $options += [
            'routeParser' => RouteParser\Std::class,
            'dataGenerator' => DataGenerator\GroupCountBased::class,
            'dispatcher' => Dispatcher\GroupCountBased::class,
            'routeCollector' => RouteCollector::class,
        ];

        $routeCollector = new $options['routeCollector'](
            new $options['routeParser'](),
            new $options['dataGenerator']()
        );
        assert($routeCollector instanceof RouteCollector);
        $routeDefinitionCallback($routeCollector);

        return new $options['dispatcher']($routeCollector->getData());
    }

    /**
     * @param array<string, string> $options
     */
    function cachedDispatcher(callable $routeDefinitionCallback, array $options = []): Dispatcher
    {
        $options += [
            'routeParser' => RouteParser\Std::class,
            'dataGenerator' => DataGenerator\GroupCountBased::class,
            'dispatcher' => Dispatcher\GroupCountBased::class,
            'routeCollector' => RouteCollector::class,
            'cacheDisabled' => false,
        ];

        if (! isset($options['cacheFile'])) {
            throw new LogicException('Must specify "cacheFile" option');
        }

        if (! $options['cacheDisabled'] && file_exists($options['cacheFile'])) {
            G::timerStart('FastRoute HIT');
            $dispatchData = require $options['cacheFile'];
            if (! is_array($dispatchData)) {
                throw new RuntimeException('Invalid cache file "' . $options['cacheFile'] . '"');
            }
            G::timerStop('FastRoute HIT');
            return new $options['dispatcher']($dispatchData);
        }

        $timerName = $options['cacheDisabled'] ? 'FastRoute BYPASS' : 'FastRoute MISS';
        G::timerStart($timerName);

        $routeCollector = new $options['routeCollector'](
            new $options['routeParser'](),
            new $options['dataGenerator']()
        );
        assert($routeCollector instanceof RouteCollector);
        $routeDefinitionCallback($routeCollector);

        $dispatchData = $routeCollector->getData();
        if (! $options['cacheDisabled']) {
            file_put_contents(
                $options['cacheFile'],
                '<?php return ' . var_export($dispatchData, true) . ';'
            );
        }

        G::timerStop($timerName);
        return new $options['dispatcher']($dispatchData);
    }
}
