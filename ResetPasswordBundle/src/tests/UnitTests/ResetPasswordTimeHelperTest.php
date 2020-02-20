<?php

namespace SymfonyCasts\Bundle\ResetPassword\tests\UnitTests;

use SymfonyCasts\Bundle\ResetPassword\ResetPasswordTimeHelper;
use PHPUnit\Framework\TestCase;

/** @TODO WIP */
class ResetPasswordTimeHelperTest extends TestCase
{
    public function secondsDataProvider(): \Generator
    {
        yield [3600, '1 hour'];
        yield [7200, '2 hours'];
        yield [900, '15 minutes'];
        yield [4500, '1 hour 15 minutes'];
        yield [8100, '2 hours 15 minutes'];
    }

    /**
     * @dataProvider secondsDataProvider
     */
    public function testReturnsWhatIWant(int $seconds, string $expected): void
    {
        self::assertSame($expected, ResetPasswordTimeHelper::getFormattedSeconds($seconds));
    }
}
