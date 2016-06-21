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

namespace Opis\Colibri;

use Opis\Colibri\Components\ApplicationTrait;
use Opis\Colibri\Validators\ValidatorCollection;
use Opis\Validation\Validator as BaseValidator;
use Opis\Validation\DefaultValidatorTrait;

class Validator extends BaseValidator
{
    use ApplicationTrait;
    use DefaultValidatorTrait;

    /** @var    Application */
    protected $app;

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct(new ValidatorCollection($app), $app->getPlaceholder());
    }

    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        $errors = array();

        foreach (parent::getErrors() as $key => $value) {
            $errors[$key] = $this->app->t($value);
        }

        return $errors;
    }

    /**
     * @return Validator|static
     */
    public function csrf(): self
    {
        $this->stack[] = array(
            'name' => __FUNCTION__,
            'arguments' => array(),
        );
        return $this;
    }

}
