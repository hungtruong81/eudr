<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Connection;

use App\Domain\Connection\Connection;
use App\Domain\Connection\ConnectionNotFoundException;
use App\Domain\Connection\ConnectionRepository;
use App\Application\Utility\Utils;

class InDatabaseConnectionRepository implements ConnectionRepository
{
    /**
     * @var MysqliDb
     */
    private $db;

    /**
     * InDatabaseConnectionRepository constructor.
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
        $type = $params['type'] ?? 'received'; // received, sent, all
        $status = $params['status'] ?? 'all'; // active, removed, blocked
        $user_id = $params['user_id'] ?? 0;
        $account_type = $params['account_type'] ?? null;

        // Build status condition
        $statusCondition = "";
        if($status !== 'all') {
            $statusCondition = " AND con.status = '{$status}'";
        }

        // Build account type condition (multi-role: filter via eudr_user_roles)
        $accountTypeCondition = "";
        if (!empty($account_type)) {
            $roleFilterSQL = Utils::buildRoleFilterSQL('user.user_id', $account_type);
            if (!empty($roleFilterSQL)) {
                $accountTypeCondition = " AND {$roleFilterSQL}";
            }
        }

        // Count total records
        $total_records = 0;
        
        if($type == "all") {
            // For 'all' type, use UNION to get both sent and received connections
            $countQuery = "
                SELECT COUNT(*) as total FROM (
                    SELECT con.connection_id
                    FROM eudr_connections con
                    LEFT JOIN eudr_users user ON user.user_id = con.target_user_id
                    WHERE con.is_deleted = 0 AND user.phone IS NOT NULL 
                    AND con.requester_user_id = {$user_id}
                    {$statusCondition}
                    {$accountTypeCondition}
                    
                    UNION
                    
                    SELECT con.connection_id
                    FROM eudr_connections con
                    LEFT JOIN eudr_users user ON user.user_id = con.requester_user_id
                    WHERE con.is_deleted = 0 AND user.phone IS NOT NULL 
                    AND con.target_user_id = {$user_id}
                    {$statusCondition}
                    {$accountTypeCondition}
                ) as combined_connections
            ";
            $countResult = $this->db->rawQuery($countQuery);
            $total_records = $countResult[0]['total'] ?? 0;
        } else {
            // Original count logic for received/sent
            $this->db->where("con.is_deleted", 0);
            if($status !== 'all') {
                $this->db->where("con.status", $status);
            }
            if (!empty($account_type)) {
                $roleFilterSQL = Utils::buildRoleFilterSQL('user.user_id', $account_type);
                if (!empty($roleFilterSQL)) {
                    $this->db->where($roleFilterSQL);
                }
            }
            if($type == "received") {
                $this->db->where("con.target_user_id", $user_id);
                $this->db->where("user.phone", NULL, "IS NOT");
                $this->db->join("eudr_users user", "user.user_id=con.requester_user_id", "LEFT");
            } elseif ($type == "sent") {
                $this->db->where("con.requester_user_id", $user_id);
                $this->db->where("user.phone", NULL, "IS NOT");
                $this->db->join("eudr_users user", "user.user_id=con.target_user_id", "LEFT");
            }
            $total_records = $this->db->getValue("eudr_connections con", "count(*)");
        }

        // Get paginated records
        $records = [];
        
        if($type == "all") {
            // For 'all' type, use UNION with pagination
            $offset = ($page - 1) * $page_limit;
            
            $query = "
                SELECT 
                    con.*,
                    user.user_code,
                    user.phone, 
                    user.full_name, 
                    user.email, 
                    user.register_type,
                    'sent' as connection_direction
                FROM eudr_connections con
                LEFT JOIN eudr_users user ON user.user_id = con.target_user_id
                WHERE con.is_deleted = 0 AND user.phone IS NOT NULL 
                AND con.requester_user_id = {$user_id}
                {$statusCondition}
                {$accountTypeCondition}
                
                UNION ALL
                
                SELECT 
                    con.*,
                    user.user_code,
                    user.phone, 
                    user.full_name, 
                    user.email, 
                    user.register_type,
                    'received' as connection_direction
                FROM eudr_connections con
                LEFT JOIN eudr_users user ON user.user_id = con.requester_user_id
                WHERE con.is_deleted = 0 AND user.phone IS NOT NULL 
                AND con.target_user_id = {$user_id}
                {$statusCondition}
                {$accountTypeCondition}
                
                ORDER BY created_at DESC
                LIMIT {$page_limit} OFFSET {$offset}
            ";
            
            $records = $this->db->rawQuery($query);
        } else {
            // Original pagination logic for received/sent
            $this->db->pageLimit = $page_limit;
            $this->db->where("con.is_deleted", 0);
            if($status !== 'all') {
                $this->db->where("con.status", $status);
            }
            if (!empty($account_type)) {
                $roleFilterSQL = Utils::buildRoleFilterSQL('user.user_id', $account_type);
                if (!empty($roleFilterSQL)) {
                    $this->db->where($roleFilterSQL);
                }
            }
            if($type == "received") {
                $cols = "con.*,user.user_code,user.phone, user.full_name, user.email, user.register_type, 'received' as connection_direction";
                $this->db->where("user.phone", NULL, "IS NOT");
                $this->db->where("con.target_user_id", $user_id);
                $this->db->join("eudr_users user", "user.user_id=con.requester_user_id", "LEFT");
            } elseif ($type == "sent") {
                $cols = "con.*,user.user_code,user.phone, user.full_name, user.email, user.register_type, 'sent' as connection_direction";
                $this->db->where("user.phone", NULL, "IS NOT");
                $this->db->where("con.requester_user_id", $user_id);
                $this->db->join("eudr_users user", "user.user_id=con.target_user_id", "LEFT");
            }
            $this->db->orderBy("con.created_at", "DESC");
            $records = $this->db->arraybuilder()->paginate("eudr_connections con", $page, $cols);
        }

        // Calculate total pages for 'all' type
        $totalPages = ($type == "all") ? (int)ceil($total_records / $page_limit) : $this->db->totalPages;
       
        $return_data = [
            "current_page" => $page,
            "total_pages" => $totalPages,
            "total_records" => $total_records,
            "page_limit" => $page_limit,
            "records" => $records ?? [],
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function findConnectionOfCode(string $code): ?Connection
    {
        $this->db->where("con.connection_code", $code);
        //$this->db->where("con.is_deleted", 0);
        $connection = $this->db->getOne("eudr_connections con");
        if (empty($connection)) {
            return null;
        }
        return new Connection($connection['connection_id'], $connection);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "conn-".date("ymd").'-'.Utils::generateRandomString(8);
            $connection = $this->findConnectionOfCode($code);
            if (!$connection) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function searchUserOfPhone(string $phone, int $current_user_id=0): array
    {
        $this->db->where("phone", $phone);
        $this->db->where("is_approved", 1);
        $this->db->where("deleted_by", 0);
        // Multi-role: filter users with valid connectable roles
        $this->db->where("(user_id IN (SELECT ur.user_id FROM eudr_user_roles ur JOIN eudr_roles r ON r.role_id = ur.role_id WHERE r.name IN ('farmer','purchaser','transport','factory','sales','inspector')) OR register_type IN ('farmer','purchaser','trader','inspector','company'))");
        if ($current_user_id > 0) {
            $this->db->where ('user_id', $current_user_id, "!=");
        }
        $data_user = $this->db->getOne("eudr_users", "user_id, user_code, full_name, email, phone, register_type, created_at");
        if (empty($data_user)) {
            return [];
        }
        return $data_user;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionRequest(array $data): int|array
    {
        $requester_user_id = $data['requester_user_id'] ?? 0;
        $target_user_id = $data['target_user_id'] ?? 0;
        // delete old connection between the two users
        $this->db->where("( (requester_user_id = ? AND target_user_id = ?) OR (requester_user_id = ? AND target_user_id = ?) )", Array($requester_user_id, $target_user_id, $target_user_id, $requester_user_id));
        $this->db->where("status", Array('cancelled','rejected', 'blocked'), 'IN');
        $this->db->update("eudr_connections", ['is_deleted' => 1]);
        // create new connection
        $data['is_deleted'] = 0;
        $data['connection_code'] = $this->generateCode();
        
        $connection_id = $this->db->insert("eudr_connections", $data);
        if (empty($connection_id)) {
            return [];
        }

        $this->db->where("connection_id", $connection_id);
        $connection_item = $this->db->getOne("eudr_connections");

        if (empty($connection_item)) {
            throw new ConnectionNotFoundException();
        }

        return $connection_item;
    }

    /**
     * {@inheritdoc}
     */
    public function findConnectionBetweenUsers($requester_user_id, $target_user_id, $status = ""): array
    {
        /*
        $connection_request = $this->db->rawQuery("
            SELECT connection_request_id AS id, 'request' AS source, status
            FROM eudr_connection_requests
            WHERE (
                (user_request_id = ? AND user_target_id = ?)
                OR (user_request_id = ? AND user_target_id = ?)
            )
            AND (status = 'pending')

            UNION

            SELECT connection_id AS id, 'connection' AS source, status
            FROM eudr_connections
            WHERE (
                (user_id = ? AND connected_user_id = ?)
                OR (user_id = ? AND connected_user_id = ?)
            )
            AND status = 'active'
        ", [
            $user_request_id, $user_target_id, $user_target_id, $user_request_id,
            $user_request_id, $user_target_id, $user_target_id, $user_request_id
        ]);
        */

        $this->db->where("( (requester_user_id = ? AND target_user_id = ?) OR (requester_user_id = ? AND target_user_id = ?) )", Array($requester_user_id, $target_user_id, $target_user_id, $requester_user_id));
        if(!empty($status)) {
            $this->db->where("status", $status);
        } else {
            $this->db->where('status', Array('pending','accepted'), 'IN');
        }
        $this->db->where("is_deleted", 0);
        $data_connection = $this->db->getOne("eudr_connections");
        
        if(empty($data_connection)) {
            return [];
        }
        return $data_connection;
    }
    
