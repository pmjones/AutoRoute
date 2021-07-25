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

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    protected array $messages = [];

    public function log(
        $level,
        string|\Stringable $message,
        array $context = [],
    ) : void
    {
        $this->messages[] = "($level) $message";
    }

    public function getMessages() : array
    {
        return $this->messages;
    }

    public function reset() : void
    {
        $this->messages = [];
    }
}
