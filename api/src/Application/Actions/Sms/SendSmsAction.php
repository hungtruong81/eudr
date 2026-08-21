<?php

declare(strict_types=1);

namespace App\Application\Actions\Sms;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use App\Application\Utility\Curl;

class SendSmsAction extends SmsAction
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

        $validator->validate('sms_id', $formData['sms_id'] ?? null, 'required|integer|min:1');

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
            'sms_id' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $sms_id = $cleanData['sms_id'];
        $limit = 1; // max sms send per request
        $sms_pending = $this->smsRepository->getSmsPending($limit, $sms_id);

        if(empty($sms_pending)) {
            throw new HttpNotFoundException($this->request, "Không có SMS nào cần gửi");
        }
        
        $sms_api_url = $this->settings->get('sms')["api_url"];
        $sms_api_key = $this->settings->get('sms')["api_key"];
        $sms_secret_key = $this->settings->get('sms')["secret_key"];
        $sms_brand_name = $this->settings->get('sms')["brand_name"];

        foreach ($sms_pending as $record) {
            $sms_id = $record['sms_id'];
            $phone = $record['phone'];
            $message = $record['message'] ?? '';
            $request_id = $sms_id.'-'.time();

            // Update status
            $this->smsRepository->updateSms($sms_id, ["status" => "sent"]);

            // Send SMS
            $data_curl_post = [
                'ApiKey' => $sms_api_key,
                'Content' => $message,
                'Phone' => $phone,
                'SecretKey' => $sms_secret_key,
                'IsUnicode' => '0',
                'Brandname' => $sms_brand_name,
                'SmsType' => '2',
                'RequestId' => $request_id,
                'CallbackUrl' => '',
                'campaignid' => 'EUDR-SMS',
            ];

            $result = Curl::post($sms_api_url, $data_curl_post, $headers = []);
            
            $status = 'failed';
            if(!empty($result['success']) && !empty($result['data']['SMSID']) && $result['data']['CodeResult'] == '100') {
                $status = 'sent';
            }
            // Update response payload
            $data_update = [
                'request_payload' => json_encode($data_curl_post),
                'response_payload' => !empty($result) ? json_encode($result) : null,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $this->smsRepository->updateSms($sms_id, $data_update);
        }

        $res_return =  [
            "result" => 'success',
        ];

        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
