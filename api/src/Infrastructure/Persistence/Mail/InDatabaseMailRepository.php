<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mail;

use App\Domain\Mail\Mail;
use App\Domain\Mail\MailNotFoundException;
use App\Domain\Mail\MailRepository;
use App\Application\Utility\Utils;

class InDatabaseMailRepository implements MailRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * InDatabaseMailRepository constructor.
     *
     * @param MysqliDb $db
     */
    public function __construct(\MysqliDb $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $plot_id = $params['plot_id'] ?? 0;
        $permission_status = $params['permission_status'] ?? '';
        $user_id = $params['user_id'] ?? 0;

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function findMailOfCode(string $code): ?Mail
    {
        $this->db->where("mail.message_code", $code);
        $mail = $this->db->getOne("eudr_general_mail_queue mail");
        if (empty($mail)) {
            return null;
        }
        return new Mail($mail['mail_id'], $mail);
    }

    /**
     * {@inheritdoc}
     */
    public function getMailPending(int $limit = 2, int $mail_id = 0): array
    {
        $this->db->where("mail.status", "pending");
        if ($mail_id > 0) {
            $this->db->where("mail.mail_id", $mail_id);
        }
        $this->db->orderBy("mail.mail_id", "ASC");
        $mails = $this->db->get("eudr_general_mail_queue mail", $limit);
        
        return $mails;
        
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "mail-".date("ymd").'-'.Utils::generateRandomString(8);
            $mail = $this->findMailOfCode($code);
            if (!$mail) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function findMailOfId(int $mail_id): ?Mail
    {
        $this->db->where("mail.mail_id", $mail_id);
        $mail = $this->db->getOne("eudr_general_mail_queue mail");
        if (empty($mail)) {
            return null;
        }
        return new Mail($mail['mail_id'], $mail);
    }

    /**
     * {@inheritdoc}
     */
    public function addMailQueue(array $data): ?Mail
    {
        $data['created_at'] = date("Y-m-d H:i:s", time());
        $mail_id = $this->db->insert("eudr_general_mail_queue", $data);
        
        if (empty($mail_id)) {
            throw new MailNotFoundException("Could not add mail to queue: " . $this->db->getLastError());
        }

        return $this->findMailOfId($mail_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateMail(int $mail_id, array $data_update): Mail
    {
        $this->db->where("mail_id", $mail_id);
        $this->db->update("eudr_general_mail_queue", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new MailNotFoundException("Mail not found with ID: $mail_id");
        }
        return $this->findMailOfId($mail_id);
    }

}
