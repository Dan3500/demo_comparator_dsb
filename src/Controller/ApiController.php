<?php

namespace App\Controller;

use App\DTO\Response\CalculateResponseDTO;
use App\Service\QuoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    /**
     * Calculates insurance quotes by fetching data from multiple providers.
     * 
     * @param QuoteService $quoteService 
     * The service responsible for fetching quotes from providers
     * @param Request $request 
     *  The HTTP request containing driver and car data 
     *  {"driver_birthday": "1990-01-01", "car_type": "turismo", "car_use": "privado"}
     * @return JsonResponse The insurance quotes response
     */
    #[Route('/v1/calculate', name: 'api_calculate', methods: ['POST'])]
    public function calculate_quotes(QuoteService $quoteService, Request $request): JsonResponse
    {
        try {
            $result = $quoteService->fetchQuotes($request);
            $responseDTO = CalculateResponseDTO::fromArray($result);
            
            return new JsonResponse($responseDTO);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Validation error',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to fetch quotes', 
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
