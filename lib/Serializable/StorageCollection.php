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

namespace Opis\Colibri\Serializable;

use Closure;
use Opis\Closure\SerializableClosure;
use Opis\Colibri\Application;
use RuntimeException;
use Serializable;

class StorageCollection implements Serializable
{
    protected $storages = array();
    protected $instances = array();
    protected $builder;
    protected $defaultStorage;

    public function __construct(Closure $builder)
    {
        $this->builder = $builder;
    }

    public function add($storage, Closure $constructor, $default = false)
    {
        if ($this->defaultStorage === null) {
            $default = true;
        }

        if ($default) {
            $this->defaultStorage = $storage;
        }

        $this->storages[$storage] = $constructor;
        unset($this->instances[$storage]);

        return $this;
    }

    public function remove($storage)
    {
        unset($this->storages[$storage]);
        unset($this->instances[$storage]);

        if ($this->defaultStorage === $storage) {
            $this->defaultStorage = null;
            if (!empty($this->storages)) {
                $this->defaultStorage = array_shift(array_keys($this->storages));
            }
        }

        return $this;
    }

    public function has($storage)
    {
        return isset($this->storages[$storage]);
    }

    public function setDefault($storage)
    {
        if ($this->has($storage)) {
            $this->defaultStorage = $storage;
        }

        return $this;
    }

    public function get(Application $app, $storage = null)
    {
        if ($storage === null) {
            $storage = $this->defaultStorage;
        }

        if (!$this->has($storage)) {
            throw new RuntimeException('Unknown storage ' . $storage);
        }

        if (!isset($this->instances[$storage])) {
            $constructor = $this->storages[$storage];
            $builder = $this->builder;
            $this->instances[$storage] = $builder($storage, $constructor, $app);
        }

        return $this->instances[$storage];
    }

    public function serialize()
    {
        SerializableClosure::enterContext();
        $object = serialize(array(
            'builder' => SerializableClosure::from($this->builder),
            'defaultStorage' => $this->defaultStorage,
            'storages' => array_map(function ($value) {
                return SerializableClosure::from($value);
            }, $this->storages),
        ));
        SerializableClosure::exitContext();
        return $object;
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $object = SerializableClosure::unserializeData($data);
        /** @var SerializableClosure builder */
        $this->builder = $object['builder']->getClosure();
        $this->defaultStorage = $object['defaultStorage'];
        $this->storages = array_map(function ($value) {
            return $value->getClosure();
        }, $object['storages']);
    }
}
