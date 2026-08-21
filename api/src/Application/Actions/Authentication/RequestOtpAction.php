<?php

declare(strict_types=1);

namespace App\Application\Actions\Authentication;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\User\UserErrorException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use App\Application\Utility\Curl;

class RequestOtpAction extends AuthenticationAction
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

        $validator->validate('phone', $formData['phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
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
            'phone' => 'string',
            'purpose' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $phone = $cleanData['phone'];
        $purpose = $cleanData['purpose'];

        // Check duplicate user phone
        $data_user = $this->userRepository->findUserOfPhone($phone);
        if ($data_user) {
            $message = $validator->getErrorMessage('duplicate_phone', []);
            throw new HttpBadRequestException($this->request, $message);
        }

        // Request OTP
        $request_output = $this->userRepository->requestOtp($phone, $purpose);
        if (empty($request_output)) {
            throw new UserErrorException("Yêu cầu OTP không thành công", 500);
        }
        
        $otp_request_id = $request_output['otp_request_id'];
        $otp_code = $request_output['otp_code'];
        $sms_content_template = $this->settings->get('sms')["templates"]["otp"];
        $message = str_replace("{code}", $otp_code, $sms_content_template);
        // Add SMS to queue
        $sms_code = $this->smsRepository->generateCode();
        $data_sms = array(
            'sms_code' => $sms_code,
            'phone' => $phone,
            'otp_code' => $otp_code,
            'message' => $message,
            'status' => 'pending',
        );
        
        $data_sms = $this->smsRepository->addSmsQueue($data_sms);
        if (empty($data_sms)) {
            throw new UserErrorException("Không thể thêm SMS vào hàng đợi", 500);
        }

        // Trigger send sms
        $trigger_send_sms = false;
        if($trigger_send_sms) {
            $api_url_sms_send = $this->settings->get('url_api') . "/v1/sms/send/";
            $params = ['sms_id' => $data_sms->getId()];
            $sms_res = Curl::get($api_url_sms_send, $params, $headers = []);
        }

        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'auth',
            "action" => 'request_otp',
            "user_id" => 0,
            "extra_1" => $otp_request_id,
        );

        Utils::save_log($this->logger, $log);

        $data_response = [
            'otp_request_id' => $otp_request_id,
        ];

        if(!$trigger_send_sms) {
            $data_response['otp_code'] = $otp_code;
        }

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $data_response;
        
        return $this->respondWithData($res_return);

    }
}
