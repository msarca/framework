<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

namespace Opis\Colibri\ItemCollectors;

use Opis\Colibri\ItemCollector;
use Opis\Events\RouteCollection;
use Opis\Routing\Route;

/**
 * @property RouteCollection $data
 */
class EventHandlerCollector extends ItemCollector
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new RouteCollection());
    }

    /**
     * Register a new event handler
     *
     * @param   string $event Event name
     * @param   callable $callback A callback that will be executed
     *
     * @return  Route
     */
    public function handle(string $event, callable $callback): Route
    {
        $route = $this->data
            ->createRoute($event, $callback)
            ->set('priority', $this->crtPriority);

        $this->data->sort();

        return $route;
    }
}
