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

namespace Opis\Colibri\Validation\Rules;

use Opis\Validation\IValidationRule;
use function Opis\Colibri\Functions\{
    validateCSRFToken
};

class Csrf implements IValidationRule
{
    /**
     * Validator's name
     *
     * @return string
     */
    public function name(): string
    {
        return 'field:csrf';
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return 'Invalid CSRF token';
    }

    /**
     * @param array $arguments
     * @return array
     */
    public function getFormattedArgs(array $arguments): array
    {
        return [
            'remove' => $arguments[0],
        ];
    }

    /**
     * @inheritDoc
     */
    public function prepareValue($value, array $arguments)
    {
        return $value;
    }

    /**
     * Validate
     *
     * @param mixed $value
     * @param array $arguments
     * @return bool
     */
    public function validate($value, array $arguments): bool
    {
        return validateCSRFToken($value, $arguments['remove']);
    }
}