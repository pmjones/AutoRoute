<?php
declare(strict_types=1);

namespace AutoRoute;

class LoggerTest extends \PHPUnit\Framework\TestCase
{
    public function test()
    {
        $logger = new Logger();
        $logger->debug('foo');
        $expect = ['(debug) foo'];
        $actual = $logger->getMessages();
        $this->assertSame($expect, $actual);

        $logger->reset();
        $expect = [];
        $actual = $logger->getMessages();
        $this->assertSame($expect, $actual);

        $logger->debug('bar');
        $expect = ['(debug) bar'];
        $actual = $logger->getMessages();
        $this->assertSame($expect, $actual);
    }
}
