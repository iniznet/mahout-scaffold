<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Exception\ClassMapMalformed;
use Iniznet\Howdah\Exception\InvalidContentDeclaration;
use Iniznet\Howdah\Exception\NotBooted;
use Iniznet\Howdah\Exception\PluginException;
use PHPUnit\Framework\TestCase;

/**
 * Every exception carries this host's marker and names its condition, not its throw
 * site. A named constructor is the only way in.
 */
final class ExceptionsTest extends TestCase
{
    public function testEveryExceptionCarriesThePackageMarker(): void
    {
        foreach ([ClassMapMalformed::class, InvalidContentDeclaration::class, NotBooted::class] as $exception) {
            self::assertTrue(is_subclass_of($exception, PluginException::class), $exception.' must implement the package marker');
        }
    }

    public function testTheClassmapExceptionNamesTheShape(): void
    {
        self::assertStringContainsString('malformed', ClassMapMalformed::because('the map is not an object')->getMessage());
        self::assertStringContainsString('not parseable', ClassMapMalformed::becauseJson(new \JsonException('Syntax error'))->getMessage());
    }

    public function testTheContentExceptionNamesTheDeclaration(): void
    {
        self::assertStringContainsString('PostType', InvalidContentDeclaration::forType('int')->getMessage());
        self::assertStringContainsString('unusable', InvalidContentDeclaration::forShape('the file returned void')->getMessage());
    }

    public function testTheBootExceptionNamesTheOrder(): void
    {
        self::assertStringContainsString('has not booted', NotBooted::beforeResolution()->getMessage());
    }
}
