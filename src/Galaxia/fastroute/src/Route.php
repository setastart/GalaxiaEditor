<?php
declare(strict_types=1);

namespace Galaxia\FastRoute;

use function preg_match;

readonly class Route {

    public function __construct(
        public string $httpMethod,
        public mixed  $handler,
        public string $regex,
        public array  $variables
    ) {
    }

    /**
     * Tests whether this route matches the given string.
     */
    public function matches(string $str): bool {
        $regex = '~^' . $this->regex . '$~';

        return (bool)preg_match($regex, $str);
    }

}
