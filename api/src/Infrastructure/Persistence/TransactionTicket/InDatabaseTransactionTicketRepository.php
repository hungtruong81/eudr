<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\TransactionTicket;

use App\Domain\TransactionTicket\TransactionTicket;
use App\Domain\TransactionTicket\TransactionTicketNotFoundException;
use App\Domain\TransactionTicket\TransactionTicketRepository;
use App\Application\Utility\Utils;
use App\Application\Utility\CurrentUserContext;

class InDatabaseTransactionTicketRepository implements TransactionTicketRepository
{
    /**
     * @var MysqliDb
     */
    private $db;
    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseTransactionTicketRepository constructor.
     *
     * @param MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = []): array
    {
        $transaction_ticket_type = $params['transaction_ticket_type'] ?? '';
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 100;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? 'all';
        $start_date = $params['start_date'] ?? null;
        $end_date = $params['end_date'] ?? null;
        $contract_code = $params['contract_code'] ?? null;
        $member_user_id = $params['member_user_id'] ?? 0;
        $account_type = $params['account_type'] ?? null;
        $target_user_id = $params['target_user_id'] ?? 0;
        $sales_source = $params['sales_source'] ?? null;
        $scope = $params['scope'] ?? '';

        // Count total records
        $total_records = 0;

        if ($status !== 'all') {
            $this->db->where("tt.status", $status);
        }
        if (!empty($start_date)) {
            $this->db->where("DATE(tt.created_at)", $start_date, ">=");
        }
        if (!empty($end_date)) {
            $this->db->where("DATE(tt.created_at)", $end_date, "<=");
        }
        if (!empty($transaction_ticket_type)) {
            if ($transaction_ticket_type === 'purchase') {
                if(!empty($account_type)) {
                    $roleFilter = Utils::buildRoleFilterSQL('tt.seller_user_id', $account_type);
                    if (!empty($roleFilter)) {
                        $this->db->where($roleFilter);
                    }
                }
                if(!empty($target_user_id)) {
                    $this->db->where("tt.seller_user_id", $target_user_id);
                }
                // Công ty 
                if($scope === 'own') {
                    $this->db->where("tt.buyer_company_id", $this->currentUser->getCompanyId());
                    // Lọc theo thành viên công ty (nếu có)
                    if (!empty($member_user_id)) {
                        $this->db->where("tt.buyer_user_id", $member_user_id);
                    }
                }
                // Người dùng / thành viên của công ty
                if($scope === 'self') {
                    $this->db->where("tt.buyer_user_id", $this->currentUser->getUserId());
                }
                // Admin
                if($scope === 'all') {}
            } elseif ($transaction_ticket_type === 'sale') {
                if(!empty($account_type)) {
                    $roleFilter = Utils::buildRoleFilterSQL('tt.buyer_user_id', $account_type);
                    if (!empty($roleFilter)) {
                        $this->db->where($roleFilter);
                    }
                }
                if(!empty($target_user_id)) {
                    $this->db->where("tt.buyer_user_id", $target_user_id);
                }
                // Công ty
                if($scope === 'own') {
                    $this->db->where("tt.seller_company_id", $this->currentUser->getCompanyId());
                    // Lọc theo thành viên công ty (nếu có)
                    if (!empty($member_user_id)) {
                        $this->db->where("tt.seller_user_id", $member_user_id);
                    }
                }
                // Người dùng / thành viên của công ty
                if($scope === 'self') {
                    $this->db->where("tt.seller_user_id", $this->currentUser->getUserId());
                }
                // Admin
                if($scope === 'all') {}
            }
        }
        if (!empty($contract_code)) {
            $this->db->where("tt.contract_code", $contract_code);
        }
        if (!empty($search)) {
            $this->db->where("(tt.contract_code LIKE '%".$search."%' OR tt.seller_phone LIKE '%".$search."%' OR tt.buyer_phone LIKE '%".$search."%')");
        }
        // Filter by sales_source (land or ticket)
        if (!empty($sales_source)) {
            if ($sales_source === 'land') {
                $this->db->where("EXISTS (SELECT 1 FROM eudr_transaction_ticket_land_links tll WHERE tll.transaction_ticket_id = tt.transaction_ticket_id)");
            } elseif ($sales_source === 'ticket') {
                $this->db->where("EXISTS (SELECT 1 FROM eudr_transaction_ticket_sale_purchase_links spl WHERE spl.sale_ticket_id = tt.transaction_ticket_id)");
            }
        }
        $total_records = $this->db->getValue("eudr_transaction_tickets tt", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        // Subquery để tính số lần 1 purchase_ticket_id được dùng
        $usage_subquery = "(SELECT purchase_ticket_id, COUNT(*) as usage_count 
                        FROM eudr_transaction_ticket_sale_purchase_links 
                        GROUP BY purchase_ticket_id) u";

        // Lấy cột
        $cols = "
        tt.*,
        comp_buyer.short_name as buyer_company_short_name, 
        comp_seller.short_name as seller_company_short_name, 
        u.usage_count";

        if ($status !== 'all') {
            $this->db->where("tt.status", $status);
        }
        if (!empty($start_date)) {
            $this->db->where("DATE(tt.created_at)", $start_date, ">=");
        }
        if (!empty($end_date)) {
            $this->db->where("DATE(tt.created_at)", $end_date, "<=");
        }
        if (!empty($transaction_ticket_type)) {
            if ($transaction_ticket_type === 'purchase') {
                if(!empty($account_type)) {
                    $roleFilter = Utils::buildRoleFilterSQL('tt.seller_user_id', $account_type);
                    if (!empty($roleFilter)) {
                        $this->db->where($roleFilter);
                    }
                }
                if(!empty($target_user_id)) {
                    $this->db->where("tt.seller_user_id", $target_user_id);
                }
                // Công ty
                if($scope === 'own') {
                    $this->db->where("tt.buyer_company_id", $this->currentUser->getCompanyId());
                    // Lọc theo thành viên công ty (nếu có)
                    if (!empty($member_user_id)) {
                        $this->db->where("tt.buyer_user_id", $member_user_id);
                    }
                }
                // Người dùng / thành viên của công ty
                if($scope === 'self') {
                    $this->db->where("tt.buyer_user_id", $this->currentUser->getUserId());
                }
                // Admin
                if($scope === 'all') {}
                $this->db->join("eudr_users user", "user.user_id=tt.seller_user_id", "LEFT");
            } elseif ($transaction_ticket_type === 'sale') {
                if(!empty($account_type)) {
                    $roleFilter = Utils::buildRoleFilterSQL('tt.buyer_user_id', $account_type);
                    if (!empty($roleFilter)) {
                        $this->db->where($roleFilter);
                    }
                }
                if(!empty($target_user_id)) {
                    $this->db->where("tt.buyer_user_id", $target_user_id);
                }
                // Công ty
                if($scope === 'own') {
                    $this->db->where("tt.seller_company_id", $this->currentUser->getCompanyId());
                    // Lọc theo thành viên công ty (nếu có)
                    if (!empty($member_user_id)) {
                        $this->db->where("tt.seller_user_id", $member_user_id);
                    }
                }
                // Người dùng / thành viên của công ty
                if($scope === 'self') {
                    $this->db->where("tt.seller_user_id", $this->currentUser->getUserId());
                }
                // Admin
                if($scope === 'all') {}
                $this->db->join("eudr_users user", "user.user_id=tt.buyer_user_id", "LEFT");
            }
        }
        if (!empty($contract_code)) {
            $this->db->where("tt.contract_code", $contract_code);
        }
        if (!empty($search)) {
            $this->db->where("(tt.contract_code LIKE '%".$search."%' OR tt.seller_phone LIKE '%".$search."%' OR tt.buyer_phone LIKE '%".$search."%')");
        }
        // Filter by sales_source (land or ticket)
        if (!empty($sales_source)) {
            if ($sales_source === 'land') {
                $this->db->where("EXISTS (SELECT 1 FROM eudr_transaction_ticket_land_links tll WHERE tll.transaction_ticket_id = tt.transaction_ticket_id)");
            } elseif ($sales_source === 'ticket') {
                $this->db->where("EXISTS (SELECT 1 FROM eudr_transaction_ticket_sale_purchase_links spl WHERE spl.sale_ticket_id = tt.transaction_ticket_id)");
            }
        }
        $this->db->orderBy("tt.transaction_ticket_id", "DESC");
        $this->db->join($usage_subquery, "u.purchase_ticket_id = tt.transaction_ticket_id", "LEFT");
        $this->db->join("eudr_companies comp_buyer", "comp_buyer.company_id = tt.buyer_company_id", "LEFT");
        $this->db->join("eudr_companies comp_seller", "comp_seller.company_id = tt.seller_company_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_transaction_tickets tt", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new TransactionTicket($item['transaction_ticket_id'], $item);
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];

        return $return_data;
    }

    /**
     * {@inheritdoc}
     */
    public function findTransactionTicketOfCode(string $code): ?TransactionTicket
    {
        $this->db->where("tt.transaction_ticket_code", $code);
        $this->db->where("tt.deleted_by", 0);
        $this->db->join("eudr_companies comp_buyer", "comp_buyer.company_id = tt.buyer_company_id", "LEFT");
        $this->db->join("eudr_companies comp_seller", "comp_seller.company_id = tt.seller_company_id", "LEFT");
        $ticket = $this->db->getOne("eudr_transaction_tickets tt",
            "tt.*, 
            comp_buyer.short_name as buyer_company_short_name, 
            comp_seller.short_name as seller_company_short_name");
        if (empty($ticket)) {
            return null;
        }
        return new TransactionTicket($ticket['transaction_ticket_id'], $ticket);
    }

    /**
     * {@inheritdoc}
     */
    public function findTransactionTicketOfContractCode(string $contract_code): ?TransactionTicket
    {
        $this->db->where("tt.contract_code", $contract_code);
        $this->db->where("tt.deleted_by", 0);
        $this->db->join("eudr_companies comp_buyer", "comp_buyer.company_id = tt.buyer_company_id", "LEFT");
        $this->db->join("eudr_companies comp_seller", "comp_seller.company_id = tt.seller_company_id", "LEFT");
        $ticket = $this->db->getOne(
            "eudr_transaction_tickets tt",
            "tt.*, 
            comp_buyer.short_name as buyer_company_short_name, 
            comp_seller.short_name as seller_company_short_name"
        );
        
        if (empty($ticket)) {
            return null;
        }
        return new TransactionTicket($ticket['transaction_ticket_id'], $ticket);
    }


    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "ttkt-" . date("ymd") . '-' . Utils::generateRandomString(8);
            $ticket = $this->findTransactionTicketOfCode($code);
            if (!$ticket) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function generateContractCode(): string
    {
        $this->db->startTransaction();
        try {
            $today = date('Y-m-d');
            $prefix = 'HD' . date('Ymd'); // HD20250924

            // 1. Insert hoặc update counter
            $sql = "INSERT INTO eudr_general_counters (counter_date, counter) 
                    VALUES (?, 1) 
                    ON DUPLICATE KEY UPDATE counter = counter + 1";
            $this->db->rawQuery($sql, [$today]);

            // 2. Lấy lại giá trị counter
            $this->db->where('counter_date', $today);
            $row = $this->db->getOne('eudr_general_counters', ['counter']);
            if (!$row) {
                $this->db->rollback();
                throw new TransactionTicketNotFoundException("Transaction ticket not found with counter_date: $today");
            }
            $counter = (int)$row['counter'];

            // 3. Ghép mã hợp đồng: HDyyyyMMddNNN
            $contractCode = $prefix . str_pad((string)$counter, 3, '0', STR_PAD_LEFT);

            $this->db->commit();
            return $contractCode;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findTransactionTicketOfId(int $transaction_ticket_id): ?TransactionTicket
    {
        $this->db->where("tt.deleted_by", 0);
        $this->db->where("tt.transaction_ticket_id", $transaction_ticket_id);
        $record = $this->db->getOne("eudr_transaction_tickets tt");
        if (empty($record)) {
            return null;
        }
        return new TransactionTicket($record['transaction_ticket_id'], $record);
    }

    /**
     * {@inheritdoc}
     */
    public function createTransactionTicket(array $data): ?TransactionTicket
    {
        $plot_ids = $data['plot_ids'] ?? [];
        $purchase_ticket_ids = $data['purchase_ticket_ids'] ?? [];

        unset($data['plot_ids']);
        unset($data['purchase_ticket_ids']);

        $data['contract_code'] = $this->generateContractCode();

        $transaction_ticket_id = $this->db->insert("eudr_transaction_tickets", $data);
        if (empty($transaction_ticket_id)) {
            throw new \RuntimeException("Failed to create transaction ticket");
        }
        // Link transaction ticket to plots
        if (!empty($plot_ids) && is_array($plot_ids)) {
            foreach ($plot_ids as $plot_id) {
                $this->db->insert("eudr_transaction_ticket_land_links", [
                    "transaction_ticket_id" => $transaction_ticket_id,
                    "plot_id" => $plot_id,
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
            }
        }
        // Link transaction ticket if it's a sale transaction
        if (!empty($purchase_ticket_ids) && is_array($purchase_ticket_ids) && $data['transaction_ticket_type'] === 'sale') {
            foreach ($purchase_ticket_ids as $purchase_ticket_id) {
                $this->db->insert("eudr_transaction_ticket_sale_purchase_links", [
                    "sale_ticket_id" => $transaction_ticket_id,
                    "purchase_ticket_id" => $purchase_ticket_id,
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
            }
        }

        return $this->findTransactionTicketOfId($transaction_ticket_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateTransactionTicket(int $transaction_ticket_id, array $data_update): TransactionTicket
    {

        $plot_ids = $data_update['plot_ids'] ?? [];
        $purchase_ticket_ids = $data_update['purchase_ticket_ids'] ?? [];
        unset($data_update['plot_ids']);
        unset($data_update['purchase_ticket_ids']);

        $this->db->where("transaction_ticket_id", $transaction_ticket_id);
        $this->db->update("eudr_transaction_tickets", $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new TransactionTicketNotFoundException("Transaction ticket not found with ID: $transaction_ticket_id");
        }

        // link to land plots
        if (!empty($plot_ids) && is_array($plot_ids)) {
            // first, delete existing links
            $this->db->where("transaction_ticket_id", $transaction_ticket_id);
            $this->db->delete("eudr_transaction_ticket_land_links");
            // then, insert new links
            foreach ($plot_ids as $plot_id) {
                $this->db->insert("eudr_transaction_ticket_land_links", [
                    "transaction_ticket_id" => $transaction_ticket_id,
                    "plot_id" => $plot_id,
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
            }
        }
        // link to purchase tickets if it's a sale transaction
        if (!empty($purchase_ticket_ids) && is_array($purchase_ticket_ids)) {
            // first, delete existing links
            $this->db->where("sale_ticket_id", $transaction_ticket_id);
            $this->db->delete("eudr_transaction_ticket_sale_purchase_links");
            // then, insert new links
            foreach ($purchase_ticket_ids as $purchase_ticket_id) {
                $this->db->insert("eudr_transaction_ticket_sale_purchase_links", [
                    "sale_ticket_id" => $transaction_ticket_id,
                    "purchase_ticket_id" => $purchase_ticket_id,
                    "created_at" => date("Y-m-d H:i:s"),
                ]);
            }
        }

        return $this->findTransactionTicketOfId($transaction_ticket_id);
    }

    /**
     * {@inheritdoc}
     */
    public function findPurchaseTicketsByIds(array $ids, int $user_id): array
    {
        if (empty($ids)) {
            return [];
        }
        $this->db->where("tt.transaction_ticket_id", $ids, "IN");
        $this->db->where("tt.status", "completed");
        $this->db->where("tt.buyer_user_id", $user_id);
        $this->db->where("tt.deleted_by", 0);
        $records = $this->db->arraybuilder()->get("eudr_transaction_tickets tt");
        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new TransactionTicket($item['transaction_ticket_id'], $item);
            }
        }
        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function sumWeightOfTransactionTickets(array $ids): float 
    {
        if (empty($ids)) {
            return 0.0;
        }
        $this->db->where("tt.transaction_ticket_id", $ids, "IN");
        $this->db->where("tt.status", "completed");
        $this->db->where("tt.deleted_by", 0);
        $total_weight = $this->db->getValue("eudr_transaction_tickets tt", "SUM(tt.latex_weight + tt.scrap_rubber_weight)");
        return (float)$total_weight;
    }

    /**
     * {@inheritdoc}
     */
    public function findSaleTicketsByIds(array $ids, int $user_id): array
    {
        if (empty($ids)) {
            return [];
        }
        $this->db->where("tt.transaction_ticket_id", $ids, "IN");
        $this->db->where("tt.status", "completed");
        $this->db->where("tt.seller_user_id", $user_id);
        $this->db->where("tt.deleted_by", 0);
        $records = $this->db->arraybuilder()->get("eudr_transaction_tickets tt");
        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new TransactionTicket($item['transaction_ticket_id'], $item);
            }
        }
        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseTicketsBySaleTicket(int $sale_ticket_id, array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 100;
        $user_id = $params['user_id'] ?? 0;

        // Count total records
        $this->db->where("spl.sale_ticket_id", $sale_ticket_id);
        $total_records = $this->db->getValue("eudr_transaction_ticket_sale_purchase_links spl", "count(*)");

        // Set pagination
        $this->db->pageLimit = $page_limit;

        // Subquery để tính số lần 1 purchase_ticket_id được dùng
        $usage_subquery = "(SELECT purchase_ticket_id, COUNT(*) as usage_count 
                        FROM eudr_transaction_ticket_sale_purchase_links 
                        GROUP BY purchase_ticket_id) u";

        // Lấy cột
        $cols = "tt.*, u.usage_count";

        // Query
        $this->db->where("spl.sale_ticket_id", $sale_ticket_id);
        $this->db->join("eudr_transaction_tickets tt", "tt.transaction_ticket_id = spl.purchase_ticket_id", "LEFT");
        $this->db->join($usage_subquery, "u.purchase_ticket_id = spl.purchase_ticket_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_transaction_ticket_sale_purchase_links spl", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $ticket = new TransactionTicket($item['transaction_ticket_id'], $item);
                $items[] = $ticket;
            }
        }

        return [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function findPurchaseTicketsUnrouted($params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 100;
        $search = $params['search'] ?? '';
        $permission_status = $params['permission_status'] ?? '';
        $status = 'completed';
        $start_date = $params['start_date'] ?? null;
        $end_date = $params['end_date'] ?? null;
        $contract_code = $params['contract_code'] ?? null;
        $user_id = $params['user_id'] ?? 0;
        $account_type = $params['account_type'] ?? null;
        $target_user_id = $params['target_user_id'] ?? 0;

        // Count total records
        $total_records = 0;

        if ($status !== 'all') {
            $this->db->where("tt.status", $status);
        }
        if (!empty($start_date)) {
            $this->db->where("DATE(tt.created_at)", $start_date, ">=");
        }
        if (!empty($end_date)) {
            $this->db->where("DATE(tt.created_at)", $end_date, "<=");
        }
        if(!empty($account_type)) {
            $this->db->where("tt.seller_account_type", $account_type);
        }
        if(!empty($target_user_id)) {
            $this->db->where("tt.seller_user_id", $target_user_id);
        }
        $this->db->where("tt.buyer_user_id", $user_id);

        if (!empty($contract_code)) {
            $this->db->where("tt.contract_code", $contract_code);
        }
        if (!empty($search)) {
            $this->db->where("(tt.contract_code LIKE '%".$search."%' OR tt.seller_phone LIKE '%".$search."%')");
        }
        // Only count tickets that are not yet routed
        $this->db->where("NOT EXISTS (
            SELECT 1 FROM eudr_transportation_route_transaction_tickets rtt
            WHERE rtt.transaction_ticket_id = tt.transaction_ticket_id
        )");
        $total_records = $this->db->getValue("eudr_transaction_tickets tt", "count(*)");


        // Set pagination
        $this->db->pageLimit = $page_limit;

        // Subquery để tính số lần 1 purchase_ticket_id được dùng
        $usage_subquery = "(SELECT purchase_ticket_id, COUNT(*) as usage_count 
                        FROM eudr_transaction_ticket_sale_purchase_links 
                        GROUP BY purchase_ticket_id) u";

        // Lấy cột
        $cols = "tt.*, u.usage_count";

        if ($status !== 'all') {
            $this->db->where("tt.status", $status);
        }
        if (!empty($start_date)) {
            $this->db->where("DATE(tt.created_at)", $start_date, ">=");
        }
        if (!empty($end_date)) {
            $this->db->where("DATE(tt.created_at)", $end_date, "<=");
        }
        if(!empty($account_type)) {
            $this->db->where("tt.seller_account_type", $account_type);
        }
        if(!empty($target_user_id)) {
            $this->db->where("tt.seller_user_id", $target_user_id);
        }
        $this->db->where("tt.buyer_user_id", $user_id);
        if (!empty($contract_code)) {
            $this->db->where("tt.contract_code", $contract_code);
        }
        if (!empty($search)) {
            $this->db->where("(tt.contract_code LIKE '%".$search."%' OR tt.seller_phone LIKE '%".$search."%')");
        }
        // Only get tickets that are not yet routed
        $this->db->where("NOT EXISTS (
            SELECT 1 FROM eudr_transportation_route_transaction_tickets rtt
            WHERE rtt.transaction_ticket_id = tt.transaction_ticket_id
        )");
        $this->db->orderBy("tt.transaction_ticket_id", "DESC");
        $this->db->join("eudr_users user", "user.user_id=tt.seller_user_id", "LEFT");
        $this->db->join($usage_subquery, "u.purchase_ticket_id = tt.transaction_ticket_id", "LEFT");
        $records = $this->db->arraybuilder()->paginate("eudr_transaction_tickets tt", $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new TransactionTicket($item['transaction_ticket_id'], $item);
            }
        }

        $return_data = [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $items,
        ];

        return $return_data;
    }


}
