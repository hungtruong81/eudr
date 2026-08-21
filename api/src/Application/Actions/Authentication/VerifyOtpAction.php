<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class VerifyOtpAction extends AuthenticationAction
{

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('otp_request_id', $formData['otp_request_id'] ?? null, 'required|integer');
        $validator->validate('phone', $formData['phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
        $validator->validate('otp_code', $formData['otp_code'] ?? null, 'required|string');
        $validator->validate('purpose', $formData['purpose'] ?? null, 'required|in:register,other');

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
            'otp_request_id' => 'integer',
            'phone' => 'string',
            'otp_code' => 'string',
            'purpose' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $otp_request_id = $cleanData['otp_request_id'];
        $phone = $cleanData['phone'];
        $otp_code = $cleanData['otp_code'];
        $purpose = $cleanData['purpose'];

        // Check duplicate user phone
        $data_user = $this->userRepository->findUserOfPhone($phone);
        if ($data_user) {
            $message = $validator->getErrorMessage('duplicate_phone', []);
            throw new HttpBadRequestException($this->request, $message);
        }
        
        // Check OTP
        $is_valid_otp = $this->userRepository->verifyOtp($otp_request_id, $phone, $otp_code, $purpose);
        if (!$is_valid_otp) {
            throw new HttpBadRequestException($this->request, "OTP không hợp lệ");
        }

        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'auth',
            "action" => 'verify_otp',
            "user_id" => 0,
            "extra_1" => $otp_request_id,
        );

        Utils::save_log($this->logger, $log);

        $res_return =  [
            "result" => 'success',
        ];

        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return, 200);

    }
}
