<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Mappers;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\RecordMapper;

final class RecordMapperConventionTest extends TestCase
{
    public function testRecordMapperDefinesCanonicalTranslationContract(): void
    {
        $reflection = new ReflectionClass(RecordMapper::class);

        self::assertTrue($reflection->isInterface());
        self::assertTrue($reflection->hasMethod('toRecord'));
        self::assertTrue($reflection->hasMethod('toDomain'));
        self::assertSame('array', (string) $reflection->getMethod('toRecord')->getReturnType());
        self::assertSame('array', (string) $reflection->getMethod('toDomain')->getParameters()[0]->getType());
        self::assertSame('object', (string) $reflection->getMethod('toDomain')->getReturnType());
    }

    public function testArchitectureDocumentDescribesDataMappingStrategy(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/data-mapping-strategy.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('Plugins\\\\Data\\\\Mappers\\\\RecordMapper', $documentContents);
        self::assertStringContainsString('Money::toDecimal()', $documentContents);
        self::assertStringContainsString('DateTimeImmutable', $documentContents);
        self::assertStringContainsString('ScheduleAt', $documentContents);
    }
}
