<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Colibri\Collector;

use Opis\Colibri\ItemCollector;
use Opis\Events\RouteCollection;
use Opis\Routing\Context;
use Opis\Routing\Router as BaseRouter;

/**
 * @method ItemCollector route(Context $context)
 */
class Router extends BaseRouter
{
    public function __construct()
    {
        parent::__construct(new RouteCollection(), new Dispatcher());
    }

    /**
     * @param string $name
     * @param callable $callback
     * @param int $priority
     */
    public function handle(string $name, callable $callback, int $priority = 0)
    {
        $this->getRouteCollection()->createRoute(strtolower($name), $callback)->set('priority', $priority);
    }
}