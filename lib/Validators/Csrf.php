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

namespace Opis\Colibri\Validators;

use Opis\Colibri\Application;
use Opis\Validation\ValidatorInterface;

class Csrf implements ValidatorInterface
{

    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Validator's name
     *
     * @return string
     */
    public function name()
    {
        return 'csrf';
    }

    /**
     * @return string
     */
    public function getError()
    {
        return 'Invalid CSRF token';
    }

    /**
     * @param array $arguments
     * @return array
     */
    public function getFormattedArgs(array $arguments)
    {
        return array();
    }

    /**
     * Validate
     *
     * @param mixed $value
     * @param array $arguments
     * @return bool
     */
    public function validate($value, array $arguments)
    {
        return $this->app->csrfValidate($value);
    }

}