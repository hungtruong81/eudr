<?php

declare(strict_types=1);

namespace App\Domain\Mail;

use JsonSerializable;

class Mail implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $mail_id;
    /**
     * @var string
    */
    private $message_code;
    /**
     * @var string
     */
    private $send_name;
    /**
     * @var string
     */
    private $send_from;
    /**
     * @var string
     */
    private $send_to;
    /**
     * @var string
     */
    private $cc;
    /**
     * @var string
     */
    private $bcc;
    /**
     * @var string
     */
    private $reply_to;
    /**
     * @var string
     */
    private $subject;
    /**
     * @var string
     */
    private $content;
    /**
     * @var string
     */
    private $content_plain;
    /**
     * @var string
     */
    private $calendar;
    /**
     * @var string
     */
    private $status;
    /**
     * @var date
     */
    private $time_sent;
    /**
     * @var int
     */
    private $sent_count;
    /**
     * @var string
     */
    private $error;
    /**
     * @var date
     */
    private $created_at;
    /**
     * @var int|null
     */
    private $created_by;
    /**
     * @param int|null  $mail_id
     * @param array    $data
     */
    public function __construct(?int $mail_id, array $data)
    {
        $this->mail_id = $mail_id;
        $this->message_code = $data['message_code'] ?? '';
        $this->send_name = $data['send_name'] ?? '';
        $this->send_from = $data['send_from'] ?? '';
        $this->send_to = !empty($data['send_to']) ? json_decode($data['send_to'], true) : [];
        $this->cc = !empty($data['cc']) ? json_decode($data['cc'], true) : [];
        $this->bcc = !empty($data['bcc']) ? json_decode($data['bcc'], true) : [];
        $this->reply_to = !empty($data['reply_to']) ? json_decode($data['reply_to'], true) : [];
        $this->subject = $data['subject'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->content_plain = $data['content_plain'] ?? '';
        $this->calendar = $data['calendar'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->time_sent = $data['time_sent'] ?? '';
        $this->sent_count = $data['sent_count'] ?? 0;
        $this->error = $data['error'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
        $this->created_by = $data['created_by'] ?? 0;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->mail_id;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->message_code;
    }
    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'mail_id' => $this->mail_id,
            'message_code' => $this->message_code,
            'send_name' => $this->send_name,
            'send_from' => $this->send_from,
            'send_to' => $this->send_to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->reply_to,
            'subject' => $this->subject,
            'content' => $this->content,
            'content_plain' => $this->content_plain,
            'calendar' => $this->calendar,
            'status' => $this->status,
            'time_sent' => $this->time_sent,
            'sent_count' => $this->sent_count,
            'error' => $this->error,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
        ];
    }
}
