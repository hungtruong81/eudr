<?php

declare(strict_types=1);

namespace App\Application\Actions\Mail;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Mail\MailErrorException;
use App\Application\Utility\Utils;


class AddMailQueueAction extends MailAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new MailErrorException("Thiếu quyền truy cập", 113);
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
            throw new MailErrorException("Thiếu trường dữ liệu ".implode(", ", $missing_fields), 101);
        }

        $send_to = [];
        if (!empty($formData['send_to']) && is_array($formData['send_to'])) {
            $send_to = $formData['send_to'];
        }
        // Check if send_to is not empty
        if (empty($send_to)) {
            throw new MailErrorException("Trường 'send_to' không được để trống", 101);
        }

        // Validate send_to field
        if (!Utils::isValidEmails($send_to)) {
            throw new MailErrorException("Trường 'send_to' địa chỉ email không hợp lệ", 102);
        }
        
        $cc = [];
        if (!empty($formData['cc']) && is_array($formData['cc'])) {
            $cc = $formData['cc'];
        }

        if (!empty($cc) && !Utils::isValidEmails($cc)) {
            throw new MailErrorException("Trường 'cc' địa chỉ email không hợp lệ", 102);
        }

        $bcc = [];
        if (!empty($formData['bcc']) && is_array($formData['bcc'])) {
            $bcc = $formData['bcc'];
        }

        if (!empty($bcc) && !Utils::isValidEmails($bcc)) {
            throw new MailErrorException("Trường 'bcc' địa chỉ email không hợp lệ", 102);
        }

        $reply_to = [];
        if (!empty($formData['reply_to']) && is_array($formData['reply_to'])) {
            $reply_to = $formData['reply_to'];
        }

        if (!empty($reply_to) && !Utils::isValidEmails($reply_to)) {
            throw new MailErrorException("Trường 'reply_to' địa chỉ email không hợp lệ", 102);
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

        $message_code = $this->mailRepository->generateCode();

        $send_from = $this->settings->get('mail')["send_from"];
        $send_name = $this->settings->get('mail')["send_name"];

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
        
        $mail = $this->mailRepository->addMailQueue($data_update);
        if (!$mail) {
            throw new MailErrorException("Không thể thêm email vào hàng đợi", 103);
        }

        if ($trigger_send) {
            // Code to trigger sending the email
        }

        $action = 'add_mail_queue';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'mail',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'] ?? '',
            "extra_1" => (string)$mail->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['mail'] = $mail->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
