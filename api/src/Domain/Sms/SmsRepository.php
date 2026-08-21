<?php

declare(strict_types=1);

namespace App\Domain\Sms;

interface SmsRepository
{
    /**
     * @param array $params
     * @return Sms[]
     */
    public function findAll(array $params = []): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param string $code
     * @return Sms|null
     */
    public function findSmsOfCode(string $code): ?Sms;
    /**
     * @param int $sms_id
     * @return Sms|null
     */
    public function findSmsOfId(int $sms_id): ?Sms;
    /**
     * @param array $data
     * @return Sms|null
     */
    public function addSmsQueue(array $data): ?Sms;
    /**
     * @param int $sms_id
     * @param array $data_update
     * @return Sms
     */
    public function updateSms(int $sms_id, array $data_update): Sms;
    /**
     * @param int $sms_id
     * @param int $limit
     * @return Sms[]
     */
    public function getSmsPending(int $limit, int $sms_id = 0): array;
    /**
     * @param int $sms_id
     * @return bool
     */
    public function sendSms(int $sms_id): bool;

}
