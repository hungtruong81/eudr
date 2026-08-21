<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Utility\Utils;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class UserQrCodeAction extends AuthenticationAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        //$user = $this->userRepository->findUserOfPhone($this->auth->phone);
        
        // Get user permissions
        //$permissions = $this->userRepository->getUserPermissions($user->getId());

        // Get user role
        //$user_role = $this->userRepository->getUserRole($this->auth_data['user_id']);

        $phone = $this->auth->phone;

        // URL encode trong QR
        $url_web = $this->settings->get('url_web');
        $url_data = $url_web . "/p=" . urlencode($phone);

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

        $result = $writer->write($qrCode);
        
        $base64 = $result->getDataUri();

        $data_return = [
            "result" => "success",
            "qr_code" => $base64,
        ];
        return $this->respondWithData($data_return);
    }
}
