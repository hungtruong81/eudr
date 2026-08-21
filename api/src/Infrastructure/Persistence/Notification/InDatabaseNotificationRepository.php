<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Notification;

use App\Domain\Notification\NotificationRepository;

class InDatabaseNotificationRepository implements NotificationRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * InDatabaseNotificationRepository constructor.
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
        $status = $params['status'] ?? 'all';
        $permission_status = $params['permission_status'] ?? '';
        $related_type = $params['related_type'] ?? 'all';
        $user_id = $params['user_id'] ?? 0;

        
        // Count total records
        $total_records = 0;

        if ($status === 'unread') {
            $this->db->where('noti.read_at', NULL, 'IS');
        } elseif ($status === 'read') {
            $this->db->where('noti.read_at', NULL, 'IS NOT');
        }
        if ($related_type !== 'all') {
            $this->db->where('noti.related_type', $related_type);
        }
        $this->db->where('noti.user_id', $user_id);
        $total_records = $this->db->getValue('eudr_notifications noti', 'count(*)');

        // Fetch records
        $cols = "noti.*";
        $this->db->pageLimit = $page_limit;
        $this->db->where('noti.user_id', $user_id);
        if ($status === 'unread') {
            $this->db->where('noti.read_at', NULL, 'IS');
        } elseif ($status === 'read') {
            $this->db->where('noti.read_at', NULL, 'IS NOT');
        }
        if ($related_type !== 'all') {
            $this->db->where('noti.related_type', $related_type);
        }
        $this->db->orderBy('noti.created_at', 'DESC');
        $records = $this->db->arraybuilder()->paginate("eudr_notifications noti", $page, $cols);

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $records,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function createNotification(array $params) : int
    {
        $data = [
            'user_id' => $params['user_id'] ?? 0,
            'title' => $params['title'] ?? '',
            'type' => $params['type'] ?? '',
            'message' => $params['message'] ?? '',
            'related_id' => $params['related_id'] ?? 0,
            'related_code' => $params['related_code'] ?? '',
            'related_type' => $params['related_type'] ?? '',
            'read_at' => NULL,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $notification_id = $this->db->insert('eudr_notifications', $data);
        if (!$notification_id) {
            throw new \Exception("Failed to create notification");
        }

        return $notification_id;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsRead(int $user_id, array $notification_ids): bool
    {
        $this->db->where('read_at', NULL, 'IS');
        $this->db->where('notification_id', $notification_ids, 'IN');
        $this->db->where('user_id', $user_id);
        $update_data = [
            'read_at' => date('Y-m-d H:i:s'),
        ];
        if (!$this->db->update('eudr_notifications', $update_data)) {
            throw new \Exception("Failed to mark notification as read");
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedTypes(array $params): array
    {
        $this->db->groupBy('related_type');
        $related_types = $this->db->get('eudr_notifications', NULL, 'related_type');

        return $related_types;
    }

}
