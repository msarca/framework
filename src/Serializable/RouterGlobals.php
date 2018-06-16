<?php
/* ===========================================================================
 * Copyright 2014-2018 The Opis Project
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

namespace Opis\Colibri\Serializable;

use Opis\Routing\ClosureWrapperTrait;
use Serializable;

class RouterGlobals implements Serializable
{
    use ClosureWrapperTrait;

    private $globals = [
        'mixin' => [],
        'implicit' => [],
        'bind' => [],
        'callback' => [],
        'placeholder' => []
    ];

    /**
     * @param string $name
     * @param callable $callback
     */
    public function mixin(string $name, callable $callback)
    {
        $this->globals[__FUNCTION__][$name] = $callback;
    }

    /**
     * @param string $name
     * @param callable $callback
     */
    public function bind(string $name, callable $callback)
    {
        $this->globals[__FUNCTION__][$name] = $callback;
    }

    /**
     * @param string $name
     * @param callable $callback
     */
    public function callback(string $name, callable $callback)
    {
        $this->globals[__FUNCTION__][$name] = $callback;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function implicit(string $name, $value)
    {
        $this->globals[__FUNCTION__][$name] = $value;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function placeholder(string $name, string $value)
    {
        $this->globals[__FUNCTION__][$name] = $value;
    }

    /**
     * @return array
     */
    public function getGlobals(): array
    {
        return $this->globals;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        $map = [static::class, 'wrapClosures'];

        $data = array_map($map, $this->globals);

        return serialize($data);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $map = [static::class, 'unwrapClosures'];
        $data = unserialize($serialized);
        $this->globals = array_map($map, $data);
    }
}