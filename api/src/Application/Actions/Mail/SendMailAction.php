<?php

declare(strict_types=1);

namespace App\Application\Actions\Mail;

use Psr\Http\Message\ResponseInterface as Response;
use App\Domain\Mail\MailErrorException;
use App\Application\Utility\Utils;

class SendMailAction extends MailAction
{
    /** @var PHPMailer */
    protected $phpMailer;

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        return $this->respondWithData([$trace_id]);

        if (empty($this->auth_data['user_id'])) {
            throw new MailErrorException("Thiếu quyền truy cập", 113);
        }
        
        $data_mails = $this->mailRepository->getMailPending(1);
        
        if (empty($data_mails)) {
            throw new MailErrorException("Không có mail nào cần gửi", 114);
        }
        
        $output = [];
        // Process sending emails
        // clear all
        $this->phpMailer->ClearAllRecipients(); 
        foreach ($data_mails as $record) {
            $send_to = json_decode($record['send_to']) ?? [];
            $cc = json_decode($record['cc']) ?? [];
            $bcc = json_decode($record['bcc']) ?? [];

            // Update status
            $this->mailRepository->updateMail($record['mail_id'], ["status" => "sent"]);

            // Send email
            if(!empty($send_to) && is_array($send_to)) {
                foreach ($send_to as $email) {
                    $this->phpMailer->addAddress($email, $email);
                }
            }
            if(!empty($cc) && is_array($cc)) {
                foreach ($cc as $email) {
                    $this->phpMailer->addCC($email, $email);
                }
            }
            if(!empty($bcc) && is_array($bcc)) {
                foreach ($bcc as $email) {
                    $this->phpMailer->addBCC($email, $email);
                }
            }
            $this->phpMailer->Subject = $record['subject'];
            $this->phpMailer->Body = '<h1>Hello from AWS SES!</h1>';
            $this->phpMailer->AltBody = 'Hello from AWS SES! (Plain text)';
            $mail_result = $this->phpMailer->send();
            if (!$mail_result) {
                $data_update = [
                    "status" => "failed",
                    "error" => $this->phpMailer->ErrorInfo,
                    "time_sent" => date("Y-m-d H:i:s"),
                    "sent_count" => $record['sent_count'] + 1
                ];
                $this->mailRepository->updateMail($record['mail_id'], $data_update);
            } else {
                $output[$record['mail_id']] = "Message sent!";
            }
        }

        return $this->respondWithData($output);
    }
}
