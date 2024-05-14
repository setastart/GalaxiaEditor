<?php
declare(strict_types=1);

namespace Galaxia\FastRoute;

class RouteCollector {

    protected string $currentGroupPrefix = '';

    public function __construct(
        protected RouteParser   $routeParser,
        protected DataGenerator $dataGenerator
    ) {
    }

    /**
     * Adds a route to the collection.
     *
     * The syntax used in the $route string depends on the used route parser.
     */
    public function addRoute(array|string $httpMethod, string $route, mixed $handler): void {
        $route      = $this->currentGroupPrefix . $route;
        $routeDatas = $this->routeParser->parse($route);
        foreach ((array)$httpMethod as $method) {
            foreach ($routeDatas as $routeData) {
                $this->dataGenerator->addRoute($method, $routeData, $handler);
            }
        }
    }

    /**
     * Create a route group with a common prefix.
     *
     * All routes created in the passed callback will have the given group prefix prepended.
     */
    public function addGroup(string $prefix, callable $callback): void {
        $previousGroupPrefix      = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix . $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
    }

    /**
     * Adds a GET route to the collection
     *
     * This is simply an alias of $this->addRoute('GET', $route, $handler)
     */
    public function get(string $route, mixed $handler): void {
        $this->addRoute('GET', $route, $handler);
    }

    /**
     * Adds a POST route to the collection
     *
     * This is simply an alias of $this->addRoute('POST', $route, $handler)
     */
    public function post(string $route, mixed $handler): void {
        $this->addRoute('POST', $route, $handler);
    }

    /**
     * Adds a PUT route to the collection
     *
     * This is simply an alias of $this->addRoute('PUT', $route, $handler)
     */
    public function put(string $route, mixed $handler): void {
        $this->addRoute('PUT', $route, $handler);
    }

    /**
     * Adds a DELETE route to the collection
     *
     * This is simply an alias of $this->addRoute('DELETE', $route, $handler)
     */
    public function delete(string $route, mixed $handler): void {
        $this->addRoute('DELETE', $route, $handler);
    }

    /**
     * Adds a PATCH route to the collection
     *
     * This is simply an alias of $this->addRoute('PATCH', $route, $handler)
     */
    public function patch(string $route, mixed $handler): void {
        $this->addRoute('PATCH', $route, $handler);
    }

    /**
     * Adds a HEAD route to the collection
     *
     * This is simply an alias of $this->addRoute('HEAD', $route, $handler)
     */
    public function head(string $route, mixed $handler): void {
        $this->addRoute('HEAD', $route, $handler);
    }

    /**
     * Adds an OPTIONS route to the collection
     *
     * This is simply an alias of $this->addRoute('OPTIONS', $route, $handler)
     */
    public function options(string $route, mixed $handler): void {
        $this->addRoute('OPTIONS', $route, $handler);
    }

    /**
     * Returns the collected route data, as provided by the data generator.
     */
    public function getData(): array {
        return $this->dataGenerator->getData();
    }

}
