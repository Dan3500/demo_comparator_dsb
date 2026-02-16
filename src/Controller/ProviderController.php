<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller that simulates external insurance provider APIs.
 * These endpoints mock real provider behavior for testing purposes.
 */
class ProviderController extends AbstractController
{
    /**
     * Provider A - JSON API (simulates external server)
     *
     * Settings:
     * - Response time: ~2 seconds
     * - Error rate: 10% (returns HTTP 500)
     * - Format: JSON
     *
     * Pricing Logic:
     * - Base price: 217€
     * - Age: 18-24 +70€, 25-55 +0€, 56+ +90€
     * - Vehicle: SUV +100€, Compact +10€
     * - Commercial use: +15%
     * 
     * @param Request $request 
     *  The HTTP request containing driver and car data 
     *  {"driver_age": 30, "car_form": "suv", "car_use": "commercial"}
     * @return JsonResponse The insurance quote or error message 
     * {"price": "350 EUR"} 
     * or 
     * {"error": "Internal Server Error", "message": "Provider temporarily unavailable"}
     */
    #[Route('/provider-a/quote', name: 'provider_a_quote', methods: ['POST'])]
    public function providerAQuote(Request $request): JsonResponse
    {
        sleep(2);

        if (random_int(1, 100) <= 10) {
            return new JsonResponse(
                ['error' => 'Internal Server Error', 'message' => 'Provider temporarily unavailable'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['driver_age'], $data['car_form'], $data['car_use'])) {
            return new JsonResponse(
                ['error' => 'Bad Request', 'message' => 'Missing required fields: driver_age, car_form, car_use'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $driverAge = (int) ($data['driver_age']);
        $carForm = (string) ($data['car_form']);
        $carUse = (string) ($data['car_use']);

        $price = 217; 

        if ($driverAge >= 18 && $driverAge <= 24) {
            $price += 70;
        } elseif ($driverAge >= 56) {
            $price += 90;
        }

        $carForm = strtolower($carForm);
        if ($carForm === 'suv') {
            $price += 100;
        } elseif ($carForm === 'compact') {
            $price += 10;
        }

        if (strtolower($carUse) === 'commercial') {
            $price = (int) round($price * 1.15);
        }

        return new JsonResponse([
            'price' => $price . ' EUR',
        ]);
    }

    /**
     * Provider B - XML API (simulates external server)
     *
     * Settings:
     * - Response time: ~5 seconds (1% chance of 60 seconds timeout)
     * - Format: XML
     *
     * Pricing Logic:
     * - Base price: 250€
     * - Age: 18-29 +50€, 30-59 +20€, 60+ +100€
     * - Vehicle: Turismo +30€, SUV +200€, Compacto +0€
     * 
     * @param Request $request 
     *  The HTTP request containing driver and car data in XML format
     *  <?xml version="1.0" encoding="UTF-8"?>
     *  <SolicitudCotizacion>
     *      <EdadConductor>30</EdadConductor>
     *      <TipoCoche>suv</TipoCoche>
     *      <UsoCoche>personal</UsoCoche>
     *  </SolicitudCotizacion> 
     * @return Response The insurance quote in XML format or error message in XML format
     * <?xml version="1.0" encoding="UTF-8"?>
     * <RespuestaCotizacion>
     *     <Precio>350</Precio> 
     *     <Moneda>EUR</Moneda>
     * </RespuestaCotizacion>
     */
    #[Route('/provider-b/quote', name: 'provider_b_quote', methods: ['POST'])]
    public function providerBQuote(Request $request): Response
    {
        if (random_int(1, 100) <= 1) {
            sleep(60);
        } else {
            sleep(5);
        }

        $xmlContent = $request->getContent();
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            return new Response(
                '<?xml version="1.0" encoding="UTF-8"?>
                <Error>
                    <Mensaje>Invalid XML</Mensaje>
                </Error>',
                Response::HTTP_BAD_REQUEST,
                ['Content-Type' => 'application/xml']
            );
        }


        if (!isset($xml->EdadConductor, $xml->TipoCoche)) {
            return new Response(
                '<?xml version="1.0" encoding="UTF-8"?>
                <Error>
                    <Mensaje>Missing required fields: driver_age, car_form</Mensaje>
                </Error>',
                Response::HTTP_BAD_REQUEST,
                ['Content-Type' => 'application/xml']
            );
        }
        $driverAge = (int) ($xml->EdadConductor);
        $carType = (string) ($xml->TipoCoche);

        $price = 250.0; 

        if ($driverAge >= 18 && $driverAge <= 29) {
            $price += 50;
        } elseif ($driverAge >= 30 && $driverAge <= 59) {
            $price += 20;
        } else {
            $price += 100;
        }

        $carType = strtolower($carType);
        if ($carType === 'turismo') {
            $price += 30;
        } elseif ($carType === 'suv') {
            $price += 200;
        }

        $response = trim('<?xml version="1.0" encoding="UTF-8"?>
<RespuestaCotizacion>
    <Precio>' . $price . '</Precio>
    <Moneda>EUR</Moneda>
</RespuestaCotizacion>');

        return new Response($response, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
    }
}
