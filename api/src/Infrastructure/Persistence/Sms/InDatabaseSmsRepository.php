<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sms;

use App\Domain\Sms\Sms;
use App\Domain\Sms\SmsNotFoundException;
use App\Domain\Sms\SmsRepository;
use App\Application\Utility\Utils;

class InDatabaseSmsRepository implements SmsRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * InDatabaseSmsRepository constructor.
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
    public function findSmsOfCode(string $code): ?Sms
    {
        $this->db->where("sms.sms_code", $code);
        $sms = $this->db->getOne("eudr_sms_logs sms");
        if (empty($sms)) {
            return null;
        }
        return new Sms($sms['sms_id'], $sms);
    }

    /**
     * {@inheritdoc}
     */
    public function getSmsPending(int $limit = 2, int $sms_id = 0): array
    {
        $this->db->where("sms.status", "pending");
        if ($sms_id > 0) {
            $this->db->where("sms.sms_id", $sms_id);
        }
        $this->db->orderBy("sms.sms_id", "ASC");
        $smss = $this->db->get("eudr_sms_logs sms", $limit);

        return $smss;
        
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "smsc-".date("ymd").'-'.Utils::generateRandomString(8);
            $sms = $this->findSmsOfCode($code);
            if (!$sms) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function findSmsOfId(int $sms_id): ?Sms
    {
        $this->db->where("sms.sms_id", $sms_id);
        $sms = $this->db->getOne("eudr_sms_logs sms");
        if (empty($sms)) {
            return null;
        }
        return new Sms($sms['sms_id'], $sms);
    }

    /**
     * {@inheritdoc}
     */
    public function addSmsQueue(array $data): ?Sms
    {
        $data['created_at'] = date("Y-m-d H:i:s", time());
        $sms_id = $this->db->insert("eudr_sms_logs", $data);

        if (empty($sms_id)) {
            throw new SmsNotFoundException("Could not add SMS to queue: " . $this->db->getLastError());
        }

        return $this->findSmsOfId($sms_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateSms(int $sms_id, array $data_update): Sms
    {
        $this->db->where("sms_id", $sms_id);
        $this->db->update("eudr_sms_logs", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new SmsNotFoundException("SMS not found with ID: $sms_id");
        }
        return $this->findSmsOfId($sms_id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function sendSms(int $sms_id): bool
    {
        $sms = $this->findSmsOfId($sms_id);
        return true;
    }

}
