<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;

/**
 * The composition root boots the declared graph exactly once per suite, from
 * the same entry point production uses. This test asserts the graph that
 * boot declared, by the contract ids consumers resolve.
 */
final class BootTest extends \WP_UnitTestCase
{
    /** @return list<class-string> */
    private static function declaredServices(): array
    {
        return [
            \Iniznet\Mahout\Assets\AssetsConfig::class,
            \Iniznet\Mahout\Assets\EntryEnqueuer::class,
            \Iniznet\Mahout\Db\Contracts\SqlConnection::class,
            \Iniznet\Mahout\Db\Contracts\TableGateway::class,
            \Iniznet\Mahout\Content\Contracts\Registrar::class,
            \Iniznet\Mahout\Fields\Contracts\FieldRegistry::class,
            \Iniznet\Mahout\Fields\Contracts\FieldReader::class,
            \Iniznet\Mahout\Fields\Contracts\FieldWriter::class,
            \Iniznet\Howdah\Support\ClassResolver::class,
        ];
    }

    public function testTheCompositionRootDeclaredTheGraph(): void
    {
        $services = Bootstrap::services();

        foreach (self::declaredServices() as $id) {
            self::assertTrue($services->has($id), sprintf('The composition root did not declare %s.', $id));
        }
    }
}
