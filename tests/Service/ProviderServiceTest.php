<?php

namespace App\Tests\Service;

use App\Service\ProviderService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProviderServiceTest extends TestCase
{
    private function createMockHttpClient(array $responses): HttpClientInterface
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        
        $httpClient->method('request')
            ->willReturnCallback(function ($method, $url) use ($responses) {
                $providerKey = str_contains($url, 'provider-a') ? 'provider-a' : 'provider-b';
                return $responses[$providerKey];
            });
        
        return $httpClient;
    }

    private function createMockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function createJsonResponse(int $price): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn(['price' => $price . ' EUR']);
        return $response;
    }

    private function createXmlResponse(float $price): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(
            "<?xml version=\"1.0\"?><RespuestaCotizacion><Precio>{$price}</Precio><Moneda>EUR</Moneda></RespuestaCotizacion>"
        );
        return $response;
    }

    // PROVIDER A PRICING TESTS
    // ==========================================

    public function testProviderAPricingBasePrice(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(217),
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(30, 'turismo', 'privado');
        
        $providerA = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-a');
        $providerA = array_values($providerA)[0];
        
        $this->assertEquals(217, $providerA['price']);
    }

    public function testProviderAPricingYoungDriver(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(287),
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(22, 'turismo', 'privado');
        
        $providerA = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-a');
        $providerA = array_values($providerA)[0];
        
        $this->assertEquals(287, $providerA['price']);
    }

    public function testProviderAPricingSeniorDriver(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(307),
            'provider-b' => $this->createXmlResponse(380),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(60, 'turismo', 'privado');
        
        $providerA = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-a');
        $providerA = array_values($providerA)[0];
        
        $this->assertEquals(307, $providerA['price']);
    }

    public function testProviderAPricingSUV(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(317),
            'provider-b' => $this->createXmlResponse(470),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(30, 'suv', 'privado');
        
        $providerA = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-a');
        $providerA = array_values($providerA)[0];
        
        $this->assertEquals(317, $providerA['price']);
    }

    public function testProviderAPricingCommercialUse(): void
    {
        // Age 30 + commercial (+15%) = 217 * 1.15 = 250
        $responses = [
            'provider-a' => $this->createJsonResponse(250),
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(30, 'turismo', 'comercial');
        
        $providerA = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-a');
        $providerA = array_values($providerA)[0];
        
        $this->assertEquals(250, $providerA['price']);
    }

    
    // PROVIDER B PRICING TESTS
    // ==========================================

    public function testProviderBPricingAdultDriver(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(217),
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(35, 'turismo', 'privado');
        
        $providerB = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-b');
        $providerB = array_values($providerB)[0];
        
        $this->assertEquals(300, $providerB['price']);
    }

    public function testProviderBPricingYoungDriver(): void
    {
        // Age 25 (18-29 = +50) + turismo (+30) = 250 + 50 + 30 = 330
        $responses = [
            'provider-a' => $this->createJsonResponse(287),
            'provider-b' => $this->createXmlResponse(330),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(25, 'turismo', 'privado');
        
        $providerB = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-b');
        $providerB = array_values($providerB)[0];
        
        $this->assertEquals(330, $providerB['price']);
    }

    public function testProviderBPricingSUV(): void
    {
        // Age 40 (30-59 = +20) + SUV (+200) = 250 + 20 + 200 = 470
        $responses = [
            'provider-a' => $this->createJsonResponse(317),
            'provider-b' => $this->createXmlResponse(470),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(40, 'suv', 'privado');
        
        $providerB = array_filter($result['quotes'], fn($q) => $q['provider'] === 'provider-b');
        $providerB = array_values($providerB)[0];
        
        $this->assertEquals(470, $providerB['price']);
    }

    // ==========================================
    // SORTING TESTS
    // ==========================================

    public function testQuotesSortedByPriceAscending(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(317),
            'provider-b' => $this->createXmlResponse(270),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(30, 'suv', 'privado');
        
        $this->assertCount(2, $result['quotes']);
        $this->assertEquals('provider-b', $result['quotes'][0]['provider']);
        $this->assertEquals('provider-a', $result['quotes'][1]['provider']);
        $this->assertLessThan($result['quotes'][1]['price'], $result['quotes'][0]['price']);
    }

    public function testCheapestQuoteIsMarked(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(217),
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(30, 'turismo', 'privado');
        
        $this->assertTrue($result['quotes'][0]['is_cheapest']);
        $this->assertFalse($result['quotes'][1]['is_cheapest']);
    }

    // ==========================================
    // CAMPAIGN DISCOUNT TESTS
    // ==========================================

    public function testCampaignDiscountApplied(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(200),
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            true // Campaign active
        );
        
        $result = $service->getFromProviders(30, 'turismo', 'privado');
        
        $this->assertTrue($result['campaign_active']);
        $this->assertEquals(5, $result['discount_percentage']);
        
        // Check discounted prices (5% off)
        $providerA = $result['quotes'][0];
        $this->assertEquals(200, $providerA['original_price']);
        $this->assertEquals(190, $providerA['discounted_price']); // 200 * 0.95
    }

    public function testCampaignInactiveNoDiscount(): void
    {
        $responses = [
            'provider-a' => $this->createJsonResponse(200),
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $service = new ProviderService(
            $this->createMockHttpClient($responses),
            $this->createMockLogger(),
            false // Campaign inactive
        );
        
        $result = $service->getFromProviders(30, 'turismo', 'privado');
        
        $this->assertFalse($result['campaign_active']);
        $this->assertEquals(0, $result['discount_percentage']);
        $this->assertArrayNotHasKey('discounted_price', $result['quotes'][0]);
    }

    // ==========================================
    // ERROR HANDLING TESTS
    // ==========================================

    public function testProviderErrorIsHandled(): void
    {
        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(500);
        
        $responses = [
            'provider-a' => $errorResponse,
            'provider-b' => $this->createXmlResponse(300),
        ];
        
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')
            ->willReturnCallback(function ($method, $url) use ($responses) {
                $providerKey = str_contains($url, 'provider-a') ? 'provider-a' : 'provider-b';
                return $responses[$providerKey];
            });
        
        $service = new ProviderService(
            $httpClient,
            $this->createMockLogger(),
            false
        );
        
        $result = $service->getFromProviders(30, 'turismo', 'privado');
        
        $this->assertCount(1, $result['quotes']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('provider-a', $result['errors'][0]['provider']);
    }

    // ==========================================
    // AGE VALIDATION TESTS
    // ==========================================

    public function testDriverMustBeAtLeast18(): void
    {
        $service = new ProviderService(
            $this->createMock(HttpClientInterface::class),
            $this->createMockLogger(),
            false
        );
        
        $request = new Request([], [], [], [], [], [], json_encode([
            'driver_birthday' => '2015-01-01', // ~11 years old
            'car_type' => 'turismo',
            'car_use' => 'privado',
        ]));
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver must be at least 18 years old');
        
        $service->fetchQuotes($request);
    }

    public function testMissingRequiredFieldsThrowsException(): void
    {
        $service = new ProviderService(
            $this->createMock(HttpClientInterface::class),
            $this->createMockLogger(),
            false
        );
        
        $request = new Request([], [], [], [], [], [], json_encode([
            'driver_birthday' => '1990-01-01',
            // Missing car_type and car_use
        ]));
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields');
        
        $service->fetchQuotes($request);
    }
}
