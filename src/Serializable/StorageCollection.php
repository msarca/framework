<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

use Opis\Closure\SerializableClosure;
use RuntimeException;
use Serializable;

class StorageCollection implements Serializable
{
    protected $storage = [];
    protected $instances = [];
    protected $builder;

    public function __construct(callable $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param string $storage
     * @param callable $factory
     * @return StorageCollection
     */
    public function add(string $storage, callable $factory): self
    {
        $this->storage[$storage] = $factory;
        unset($this->instances[$storage]);

        return $this;
    }


    /**
     * @param string $storage
     * @return mixed
     */
    public function get(string $storage)
    {
        if (!isset($this->storage[$storage])) {
            throw new RuntimeException("Unknown storage '$storage'");
        }

        if (!isset($this->instances[$storage])) {
            $constructor = $this->storage[$storage];
            $builder = $this->builder;
            $this->instances[$storage] = $builder($storage, $constructor);
        }

        return $this->instances[$storage];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        SerializableClosure::enterContext();

        $map = function ($value){
            if($value instanceof \Closure){
                return SerializableClosure::from($value);
            }
            return $value;
        };

        $object = serialize([
            'builder' => $map($this->builder),
            'storage' => array_map($map, $this->storage),
        ]);

        SerializableClosure::exitContext();

        return $object;
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $object = unserialize($data);

        $map = function ($value){
            if($value instanceof SerializableClosure){
                return $value->getClosure();
            }
            return $value;
        };

        $this->builder = $map($object['builder']);
        $this->storage = array_map($map, $object['storage']);
    }
}
