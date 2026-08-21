<?php

declare(strict_types=1);

namespace App\Application\Actions\Sms;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;


class AddSmsQueueAction extends SmsAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        echo $trace_id;die;
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        // Validate data fields
        $required_fields = [
            'send_to', 
            'subject', 
            'content',
        ];

        $missing_fields = Utils::validFields($required_fields, $formData);
        if (!empty($missing_fields)) {
            throw new HttpBadRequestException($this->request, "Thiếu trường dữ liệu ".implode(", ", $missing_fields));
        }

        $send_to = [];
        if (!empty($formData['send_to']) && is_array($formData['send_to'])) {
            $send_to = $formData['send_to'];
        }
        // Check if send_to is not empty
        if (empty($send_to)) {
            throw new HttpBadRequestException($this->request, "Trường 'send_to' không được để trống");
        }

        // Validate send_to field
        if (!Utils::isValidEmails($send_to)) {
            throw new HttpBadRequestException($this->request, "Trường 'send_to' địa chỉ email không hợp lệ");
        }
        
        $cc = [];
        if (!empty($formData['cc']) && is_array($formData['cc'])) {
            $cc = $formData['cc'];
        }

        if (!empty($cc) && !Utils::isValidEmails($cc)) {
            throw new HttpBadRequestException($this->request, "Trường 'cc' địa chỉ email không hợp lệ");
        }

        $bcc = [];
        if (!empty($formData['bcc']) && is_array($formData['bcc'])) {
            $bcc = $formData['bcc'];
        }

        if (!empty($bcc) && !Utils::isValidEmails($bcc)) {
            throw new HttpBadRequestException($this->request, "Trường 'bcc' địa chỉ email không hợp lệ");
        }

        $reply_to = [];
        if (!empty($formData['reply_to']) && is_array($formData['reply_to'])) {
            $reply_to = $formData['reply_to'];
        }

        if (!empty($reply_to) && !Utils::isValidEmails($reply_to)) {
            throw new HttpBadRequestException($this->request, "Trường 'reply_to' địa chỉ email không hợp lệ");
        }

        $subject = "";
        if (!empty($formData['subject'])) {
            $subject = htmlspecialchars(trim($formData['subject']));
        }

        $content = "";
        if (!empty($formData['content'])) {
            $content = htmlspecialchars(trim($formData['content']));
        }

        $content_plain = "";
        if (!empty($formData['content_plain'])) {
            $content_plain = htmlspecialchars(trim($formData['content_plain']));
        }

        $trigger_send = false;
        if (!empty($formData['trigger_send']) && is_bool($formData['trigger_send'])) {
            $trigger_send = $formData['trigger_send'];
        }

        $message_code = $this->smsRepository->generateCode();

        $send_from = $this->settings->get('sms')["send_from"];
        $send_name = $this->settings->get('sms')["send_name"];

        $data_update = [
            "message_code" => $message_code,
            "send_name" => $send_name,
            "send_from" => $send_from,
            "send_to" => json_encode($send_to),
            "subject" => $subject,
            "content" => $content,
            "content_plain" => $content_plain,
            "cc" => json_encode($cc),
            "bcc" => json_encode($bcc),
            "created_by" => $this->auth_data['user_id'] ?? 0,
        ];

        $sms = $this->smsRepository->addSmsQueue($data_update);
        if (!$sms) {
            throw new HttpBadRequestException($this->request, "Không thể thêm SMS vào hàng đợi");
        }

        if ($trigger_send) {
            // Code to trigger sending the SMS
        }

        $action = 'add_sms_queue';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'sms',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'] ?? '',
            "extra_1" => (string)$sms->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['sms'] = $sms->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
