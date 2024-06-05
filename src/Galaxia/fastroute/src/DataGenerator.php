<?php
declare(strict_types=1);

namespace Galaxia\FastRoute;

// Mark based DataGenerator
class DataGenerator {

    /** @var array<string, array<string, mixed>> */
    protected array $staticRoutes = [];

    /** @var array<string, array<string, Route>> */
    protected array $methodToRegexToRoutesMap = [];

    protected function getApproxChunkSize(): int {
        return 30;
    }

    /**
     * @param array<string, Route> $regexToRoutesMap
     */
    protected function processChunk(array $regexToRoutesMap): array {
        $routeMap = [];
        $regexes  = [];
        $markName = 'a';

        foreach ($regexToRoutesMap as $regex => $route) {
            $regexes[]           = $regex . '(*MARK:' . $markName . ')';
            $routeMap[$markName] = [$route->handler, $route->variables];

            ++$markName;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';

        return ['regex' => $regex, 'routeMap' => $routeMap];
    }

    /**
     * Adds a route to the data generator. The route data uses the
     * same format that is returned by RouterParser::parser().
     *
     * The handler doesn't necessarily need to be a callable, it
     * can be arbitrary data that will be returned when the route
     * matches.
     *
     * @param array<string|array{string, string}> $routeData
     */
    public function addRoute(string $httpMethod, array $routeData, mixed $handler): void {
        if ($this->isStaticRoute($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData, $handler);
        } else {
            $this->addVariableRoute($httpMethod, $routeData, $handler);
        }
    }

    /**
     * Returns dispatcher data in some unspecified format, which
     * depends on the used method of dispatch.
     */
    public function getData(): array {
        if ($this->methodToRegexToRoutesMap === []) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    private function generateVariableRouteData(): array {
        $data = [];
        foreach ($this->methodToRegexToRoutesMap as $method => $regexToRoutesMap) {
            $chunkSize     = $this->computeChunkSize(count($regexToRoutesMap));
            $chunks        = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] = array_map([$this, 'processChunk'], $chunks);
        }

        return $data;
    }

    private function computeChunkSize(int $count): int {
        $numParts = max(1, round($count / $this->getApproxChunkSize()));

        return (int)ceil($count / $numParts);
    }

    /**
     * @param array<int, mixed> $routeData
     */
    private function isStaticRoute(array $routeData): bool {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    /**
     * @param array<int, mixed> $routeData
     */
    private function addStaticRoute(string $httpMethod, array $routeData, mixed $handler): void {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$httpMethod][$routeStr])) {
            throw new BadRouteException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $routeStr,
                $httpMethod
            ));
        }

        if (isset($this->methodToRegexToRoutesMap[$httpMethod])) {
            foreach ($this->methodToRegexToRoutesMap[$httpMethod] as $route) {
                if ($route->matches($routeStr)) {
                    throw new BadRouteException(sprintf(
                        'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"',
                        $routeStr,
                        $route->regex,
                        $httpMethod
                    ));
                }
            }
        }

        $this->staticRoutes[$httpMethod][$routeStr] = $handler;
    }

    /**
     * @param array<int, mixed> $routeData
     */
    private function addVariableRoute(string $httpMethod, array $routeData, mixed $handler): void {
        [$regex, $variables] = $this->buildRegexForRoute($routeData);

        if (isset($this->methodToRegexToRoutesMap[$httpMethod][$regex])) {
            throw new BadRouteException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $regex,
                $httpMethod
            ));
        }

        $this->methodToRegexToRoutesMap[$httpMethod][$regex] = new Route(
            $httpMethod,
            $handler,
            $regex,
            $variables
        );
    }

    private function buildRegexForRoute(array $routeData): array {
        $regex     = '';
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            [$varName, $regexPart] = $part;

            if (isset($variables[$varName])) {
                throw new BadRouteException(sprintf(
                    'Cannot use the same placeholder "%s" twice',
                    $varName
                ));
            }

            if ($this->regexHasCapturingGroups($regexPart)) {
                throw new BadRouteException(sprintf(
                    'Regex "%s" for parameter "%s" contains a capturing group',
                    $regexPart,
                    $varName
                ));
            }

            $variables[$varName] = $varName;
            $regex               .= '(' . $regexPart . ')';
        }

        return [$regex, $variables];
    }

    /** @noinspection Annotator */
    private function regexHasCapturingGroups(string $regex): bool {
        if (!str_contains($regex, '(')) {
            // Needs to have at least a ( to contain a capturing group
            return false;
        }

        // Semi-accurate detection for capturing groups
        return (bool)preg_match(
            '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x',
            $regex
        );
    }

}
