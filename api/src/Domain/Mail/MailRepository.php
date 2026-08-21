<?php

declare(strict_types=1);

namespace App\Domain\Mail;

interface MailRepository
{
    /**
     * @param array $params
     * @return Mail[]
     */
    public function findAll(array $params = []): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param string $code
     * @return Mail|null
     */
    public function findMailOfCode(string $code): ?Mail;
    /**
     * @param int $mail_id
     * @return Mail|null
     */
    public function findMailOfId(int $mail_id): ?Mail;
    /**
     * @param array $data
     * @return Mail
     */
    public function addMailQueue(array $data): ?Mail;
    /**
     * @param int $mail_id
     * @param array $data_update
     * @return Mail
     */
    public function updateMail(int $mail_id, array $data_update): Mail;
    /**
     * @param int $mail_id
     * @param int $limit
     * @return Mail[]
     */
    public function getMailPending(int $limit, int $mail_id = 0): array;

}