    /**
     * {@inheritdoc}
     */
    public function cancelConnectionRequest(int $connection_id, int $requester_user_id): bool
    {
        $this->db->where("connection_id", $connection_id);
        $this->db->where("requester_user_id", $requester_user_id);
        $this->db->where("status", 'pending');
        $this->db->where("is_deleted", 0);
        $data_request = $this->db->getOne("eudr_connections");
        if (empty($data_request)) {
            return false;
        }

        // Update status to cancelled
        $data_update = [
            "status" => "cancelled",
            "updated_at" => date("Y-m-d H:i:s", time()),
            "updated_by" => $requester_user_id,
        ];
        $this->db->where("is_deleted", 0);
        $this->db->where("connection_id", $connection_id);
        $this->db->where("requester_user_id", $requester_user_id);
        $updated = $this->db->update("eudr_connections", $data_update);
        if (empty($updated)) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function respondConnectionRequest(int $connection_id, int $target_user_id, array $data_update): bool
    {
        $action = $data_update['action'] ?? '';
        $rejection_reason = $data_update['rejection_reason'] ?? '';

        $this->db->where("is_deleted", 0);
        $this->db->where("connection_id", $connection_id);
        $this->db->where("target_user_id", $target_user_id);
        $this->db->where("status", 'pending');
        $data_request = $this->db->getOne("eudr_connections");
        if (empty($data_request)) {
            return false;
        }

        // Update status to accepted or rejected
        $data_update = [
            "updated_at" => date("Y-m-d H:i:s", time()),
            "updated_by" => $target_user_id,
            "responded_at" => date("Y-m-d H:i:s", time()),
        ];
        if ($action === 'accept') {
            $data_update['status'] = 'accepted';
            
        }
        if ($action === 'reject') {
            $data_update['status'] = 'rejected';
            $data_update['rejection_reason'] = $rejection_reason;
        }

        $this->db->where("connection_id", $connection_id);
        $this->db->where("target_user_id", $target_user_id);
        $updated = $this->db->update("eudr_connections", $data_update);
        if (empty($updated)) {
            return false;
        }
       
       return true;
   }

    /**
     * {@inheritdoc}
     */
   public function updateConnectionStatus(int $connection_id, int $user_id, string $action): bool
   {
        $this->db->where("connection_id", $connection_id);
        $this->db->where("(requester_user_id = ? OR target_user_id = ?)", [$user_id, $user_id]);
        $this->db->where("status", 'accepted');
        $this->db->where("is_deleted", 0);
        $data_connection = $this->db->getOne("eudr_connections");
        if (empty($data_connection)) {
            return false;
        }

        if (in_array($action, ['remove', 'block'])) {
            // Update status to removed or blocked
            $data_update = [
                "status" => ($action === 'remove') ? "removed" : "blocked",
                "updated_by" => $user_id,
                "updated_at" => date("Y-m-d H:i:s", time()),
            ];
            $this->db->where("is_deleted", 0);
            $this->db->where("connection_id", $connection_id);
            $this->db->where("(requester_user_id = ? OR target_user_id = ?)", [$user_id, $user_id]);
            $updated = $this->db->update("eudr_connections", $data_update);
            if (empty($updated)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
   }

}
