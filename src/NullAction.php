<?php
/**
 *
 * This file is part of AutoRoute for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
declare(strict_types=1);

namespace AutoRoute;

class NullAction extends Action
{
    public function __construct()
    {
    }

    public function getClass() : string
    {
        return '';
    }

    public function getRequiredParameters(int $offset = 0) : array
    {
        return [];
    }

    public function getOptionalParameters() : array
    {
        return [];
    }
}
