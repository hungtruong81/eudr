<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\RubberBlock;

use App\Domain\RubberBlock\RubberBlock;
use App\Domain\RubberBlock\RubberBlockNotFoundException;
use App\Domain\RubberBlock\RubberBlockRepository;
use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;

class InDatabaseRubberBlockRepository implements RubberBlockRepository
{
    /**
     * @var \MysqliDb
     */
    private $db;

    /**
     * @var CurrentUserContext
     */
    private $currentUser;

    /**
     * InDatabaseRubberBlockRepository constructor.
     *
     * @param \MysqliDb $db
     * @param CurrentUserContext $currentUserContext
     */
    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db = $db;
        $this->currentUser = $currentUserContext;
    }

    /**
     * Hydrate a RubberBlock entity from a database row.
     */
    private function hydrateRubberBlock(array $data): RubberBlock
    {
        return new RubberBlock((int)$data['rubber_block_id'], $data);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $production_order_id = $params['production_order_id'] ?? 0;
        $product_type_id = $params['product_type_id'] ?? 0;
        $grade = $params['grade'] ?? '';
        $status = $params['status'] ?? 'all';
        $production_date_from = $params['production_date_from'] ?? null;
        $production_date_to = $params['production_date_to'] ?? null;

        // Count total records
        if (!empty($search)) {
            $this->db->where('(rb.rubber_block_code LIKE ? OR rb.grade LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($production_order_id)) {
            $this->db->where('rb.production_order_id', $production_order_id);
        }
        if (!empty($product_type_id)) {
            $this->db->where('rb.product_type_id', $product_type_id);
        }
        if (!empty($grade)) {
            $this->db->where('rb.grade', $grade);
        }
        if ($status !== 'all') {
            $this->db->where('rb.status', $status);
        }
        if (!empty($production_date_from)) {
            $this->db->where('rb.production_date', $production_date_from, '>=');
        }
        if (!empty($production_date_to)) {
            $this->db->where('rb.production_date', $production_date_to, '<=');
        }
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = rb.product_type_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = rb.production_order_id', 'LEFT');
        $total_records = (int)$this->db->getValue('eudr_production_rubber_blocks rb', 'count(*)');

        // Set pagination
        $this->db->pageLimit = $page_limit;

        if (!empty($search)) {
            $this->db->where('(rb.rubber_block_code LIKE ? OR rb.grade LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($production_order_id)) {
            $this->db->where('rb.production_order_id', $production_order_id);
        }
        if (!empty($product_type_id)) {
            $this->db->where('rb.product_type_id', $product_type_id);
        }
        if (!empty($grade)) {
            $this->db->where('rb.grade', $grade);
        }
        if ($status !== 'all') {
            $this->db->where('rb.status', $status);
        }
        if (!empty($production_date_from)) {
            $this->db->where('rb.production_date', $production_date_from, '>=');
        }
        if (!empty($production_date_to)) {
            $this->db->where('rb.production_date', $production_date_to, '<=');
        }

        $cols = 'rb.*, pt.product_type_name, pt.product_type_code, po.production_order_name, po.production_order_code';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('rb.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('rb.rubber_block_id', 'DESC');
        }
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = rb.product_type_id', 'LEFT');
        $this->db->join('eudr_production_orders po', 'po.production_order_id = rb.production_order_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_production_rubber_blocks rb', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = $this->hydrateRubberBlock($item);
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
    public function findRubberBlockOfId(int $rubber_block_id): ?RubberBlock
    {
        $this->db->where('rubber_block_id', $rubber_block_id);
        $row = $this->db->getOne('eudr_production_rubber_blocks');
        if (empty($row)) {
            return null;
        }
        return $this->hydrateRubberBlock($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findRubberBlockOfCode(string $code): ?RubberBlock
    {
        $this->db->where('rubber_block_code', $code);
        $row = $this->db->getOne('eudr_production_rubber_blocks');
        if (empty($row)) {
            return null;
        }
        return $this->hydrateRubberBlock($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findRubberBlocksByProductionOrderId(int $production_order_id): array
    {
        $this->db->where('rb.production_order_id', $production_order_id);
        $this->db->orderBy('rb.rubber_block_id', 'ASC');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = rb.product_type_id', 'LEFT');
        $rows = $this->db->arraybuilder()->get('eudr_production_rubber_blocks rb', null, 'rb.*, pt.product_type_name, pt.product_type_code');

        $items = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $items[] = $this->hydrateRubberBlock($row);
            }
        }
        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function createRubberBlock(array $data): ?RubberBlock
    {
        $this->db->insert('eudr_production_rubber_blocks', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }
        $id = $this->db->getInsertId();
        return $this->findRubberBlockOfId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function createRubberBlocks(array $items): array
    {
        $created = [];
        foreach ($items as $item) {
            $block = $this->createRubberBlock($item);
            if ($block !== null) {
                $created[] = $block;
            }
        }
        return $created;
    }

    /**
     * {@inheritdoc}
     */
    public function updateRubberBlock(int $rubber_block_id, array $data_update): RubberBlock
    {
        $this->db->where('rubber_block_id', $rubber_block_id);
        $this->db->update('eudr_production_rubber_blocks', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new RubberBlockNotFoundException("Rubber Block not found with ID: $rubber_block_id");
        }
        return $this->findRubberBlockOfId($rubber_block_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRubberBlocksStatus(array $rubber_block_ids, string $status): void
    {
        if (empty($rubber_block_ids)) {
            return;
        }
        $this->db->where('rubber_block_id', $rubber_block_ids, 'IN');
        $this->db->update('eudr_production_rubber_blocks', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s', time()),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "rubb-" . date("ymd") . '-' . Utils::generateRandomString(8);
            $existing = $this->findRubberBlockOfCode($code);
            if (!$existing) {
                break;
            }
        }
        return $code;
    }
}
