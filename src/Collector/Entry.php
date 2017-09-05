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

use Opis\Colibri\CollectingContainer;
use Opis\Routing\Context;

class Entry extends Context
{
    /** @var CollectingContainer */
    protected $collector;

    public function __construct(string $name, CollectingContainer $collector)
    {
        $this->collector = $collector;
        parent::__construct(strtolower($name));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->path;
    }

    /**
     * @return  CollectingContainer
     */
    public function getCollector(): CollectingContainer
    {
        return $this->collector;
    }
}
