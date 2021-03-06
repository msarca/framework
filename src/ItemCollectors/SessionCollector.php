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
use Opis\Colibri\Serializable\SessionList;

/**
 * @property SessionList $data
 */
class SessionCollector extends ItemCollector
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new SessionList());
    }

    /**
     * @param string $name
     * @param callable $constructor
     * @param array $config
     * @return SessionCollector
     */
    public function register(string $name, callable $constructor, array $config = []): self
    {
        $config['cookie_name'] = $name;
        $this->data->register($name, $constructor, $config);
        return $this;
    }
}
