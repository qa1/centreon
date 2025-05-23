<?php

/*
 * Copyright 2005 - 2020 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */
declare(strict_types=1);

namespace Centreon\Domain\Common\Assertion;

/**
 * This class is designed to contain all assertion exceptions
 *
 * @package Centreon\Domain\Common\Assertion
 */
class AssertionException extends \InvalidArgumentException
{
    /**
     * Exception when the value of the string is longer than the expected number of characters.
     *
     * @param string $value Tested value
     * @param int $valueLength Length of the tested value
     * @param int $maxLength Maximum length of the expected value in characters
     * @param string|null $propertyPath Property's path (ex: Host::name)
     * @return self
     */
    public static function maxLength(string $value, int $valueLength, int $maxLength, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _(
                    '[%s] The value "%s" is too long, it should have no more than %d characters,'
                    . ' but has %d characters'
                ),
                $propertyPath,
                $value,
                $maxLength,
                $valueLength
            )
        );
    }

    /**
     * Exception when the value of the string is smaller than the expected number of characters.
     *
     * @param string $value Tested value
     * @param int $valueLength Length of the tested value
     * @param int $minLength Minimum length of the expected value in characters
     * @param string|null $propertyPath Property's path (ex: Host::name)
     * @return self
     */
    public static function minLength(string $value, int $valueLength, int $minLength, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _(
                    '[%s] The value "%s" is too short, it should have at least %d characters,'
                    . ' but only has %d characters'
                ),
                $propertyPath,
                $value,
                $minLength,
                $valueLength
            )
        );
    }

    /**
     * Exception when the value of the integer is less than the expected value.
     *
     * @param int $value Tested value
     * @param int $minValue Minimum value
     * @param string|null $propertyPath Property's path (ex: Host::maxCheckAttempts)
     * @return self
     */
    public static function min(int $value, int $minValue, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value "%d" was expected to be at least %d'),
                $propertyPath,
                $value,
                $minValue
            )
        );
    }

    /**
     * Exception when the value of the integer is higher than the expected value.
     *
     * @param int $value Tested value
     * @param int $maxValue Maximum value
     * @param string|null $propertyPath Property's path (ex: Host::maxCheckAttempts)
     * @return self
     */
    public static function max(int $value, int $maxValue, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value "%d" was expected to be at most %d'),
                $propertyPath,
                $value,
                $maxValue
            )
        );
    }

    /**
     * Exception when the value does not respect email format.
     *
     * @param string $value Tested value
     * @param string|null $propertyPath Property's path (ex: Host::maxCheckAttempts)
     * @return self
     */
    public static function email(string $value, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value "%s" was expected to be a valid e-mail address'),
                $propertyPath,
                $value
            )
        );
    }

    /**
     * Exception when the value of the date is higher than the expected date.
     *
     * @param \DateTime $date Tested date
     * @param \DateTime $maxDate Maximum date
     * @param string|null $propertyPath Property's path (ex: Host::maxCheckAttempts)
     * @return self
     */
    public static function maxDate(\DateTime $date, \DateTime $maxDate, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The date "%s" was expected to be at most %s'),
                $propertyPath,
                $date->format('c'),
                $maxDate->format('c')
            )
        );
    }

    /**
     * Exception when the value of the integer is less than the expected value.
     *
     * @param int $value Tested value
     * @param int $limit Limit value
     * @param string|null $propertyPath Property's path (ex: Host::maxCheckAttempts)
     * @return self
     */
    public static function greaterOrEqualThan(int $value, int $limit, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value "%d" is not greater or equal than %d'),
                $propertyPath,
                $value,
                $limit
            )
        );
    }

    /**
     * Exception when the value is empty.
     *
     * @param string|null $propertyPath Property's path (ex: Host::name)
     * @return self
     */
    public static function notEmpty(string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value is empty, but non empty value was expected'),
                $propertyPath
            )
        );
    }

    /**
     * Exception when the value is null.
     *
     * @param string|null $propertyPath Property's path (ex: Host::name)
     * @return self
     */
    public static function notNull(string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value is null, but non null value was expected'),
                $propertyPath
            )
        );
    }

    /**
     * Exception when the value is not expected.
     *
     * @param string|null $propertyPath Property's path (ex: Host::name)
     * @param string $value
     * @param mixed[] $expectedValues
     * @return self
     */
    public static function inArray(string $value, array $expectedValues, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value provided (%s) was not expected. Possible values %s'),
                $propertyPath,
                $value,
                implode('|', $expectedValues)
            )
        );
    }

    /**
     * Exception when the value does not respect ip format.
     *
     * @param string $value Tested value
     * @param string|null $propertyPath Property's path (ex: Host::maxCheckAttempts)
     * @return self
     */
    public static function ipOrDomain(string $value, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value "%s" was expected to be a valid ip address or domain name'),
                $propertyPath,
                $value
            )
        );
    }

    /**
     * Exception when the value doesn't match a regex.
     *
     * @param string $value
     * @param string $pattern
     * @param string|null $propertyPath
     * @return self
     */
    public static function matchRegex(string $value, string $pattern, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value (%s) doesn\'t match the regex \'%s\''),
                $propertyPath,
                $value,
                $pattern
            )
        );
    }

    /**
     * Exception when the value does not respect ip format.
     *
     * @param string $value Tested value
     * @param string|null $propertyPath Property's path (ex: Host::maxCheckAttempts)
     * @return self
     */
    public static function ipAddressNotValid(string $value, string $propertyPath = null): self
    {
        return new self(
            sprintf(
                _('[%s] The value \'%s\' was expected to be a valid ip address'),
                $propertyPath,
                $value
            )
        );
    }
}
