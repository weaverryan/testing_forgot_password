<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\BundleUnitTests\Model;

use App\Tests\Fixtures\PasswordResetRequestTraitFixture;
use PHPUnit\Framework\TestCase;

class PasswordResetRequestTraitFixtureTest extends TestCase
{
    public function propertyDataProvider(): \Generator
    {
        yield ['selector'];
        yield ['hashedToken'];
        yield ['requestedAt'];
        yield ['expiresAt'];
    }

    /**
     * @test
     * @dataProvider propertyDataProvider
     */
    public function hasProperty(string $propertyName): void
    {
        self::assertClassHasAttribute(
            $propertyName,
            PasswordResetRequestTraitFixture::class,
            sprintf('PasswordResetRequestTrait::class does not have %s property defined.', $propertyName)
        );
    }

    public function methodDataProvider(): \Generator
    {
        yield ['getRequestedAt'];
        yield ['isExpired'];
        yield ['getExpiresAt'];
        yield ['getHashedToken'];
    }

    /**
     * @test
     * @dataProvider methodDataProvider
     */
    public function hasMethod(string $methodName): void
    {
        self::assertTrue(
            method_exists(PasswordResetRequestTraitFixture::class, $methodName),
            sprintf('PasswordResetRequestTrait::class does not have %s method defined.', $methodName)
        );
    }
}
