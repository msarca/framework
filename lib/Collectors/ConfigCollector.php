<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Collector;
use Opis\Colibri\Serializable\StorageCollection;
use Opis\Config\StorageInterface;

/**
 * Class ConfigCollector
 *
 * @package Opis\Colibri\Collectors
 *
 * @method StorageCollection    data()
 */
class ConfigCollector extends Collector
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new StorageCollection(self::class . '::factory'));
    }

    /**
     * @param string $storage
     * @param callable $constructor
     * @return ConfigCollector
     */
    public function register(string $storage, callable $constructor): self
    {
        $this->dataObject->add($storage, $constructor);
        return $this;
    }

    /**
     * @param string $storage
     * @param callable $factory
     * @return StorageInterface
     */
    public static function factory(string $storage, callable $factory): StorageInterface
    {
        return $factory($storage);
    }
}
