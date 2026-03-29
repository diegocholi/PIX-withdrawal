<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Advanced\Swagger;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfig;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Swagger\OpenApiDocumentFactory;

final class OpenApiDocumentFactoryTest extends TestCase
{
    public function testFactoryBuildsOpenApiDocumentWithPublishedPathsAndSchemas(): void
    {
        $document = $this->factory()->create()->toArray();

        self::assertSame('3.0.3', $document['openapi']);
        self::assertSame('PIX Withdrawal API', $document['info']['title']);
        self::assertArrayHasKey('/account/{accountId}/balance/withdraw', $document['paths']);
        self::assertArrayHasKey('/account/{accountId}/balance/withdraw/{withdrawId}', $document['paths']);
        self::assertSame(
            '#/components/schemas/CreateWithdrawRequest',
            $document['paths']['/account/{accountId}/balance/withdraw']['post']['requestBody']['content']['application/json']['schema']['$ref']
        );
        self::assertSame(
            '#/components/schemas/FindWithdrawStatusResponse',
            $document['paths']['/account/{accountId}/balance/withdraw/{withdrawId}']['get']['responses']['200']['content']['application/json']['schema']['$ref']
        );
        self::assertArrayHasKey('ErrorResponse', $document['components']['schemas']);
    }

    public function testFactoryDocumentsAsyncFlowAndErrorExamples(): void
    {
        $document = $this->factory()->create()->toArray();
        $createOperation = $document['paths']['/account/{accountId}/balance/withdraw']['post'];
        $requestSchema = $document['components']['schemas']['CreateWithdrawRequest'];
        $scheduleSchema = $document['components']['schemas']['CreateWithdrawSchedule'];

        self::assertStringContainsString('processamento assincrono', $createOperation['description']);
        self::assertArrayHasKey('accepted_immediate', $createOperation['responses']['202']['content']['application/json']['examples']);
        self::assertArrayHasKey('accepted_scheduled', $createOperation['responses']['202']['content']['application/json']['examples']);
        self::assertSame(
            '2026-04-01T09:00:00-03:00',
            $createOperation['requestBody']['content']['application/json']['examples']['scheduled_withdraw']['value']['schedule']['at']
        );
        self::assertSame('2026-04-01T09:00:00-03:00', $requestSchema['example']['schedule']['at']);
        self::assertSame('2026-04-01T09:00:00-03:00', $scheduleSchema['example']['at']);
        self::assertSame(
            '2026-04-01T09:00:00-03:00',
            $createOperation['responses']['202']['content']['application/json']['examples']['accepted_scheduled']['value']['data']['scheduled_for']
        );
        self::assertSame(
            'http.validation_error',
            $createOperation['responses']['422']['content']['application/json']['examples']['default']['value']['error']['code']
        );
        self::assertSame(
            'withdraw.insufficient_funds',
            $createOperation['responses']['409']['content']['application/json']['examples']['insufficient_funds']['value']['error']['code']
        );
        self::assertSame(
            'withdraw.duplicate_request_blocked',
            $createOperation['responses']['409']['content']['application/json']['examples']['duplicate_request_blocked']['value']['error']['code']
        );
        self::assertSame(
            'withdraw.not_found',
            $document['paths']['/account/{accountId}/balance/withdraw/{withdrawId}']['get']['responses']['404']['content']['application/json']['examples']['default']['value']['error']['code']
        );
    }

    private function factory(): OpenApiDocumentFactory
    {
        return new OpenApiDocumentFactory(
            new AdvancedPluginConfig(
                openApiTitle: 'PIX Withdrawal API',
                openApiVersion: '1.0.0',
                specPath: '/openapi.json',
                swaggerUiPath: '/docs',
                swaggerUiEnabled: true,
            )
        );
    }
}
