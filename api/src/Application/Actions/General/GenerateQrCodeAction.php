<?php

declare(strict_types=1);

namespace App\Application\Actions\General;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class GenerateQrCodeAction extends GeneralAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {

        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('code', $formData['code'] ?? null, 'required|string|min:1|max:100');
        $validator->validate('type', $formData['type'] ?? null, 'required|in:transaction_ticket');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'code' => 'string',
            'type' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $code = $cleanData['code'];
        $type = $cleanData['type'];

        // URL encode trong QR
        $url_web = $this->settings->get('url_web');
        $url_data = $url_web . "/voucher/?c=" . urlencode($code);

        $writer = new PngWriter();

        // Create QR code
        $qrCode = new QrCode(
            data: $url_data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        // Create generic logo
        // $logo = new Logo(
        //     path: __DIR__.'/assets/bender.png',
        //     resizeToWidth: 50,
        //     punchoutBackground: true
        // );

        // Create generic label
        // $label = new Label(
        //     text: 'Label',
        //     textColor: new Color(255, 0, 0)
        // );

        //$result = $writer->write($qrCode, $logo, $label);
        $result = $writer->write($qrCode);
        
        $base64 = $result->getDataUri();

        $data_return = [
            "result" => "success",
            "qr_code" => $base64,
        ];
        return $this->respondWithData($data_return);
    }
}
