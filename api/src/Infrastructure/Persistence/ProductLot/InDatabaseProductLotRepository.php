<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ProductLot;

use App\Domain\ProductLot\ProductLot;
use App\Domain\ProductLot\ProductLotItem;
use App\Domain\ProductLot\ProductLotNotFoundException;
use App\Domain\ProductLot\ProductLotRepository;
use App\Application\Utility\CurrentUserContext;
use App\Application\Utility\Utils;

class InDatabaseProductLotRepository implements ProductLotRepository
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
     * InDatabaseProductLotRepository constructor.
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
     * Hydrate a ProductLot entity from a database row.
     */
    private function hydrateProductLot(array $data): ProductLot
    {
        return new ProductLot((int)$data['product_lot_id'], $data);
    }

    /**
     * Hydrate a ProductLotItem entity from a database row.
     */
    private function hydrateProductLotItem(array $data): ProductLotItem
    {
        return new ProductLotItem((int)$data['product_lot_item_id'], $data);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $search = $params['search'] ?? '';
        $factory_id = $params['factory_id'] ?? 0;
        $grade = $params['grade'] ?? '';
        $status = $params['status'] ?? 'all';
        $lot_type = $params['lot_type'] ?? '';
        $eudr_type = $params['eudr_type'] ?? 'all';
        $production_date_from = $params['production_date_from'] ?? null;
        $production_date_to = $params['production_date_to'] ?? null;
        $owner_company_id = isset($params['owner_company_id']) ? (int)$params['owner_company_id'] : 0;
        $owner_id = isset($params['owner_id']) ? (int)$params['owner_id'] : 0;
        $inventory_only = !empty($params['inventory_only']);

        // if ($inventory_only && $status === 'all') {
        //     $status = 'inventory';
        // }

        // Count total records
        if (!empty($search)) {
            $this->db->where('(pl.product_lot_code LIKE ? OR pl.grade LIKE ? OR pl.supplier_company_name LIKE ?)', ["%$search%", "%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where('pl.factory_id', $factory_id);
        }
        if (!empty($grade)) {
            $this->db->where('pl.grade', $grade);
        }
        if ($status !== 'all') {
            $this->db->where('pl.status', $status);
        }
        if (!empty($lot_type) && $lot_type !== 'all') {
            $this->db->where('pl.lot_type', $lot_type);
        }
        if (!empty($eudr_type) && $eudr_type !== 'all') {
            $this->db->where('pl.eudr_type', $eudr_type);
        }
        if (!empty($production_date_from)) {
            $this->db->where('pl.production_date_from', $production_date_from, '>=');
        }
        if (!empty($production_date_to)) {
            $this->db->where('pl.production_date_to', $production_date_to, '<=');
        }
        if (!empty($owner_company_id)) {
            $this->db->where('pl.owner_company_id', $owner_company_id);
        }
        if (!empty($owner_id)) {
            $this->db->where('pl.owner_id', $owner_id);
        }
        if ($inventory_only) {
            $this->db->where('pl.status', ['confirmed', 'shipped'], 'IN');
        }
        $this->db->where('pl.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pl.factory_id', 'LEFT');
        $total_records = (int)$this->db->getValue('eudr_production_product_lots pl', 'count(*)');

        // Set pagination
        $this->db->pageLimit = $page_limit;

        if (!empty($search)) {
            $this->db->where('(pl.product_lot_code LIKE ? OR pl.grade LIKE ? OR pl.supplier_company_name LIKE ?)', ["%$search%", "%$search%", "%$search%"]);
        }
        if (!empty($factory_id)) {
            $this->db->where('pl.factory_id', $factory_id);
        }
        if (!empty($grade)) {
            $this->db->where('pl.grade', $grade);
        }
        if ($status !== 'all') {
            $this->db->where('pl.status', $status);
        }
        if (!empty($lot_type) && $lot_type !== 'all') {
            $this->db->where('pl.lot_type', $lot_type);
        }
        if (!empty($eudr_type) && $eudr_type !== 'all') {
            $this->db->where('pl.eudr_type', $eudr_type);
        }
        if (!empty($production_date_from)) {
            $this->db->where('pl.production_date_from', $production_date_from, '>=');
        }
        if (!empty($production_date_to)) {
            $this->db->where('pl.production_date_to', $production_date_to, '<=');
        }
        if (!empty($owner_company_id)) {
            $this->db->where('pl.owner_company_id', $owner_company_id);
        }
        if (!empty($owner_id)) {
            $this->db->where('pl.owner_id', $owner_id);
        }
        if ($inventory_only) {
            $this->db->where('pl.status', ['confirmed', 'shipped'], 'IN');
        }
        $this->db->where('pl.deleted_by', 0);

        $cols = 'pl.*, f.factory_name, f.factory_code';

        if (!empty($params['order_by'])) {
            $this->db->orderBy('pl.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('pl.product_lot_id', 'DESC');
        }
        $this->db->join('eudr_factories f', 'f.factory_id = pl.factory_id', 'LEFT');
        $records = $this->db->arraybuilder()->paginate('eudr_production_product_lots pl', $page, $cols);

        $items = [];
        $lotIds = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $lotIds[] = (int)$item['product_lot_id'];
                $items[] = $this->hydrateProductLot($item);
            }
        }

        // Batch load items (rubber blocks) for all lots on this page
        $itemsByLotId = $this->getProductLotItemsByLotIds($lotIds);

        // Attach items to each lot record
        $lotRecords = [];
        foreach ($items as $lot) {
            $lotData = $lot->jsonSerialize();
            $lotId = (int)$lot->getId();
            $lotItems = $itemsByLotId[$lotId] ?? [];
            $lotData['items'] = array_map(fn($item) => $item->jsonSerialize(), $lotItems);
            $lotRecords[] = $lotData;
        }

        return [
            "current_page" => $page,
            "total_pages" => $this->db->totalPages,
            "total_records" => $total_records,
            "page_limit" => $this->db->pageLimit,
            "records" => $lotRecords,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findProductLotOfId(int $product_lot_id): ?ProductLot
    {
        $this->db->where('pl.product_lot_id', $product_lot_id);
        $this->db->where('pl.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pl.factory_id', 'LEFT');
        $row = $this->db->getOne('eudr_production_product_lots pl', 'pl.*, f.factory_name, f.factory_code');
        if (empty($row)) {
            return null;
        }
        return $this->hydrateWithExternalData($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findProductLotOfCode(string $code): ?ProductLot
    {
        $this->db->where('pl.product_lot_code', $code);
        $this->db->where('pl.deleted_by', 0);
        $this->db->join('eudr_factories f', 'f.factory_id = pl.factory_id', 'LEFT');
        $row = $this->db->getOne('eudr_production_product_lots pl', 'pl.*, f.factory_name, f.factory_code');
        if (empty($row)) {
            return null;
        }
        return $this->hydrateWithExternalData($row);
    }

    /**
     * Hydrate a product lot row, loading sub-resources for external lots.
     * Branches on eudr_type: 'eudr' loads lands + transport; 'non_eudr' loads items + attachments.
     */
    private function hydrateWithExternalData(array $row): ProductLot
    {
        if (($row['lot_type'] ?? 'internal') === 'external') {
            $id = (int)$row['product_lot_id'];
            if (($row['eudr_type'] ?? 'eudr') === 'non_eudr') {
                $row['non_eudr_items'] = $this->findNonEudrItemsByProductLotId($id);
                $row['attachments'] = $this->findAttachmentsByProductLotId($id);
            } else {
                $row['lands'] = $this->findLandsByProductLotId($id);
                $row['transport'] = $this->findTransportByProductLotId($id);
            }
        }
        return $this->hydrateProductLot($row);
    }

    /**
     * {@inheritdoc}
     */
    public function createProductLot(array $data): ?ProductLot
    {
        $this->db->insert('eudr_production_product_lots', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }
        $id = $this->db->getInsertId();
        return $this->findProductLotOfId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductLot(int $product_lot_id, array $data_update): ProductLot
    {
        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->update('eudr_production_product_lots', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new ProductLotNotFoundException("Product Lot not found with ID: $product_lot_id");
        }
        return $this->findProductLotOfId($product_lot_id);
    }

    /**
     * {@inheritdoc}
     */
    public function addProductLotItems(int $product_lot_id, array $items): void
    {
        foreach ($items as $item) {
            $item['product_lot_id'] = $product_lot_id;
            $this->db->insert('eudr_production_product_lot_items', $item);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProductLotItems(int $product_lot_id): array
    {
        $this->db->where('pli.product_lot_id', $product_lot_id);
        $this->db->orderBy('pli.product_lot_item_id', 'ASC');
        $this->db->join('eudr_production_rubber_blocks rb', 'rb.rubber_block_id = pli.rubber_block_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = rb.product_type_id', 'LEFT');
        $rows = $this->db->arraybuilder()->get('eudr_production_product_lot_items pli', null, 'pli.*, rb.rubber_block_code, rb.product_type_id, rb.production_order_id, pt.product_type_name, pt.product_type_code');

        $items = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $items[] = $this->hydrateProductLotItem($row);
            }
        }
        return $items;
    }

    /**
     * Batch load product lot items for multiple lot IDs (avoids N+1 queries).
     *
     * @param int[] $lotIds
     * @return array<int, ProductLotItem[]> Keyed by product_lot_id
     */
    private function getProductLotItemsByLotIds(array $lotIds): array
    {
        if (empty($lotIds)) {
            return [];
        }

        $this->db->where('pli.product_lot_id', $lotIds, 'IN');
        $this->db->orderBy('pli.product_lot_id', 'ASC');
        $this->db->orderBy('pli.product_lot_item_id', 'ASC');
        $this->db->join('eudr_production_rubber_blocks rb', 'rb.rubber_block_id = pli.rubber_block_id', 'LEFT');
        $this->db->join('eudr_production_product_types pt', 'pt.product_type_id = rb.product_type_id', 'LEFT');
        $rows = $this->db->arraybuilder()->get('eudr_production_product_lot_items pli', null, 'pli.*, rb.rubber_block_code, rb.product_type_id, rb.production_order_id, pt.product_type_name, pt.product_type_code');

        $result = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $lotId = (int)$row['product_lot_id'];
                if (!isset($result[$lotId])) {
                    $result[$lotId] = [];
                }
                $result[$lotId][] = $this->hydrateProductLotItem($row);
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createProductLotWithItems(array $data, array $rubber_block_ids): ?ProductLot
    {
        $this->db->startTransaction();

        // Create product lot
        $this->db->insert('eudr_production_product_lots', $data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $product_lot_id = $this->db->getInsertId();
        $now = date('Y-m-d H:i:s', time());
        $total_weight = 0;
        $min_date = null;
        $max_date = null;
        $grade = '';

        // Get rubber blocks and create lot items
        foreach ($rubber_block_ids as $rubber_block_id) {
            $this->db->where('rubber_block_id', (int)$rubber_block_id);
            $block = $this->db->getOne('eudr_production_rubber_blocks');

            if (empty($block)) {
                $this->db->rollback();
                return null;
            }

            if ($block['status'] !== 'available') {
                $this->db->rollback();
                return null;
            }

            // Create lot item with snapshot data
            $lotItemData = [
                'product_lot_id' => $product_lot_id,
                'rubber_block_id' => (int)$rubber_block_id,
                'weight_snapshot' => $block['weight'],
                'grade_snapshot' => $block['grade'] ?? '',
                'created_at' => $now,
            ];

            $this->db->insert('eudr_production_product_lot_items', $lotItemData);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            // Update rubber block status to 'allocated'
            $this->db->where('rubber_block_id', (int)$rubber_block_id);
            $this->db->update('eudr_production_rubber_blocks', [
                'status' => 'allocated',
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $total_weight += (float)$block['weight'];

            // Track min/max production_date and grade
            $block_date = $block['production_date'] ?? null;
            if ($block_date !== null) {
                if ($min_date === null || $block_date < $min_date) {
                    $min_date = $block_date;
                }
                if ($max_date === null || $block_date > $max_date) {
                    $max_date = $block_date;
                }
            }
            if (!empty($block['grade'])) {
                $grade = $block['grade'];
            }
        }

        // Update product lot totals and computed fields
        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->update('eudr_production_product_lots', [
            'total_blocks' => count($rubber_block_ids),
            'total_weight' => $total_weight,
            'production_date_from' => $min_date,
            'production_date_to' => $max_date,
            'grade' => $grade,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return $this->findProductLotOfId($product_lot_id);
    }

    /**
     * {@inheritdoc}
     */
    public function removeProductLotItems(int $product_lot_id): void
    {
        // Get current items to release rubber blocks
        $this->db->where('product_lot_id', $product_lot_id);
        $items = $this->db->get('eudr_production_product_lot_items');

        if (!empty($items)) {
            $rubber_block_ids = array_column($items, 'rubber_block_id');
            if (!empty($rubber_block_ids)) {
                $now = date('Y-m-d H:i:s', time());
                $this->db->where('rubber_block_id', $rubber_block_ids, 'IN');
                $this->db->update('eudr_production_rubber_blocks', [
                    'status' => 'available',
                    'updated_at' => $now,
                ]);
            }

            $this->db->where('product_lot_id', $product_lot_id);
            $this->db->delete('eudr_production_product_lot_items');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateProductLotWithItems(int $product_lot_id, array $data_update, array $rubber_block_ids): ?ProductLot
    {
        $this->db->startTransaction();

        // Remove old items and release rubber blocks
        $this->removeProductLotItems($product_lot_id);

        $now = date('Y-m-d H:i:s', time());
        $total_weight = 0;
        $min_date = null;
        $max_date = null;
        $grade = '';

        // Create new lot items from rubber block IDs
        foreach ($rubber_block_ids as $rubber_block_id) {
            $this->db->where('rubber_block_id', (int)$rubber_block_id);
            $block = $this->db->getOne('eudr_production_rubber_blocks');

            if (empty($block)) {
                $this->db->rollback();
                return null;
            }

            if ($block['status'] !== 'available') {
                $this->db->rollback();
                return null;
            }

            $lotItemData = [
                'product_lot_id' => $product_lot_id,
                'rubber_block_id' => (int)$rubber_block_id,
                'weight_snapshot' => $block['weight'],
                'grade_snapshot' => $block['grade'] ?? '',
                'created_at' => $now,
            ];

            $this->db->insert('eudr_production_product_lot_items', $lotItemData);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            // Update rubber block status to 'allocated'
            $this->db->where('rubber_block_id', (int)$rubber_block_id);
            $this->db->update('eudr_production_rubber_blocks', [
                'status' => 'allocated',
                'updated_at' => $now,
            ]);
            if ($this->db->getLastErrno() !== 0) {
                $this->db->rollback();
                return null;
            }

            $total_weight += (float)$block['weight'];

            // Track min/max production_date and grade
            $block_date = $block['production_date'] ?? null;
            if ($block_date !== null) {
                if ($min_date === null || $block_date < $min_date) {
                    $min_date = $block_date;
                }
                if ($max_date === null || $block_date > $max_date) {
                    $max_date = $block_date;
                }
            }
            if (!empty($block['grade'])) {
                $grade = $block['grade'];
            }
        }

        // Update product lot with new totals and computed fields
        $data_update['total_blocks'] = count($rubber_block_ids);
        $data_update['total_weight'] = $total_weight;
        $data_update['production_date_from'] = $min_date;
        $data_update['production_date_to'] = $max_date;
        $data_update['grade'] = $grade;

        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->update('eudr_production_product_lots', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }

        $this->db->commit();

        return $this->findProductLotOfId($product_lot_id);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "prdl-" . date("ymd") . '-' . Utils::generateRandomString(8);
            $existing = $this->findProductLotOfCode($code);
            if (!$existing) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function findProductLotsByIds(array $product_lot_ids): array
    {
        if (empty($product_lot_ids)) {
            return [];
        }
        $this->db->where('product_lot_id', $product_lot_ids, 'IN');
        $rows = $this->db->get('eudr_production_product_lots');
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateProductLot($row);
        }
        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function transferOwnership(array $product_lot_ids, int $owner_company_id, int $owner_id = 0): void
    {
        if (empty($product_lot_ids)) {
            return;
        }
        $this->db->where('product_lot_id', $product_lot_ids, 'IN');
        $updateData = [
            'owner_company_id' => $owner_company_id,
            'status' => 'shipped',
        ];
        if ($owner_id > 0) {
            $updateData['owner_id'] = $owner_id;
        }
        $this->db->update('eudr_production_product_lots', $updateData);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductLotSummary(array $params = []): array
    {
        $company_id = $params['company_id'] ?? null;
        $factory_id = $params['factory_id'] ?? null;
        $date_from = $params['date_from'] ?? null;
        $date_to = $params['date_to'] ?? null;

        // 1. Summary by status
        $conditions = [];
        $bindings = [];
        if (!empty($company_id)) {
            $conditions[] = 'owner_company_id = ?';
            $bindings[] = (int)$company_id;
        }
        if (!empty($factory_id)) {
            $conditions[] = 'factory_id = ?';
            $bindings[] = (int)$factory_id;
        }
        if (!empty($date_from)) {
            $conditions[] = 'production_date_from >= ?';
            $bindings[] = $date_from;
        }
        if (!empty($date_to)) {
            $conditions[] = 'production_date_to <= ?';
            $bindings[] = $date_to;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $statusSql = "SELECT 
                status, 
                COUNT(*) AS total_lots, 
                COALESCE(SUM(total_blocks), 0) AS total_blocks, 
                COALESCE(SUM(total_weight), 0) AS total_weight
            FROM eudr_production_product_lots
            {$whereClause}
            GROUP BY status";

        $statusRows = $this->db->rawQuery($statusSql, $bindings);

        $byStatus = [];
        $overallTotalLots = 0;
        $overallTotalBlocks = 0;
        $overallTotalWeight = 0.0;
        foreach ($statusRows as $row) {
            $byStatus[] = [
                'status' => $row['status'],
                'total_lots' => (int)$row['total_lots'],
                'total_blocks' => (int)$row['total_blocks'],
                'total_weight' => (float)$row['total_weight'],
            ];
            $overallTotalLots += (int)$row['total_lots'];
            $overallTotalBlocks += (int)$row['total_blocks'];
            $overallTotalWeight += (float)$row['total_weight'];
        }

        // 2. Summary by grade
        $gradeSql = "SELECT 
                grade, 
                status,
                COUNT(*) AS total_lots, 
                COALESCE(SUM(total_blocks), 0) AS total_blocks, 
                COALESCE(SUM(total_weight), 0) AS total_weight
            FROM eudr_production_product_lots
            {$whereClause}
            GROUP BY grade, status
            ORDER BY grade, status";

        $gradeRows = $this->db->rawQuery($gradeSql, $bindings);

        $byGrade = [];
        foreach ($gradeRows as $row) {
            $byGrade[] = [
                'grade' => $row['grade'] ?: 'N/A',
                'status' => $row['status'],
                'total_lots' => (int)$row['total_lots'],
                'total_blocks' => (int)$row['total_blocks'],
                'total_weight' => (float)$row['total_weight'],
            ];
        }

        // 3. Monthly trend (last 12 months)
        $monthlySql = "SELECT 
                DATE_FORMAT(confirmed_at, '%Y-%m') AS month,
                status,
                COUNT(*) AS total_lots,
                COALESCE(SUM(total_blocks), 0) AS total_blocks,
                COALESCE(SUM(total_weight), 0) AS total_weight
            FROM eudr_production_product_lots
            {$whereClause}
            " . (!empty($conditions) ? ' AND ' : ' WHERE ') . "confirmed_at IS NOT NULL
                AND confirmed_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month, status
            ORDER BY month ASC, status";

        $monthlyRows = $this->db->rawQuery($monthlySql, $bindings);

        $byMonth = [];
        foreach ($monthlyRows as $row) {
            $byMonth[] = [
                'month' => $row['month'],
                'status' => $row['status'],
                'total_lots' => (int)$row['total_lots'],
                'total_blocks' => (int)$row['total_blocks'],
                'total_weight' => (float)$row['total_weight'],
            ];
        }

        return [
            'overview' => [
                'total_lots' => $overallTotalLots,
                'total_blocks' => $overallTotalBlocks,
                'total_weight' => $overallTotalWeight,
            ],
            'by_status' => $byStatus,
            'by_grade' => $byGrade,
            'by_month' => $byMonth,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Trace product lot back through the supply chain to find all related farms.
     * For internal lots:
     *   Chain: Product Lot → Rubber Blocks → Production Orders → Raw Material Releases
     *     → Raw Material Tanks (via history) → Transportation Routes → Transaction Tickets → Farms
     * For external lots:
     *   Chain: Product Lot → product_lot_lands → Farms (direct link)
     */
    public function traceProductLotToFarms(int $product_lot_id): array
    {
        // Determine lot type
        $this->db->where('product_lot_id', $product_lot_id);
        $lotRow = $this->db->getOne('eudr_production_product_lots', 'lot_type');
        if (empty($lotRow)) {
            return [];
        }

        $lotType = $lotRow['lot_type'] ?? 'internal';

        if ($lotType === 'external') {
            return $this->traceExternalProductLotToFarms($product_lot_id);
        }

        return $this->traceInternalProductLotToFarms($product_lot_id);
    }

    /**
     * Trace internal product lot through the full supply chain.
     */
    private function traceInternalProductLotToFarms(int $product_lot_id): array
    {
        $sql = "
            SELECT DISTINCT
                l.plot_id, l.plot_code, l.plot_name,
                l.farmer_user_id, l.farmer_name,
                l.company_id, l.company_name,
                l.province_id, l.address, l.country,
                l.land_area, l.coordinates,
                l.ownership, l.classify, l.eudr_status,
                l.maximum_yield, l.area_24, l.status AS land_status,
                tt.transaction_ticket_id, tt.transaction_ticket_code, tt.transaction_ticket_type,
                tt.seller_user_id, tt.seller_name, tt.seller_phone, tt.seller_account_type,
                tt.buyer_user_id, tt.buyer_name, tt.buyer_phone, tt.buyer_account_type,
                tt.latex_weight AS ticket_latex_weight,
                tt.scrap_rubber_weight AS ticket_scrap_rubber_weight,
                tt.status AS ticket_status,
                tll.allocated_latex_weight, tll.allocated_scrap_weight,
                tll.estimated_harvest_date, tll.actual_harvest_date
            FROM eudr_production_product_lot_items pli
            INNER JOIN eudr_production_rubber_blocks rb
                ON rb.rubber_block_id = pli.rubber_block_id
            INNER JOIN eudr_tanks_raw_material_releases rmr
                ON rmr.production_order_id = rb.production_order_id
            INNER JOIN eudr_tanks_raw_material_release_items rmri
                ON rmri.material_release_id = rmr.material_release_id
            INNER JOIN eudr_tanks_raw_material_history rmh
                ON rmh.raw_material_tank_id = rmri.raw_tank_id
                AND rmh.entity_type = 'transportation_route'
            INNER JOIN eudr_transportation_route_transaction_tickets rtt
                ON rtt.transportation_route_id = rmh.entity_id
            INNER JOIN eudr_transaction_tickets tt
                ON tt.transaction_ticket_id = rtt.transaction_ticket_id
            INNER JOIN eudr_transaction_ticket_land_links tll
                ON tll.transaction_ticket_id = tt.transaction_ticket_id
            INNER JOIN eudr_lands l
                ON l.plot_id = tll.plot_id
            WHERE pli.product_lot_id = ?
            ORDER BY l.plot_id ASC
        ";

        $rows = $this->db->rawQuery($sql, [$product_lot_id]);

        // Group by farm, collect transaction tickets per farm
        $farmsMap = [];
        foreach ($rows as $row) {
            $plotId = (int)$row['plot_id'];
            if (!isset($farmsMap[$plotId])) {
                $farmsMap[$plotId] = [
                    'plot_id' => $plotId,
                    'plot_code' => $row['plot_code'],
                    'plot_name' => $row['plot_name'],
                    'farmer_user_id' => isset($row['farmer_user_id']) ? (int)$row['farmer_user_id'] : null,
                    'farmer_name' => $row['farmer_name'],
                    'company_id' => (int)$row['company_id'],
                    'company_name' => $row['company_name'],
                    'province_id' => isset($row['province_id']) ? (int)$row['province_id'] : null,
                    'address' => $row['address'],
                    'country' => $row['country'],
                    'land_area' => isset($row['land_area']) ? (float)$row['land_area'] : null,
                    'coordinates' => $row['coordinates'],
                    'ownership' => $row['ownership'],
                    'classify' => $row['classify'],
                    'eudr_status' => isset($row['eudr_status']) ? (int)$row['eudr_status'] : 0,
                    'maximum_yield' => isset($row['maximum_yield']) ? (int)$row['maximum_yield'] : 0,
                    'area_24' => isset($row['area_24']) ? (float)$row['area_24'] : null,
                    'land_status' => $row['land_status'],
                    'transaction_tickets' => [],
                ];
            }

            $ticketId = (int)$row['transaction_ticket_id'];
            // Avoid duplicate tickets per farm
            $existingTicketIds = array_column($farmsMap[$plotId]['transaction_tickets'], 'transaction_ticket_id');
            if (!in_array($ticketId, $existingTicketIds)) {
                $farmsMap[$plotId]['transaction_tickets'][] = [
                    'transaction_ticket_id' => $ticketId,
                    'transaction_ticket_code' => $row['transaction_ticket_code'],
                    'transaction_ticket_type' => $row['transaction_ticket_type'],
                    'seller_user_id' => isset($row['seller_user_id']) ? (int)$row['seller_user_id'] : null,
                    'seller_name' => $row['seller_name'],
                    'seller_phone' => $row['seller_phone'],
                    'seller_account_type' => $row['seller_account_type'],
                    'buyer_user_id' => isset($row['buyer_user_id']) ? (int)$row['buyer_user_id'] : null,
                    'buyer_name' => $row['buyer_name'],
                    'buyer_phone' => $row['buyer_phone'],
                    'buyer_account_type' => $row['buyer_account_type'],
                    'ticket_latex_weight' => isset($row['ticket_latex_weight']) ? (float)$row['ticket_latex_weight'] : 0,
                    'ticket_scrap_rubber_weight' => isset($row['ticket_scrap_rubber_weight']) ? (float)$row['ticket_scrap_rubber_weight'] : 0,
                    'ticket_status' => $row['ticket_status'],
                    'allocated_latex_weight' => isset($row['allocated_latex_weight']) ? (float)$row['allocated_latex_weight'] : 0,
                    'allocated_scrap_weight' => isset($row['allocated_scrap_weight']) ? (float)$row['allocated_scrap_weight'] : 0,
                    'estimated_harvest_date' => $row['estimated_harvest_date'],
                    'actual_harvest_date' => $row['actual_harvest_date'],
                ];
            }
        }

        return array_values($farmsMap);
    }

    /**
     * Trace external product lot directly to farms via product_lot_lands.
     */
    private function traceExternalProductLotToFarms(int $product_lot_id): array
    {
        $sql = "
            SELECT DISTINCT
                l.plot_id, l.plot_code, l.plot_name,
                l.farmer_user_id, l.farmer_name,
                l.company_id, l.company_name,
                l.province_id, l.address, l.country,
                l.land_area, l.coordinates,
                l.ownership, l.classify, l.eudr_status,
                l.maximum_yield, l.area_24, l.status AS land_status,
                pll.harvest_weight
            FROM eudr_production_product_lot_lands pll
            INNER JOIN eudr_lands l ON l.plot_id = pll.plot_id
            WHERE pll.product_lot_id = ?
            ORDER BY l.plot_id ASC
        ";

        $rows = $this->db->rawQuery($sql, [$product_lot_id]);

        $farms = [];
        foreach ($rows as $row) {
            $farms[] = [
                'plot_id' => (int)$row['plot_id'],
                'plot_code' => $row['plot_code'],
                'plot_name' => $row['plot_name'],
                'farmer_user_id' => isset($row['farmer_user_id']) ? (int)$row['farmer_user_id'] : null,
                'farmer_name' => $row['farmer_name'],
                'company_id' => (int)$row['company_id'],
                'company_name' => $row['company_name'],
                'province_id' => isset($row['province_id']) ? (int)$row['province_id'] : null,
                'address' => $row['address'],
                'country' => $row['country'],
                'land_area' => isset($row['land_area']) ? (float)$row['land_area'] : null,
                'coordinates' => $row['coordinates'],
                'ownership' => $row['ownership'],
                'classify' => $row['classify'],
                'eudr_status' => isset($row['eudr_status']) ? (int)$row['eudr_status'] : 0,
                'maximum_yield' => isset($row['maximum_yield']) ? (int)$row['maximum_yield'] : 0,
                'area_24' => isset($row['area_24']) ? (float)$row['area_24'] : null,
                'land_status' => $row['land_status'],
                'harvest_weight' => isset($row['harvest_weight']) ? (float)$row['harvest_weight'] : 0,
                'transaction_tickets' => [],
            ];
        }

        return $farms;
    }

    /**
     * {@inheritdoc}
     */
    public function createExternalProductLot(array $data): ?ProductLot
    {
        $lands_data = $data['lands'] ?? [];
        $transport_data = $data['transport'] ?? null;
        $non_eudr_items = $data['non_eudr_items'] ?? [];
        $attachments = $data['attachments'] ?? [];
        unset($data['lands'], $data['transport'], $data['non_eudr_items'], $data['attachments']);

        $data['lot_type'] = 'external';
        $eudr_type = $data['eudr_type'] ?? 'eudr';

        $this->db->startTransaction();

        $product_lot_id = (int)$this->db->insert('eudr_production_product_lots', $data);
        if ($product_lot_id <= 0) {
            $this->db->rollback();
            return null;
        }

        if ($eudr_type === 'eudr') {
            // Insert land links
            foreach ($lands_data as $land) {
                $this->db->insert('eudr_production_product_lot_lands', [
                    'product_lot_id' => $product_lot_id,
                    'plot_id' => (int)$land['plot_id'],
                    'harvest_weight' => (float)($land['harvest_weight'] ?? 0),
                    'notes' => $land['notes'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            }

            // Insert transport
            if (!empty($transport_data)) {
                $this->db->insert('eudr_production_product_lot_transports', [
                    'product_lot_id' => $product_lot_id,
                    'vehicle_license_plate' => $transport_data['vehicle_license_plate'] ?? '',
                    'driver_name' => $transport_data['driver_name'] ?? '',
                    'driver_phone' => $transport_data['driver_phone'] ?? '',
                    'transport_date' => $transport_data['transport_date'] ?? null,
                    'pickup_time' => $transport_data['pickup_time'] ?? null,
                    'pickup_location' => $transport_data['pickup_location'] ?? '',
                    'delivery_time' => $transport_data['delivery_time'] ?? null,
                    'delivery_location' => $transport_data['delivery_location'] ?? '',
                    'notes' => $transport_data['notes'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            }
        } else {
            // non_eudr: Insert repeater items
            foreach ($non_eudr_items as $idx => $item) {
                $this->db->insert('eudr_production_product_lot_non_eudr_items', [
                    'product_lot_id' => $product_lot_id,
                    'item_name' => $item['item_name'] ?? '',
                    'quantity' => (float)($item['quantity'] ?? 0),
                    'unit' => $item['unit'] ?? '',
                    'weight' => (float)($item['weight'] ?? 0),
                    'sort_order' => (int)($item['sort_order'] ?? $idx),
                    'notes' => $item['notes'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            }

            // non_eudr: Insert attachment pivot rows (file_id → eudr_general_files)
            foreach ($attachments as $attachment) {
                $this->db->insert('eudr_production_product_lot_attachments', [
                    'product_lot_id'  => $product_lot_id,
                    'file_id'         => (int)($attachment['file_id'] ?? 0),
                    'attachment_type' => $attachment['attachment_type'] ?? 'contract',
                    'label'           => $attachment['label'] ?? null,
                    'created_by'      => (int)($attachment['created_by'] ?? 0),
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
                if ($this->db->getLastErrno() !== 0) {
                    $this->db->rollback();
                    return null;
                }
            }
        }

        $this->db->commit();

        return $this->findProductLotOfId($product_lot_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateExternalProductLot(int $product_lot_id, array $data): ?ProductLot
    {
        $lands_data = $data['lands'] ?? null;
        $transport_data = $data['transport'] ?? null;
        $non_eudr_items = $data['non_eudr_items'] ?? null;
        $attachments_to_add = $data['attachments'] ?? null; // new attachments to append
        unset($data['lands'], $data['transport'], $data['non_eudr_items'], $data['attachments']);

        // Determine eudr_type from current record if not in data
        $existing = $this->findProductLotOfId($product_lot_id);
        $eudr_type = $data['eudr_type'] ?? ($existing ? $existing->getEudrType() : 'eudr');

        $this->db->startTransaction();

        // Update main record
        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->where('deleted_by', 0);
        $this->db->update('eudr_production_product_lots', $data);

        if ($eudr_type === 'eudr') {
            // Replace land links
            if ($lands_data !== null && is_array($lands_data)) {
                $this->db->where('product_lot_id', $product_lot_id);
                $this->db->delete('eudr_production_product_lot_lands');

                foreach ($lands_data as $land) {
                    $this->db->insert('eudr_production_product_lot_lands', [
                        'product_lot_id' => $product_lot_id,
                        'plot_id' => (int)$land['plot_id'],
                        'harvest_weight' => (float)($land['harvest_weight'] ?? 0),
                        'notes' => $land['notes'] ?? '',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        return null;
                    }
                }
            }

            // Replace transport
            if ($transport_data !== null) {
                $this->db->where('product_lot_id', $product_lot_id);
                $this->db->delete('eudr_production_product_lot_transports');

                if (!empty($transport_data)) {
                    $this->db->insert('eudr_production_product_lot_transports', [
                        'product_lot_id' => $product_lot_id,
                        'vehicle_license_plate' => $transport_data['vehicle_license_plate'] ?? '',
                        'driver_name' => $transport_data['driver_name'] ?? '',
                        'driver_phone' => $transport_data['driver_phone'] ?? '',
                        'transport_date' => $transport_data['transport_date'] ?? null,
                        'pickup_time' => $transport_data['pickup_time'] ?? null,
                        'pickup_location' => $transport_data['pickup_location'] ?? '',
                        'delivery_time' => $transport_data['delivery_time'] ?? null,
                        'delivery_location' => $transport_data['delivery_location'] ?? '',
                        'notes' => $transport_data['notes'] ?? '',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        return null;
                    }
                }
            }
        } else {
            // non_eudr: Replace repeater items
            if ($non_eudr_items !== null && is_array($non_eudr_items)) {
                $this->db->where('product_lot_id', $product_lot_id);
                $this->db->delete('eudr_production_product_lot_non_eudr_items');

                foreach ($non_eudr_items as $idx => $item) {
                    $this->db->insert('eudr_production_product_lot_non_eudr_items', [
                        'product_lot_id' => $product_lot_id,
                        'item_name' => $item['item_name'] ?? '',
                        'quantity' => (float)($item['quantity'] ?? 0),
                        'unit' => $item['unit'] ?? '',
                        'weight' => (float)($item['weight'] ?? 0),
                        'sort_order' => (int)($item['sort_order'] ?? $idx),
                        'notes' => $item['notes'] ?? '',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        return null;
                    }
                }
            }

            // non_eudr: Append new attachment pivot rows (do not delete existing ones)
            if (!empty($attachments_to_add) && is_array($attachments_to_add)) {
                foreach ($attachments_to_add as $attachment) {
                    $this->db->insert('eudr_production_product_lot_attachments', [
                        'product_lot_id'  => $product_lot_id,
                        'file_id'         => (int)($attachment['file_id'] ?? 0),
                        'attachment_type' => $attachment['attachment_type'] ?? 'contract',
                        'label'           => $attachment['label'] ?? null,
                        'created_by'      => (int)($attachment['created_by'] ?? 0),
                        'created_at'      => date('Y-m-d H:i:s'),
                    ]);
                    if ($this->db->getLastErrno() !== 0) {
                        $this->db->rollback();
                        return null;
                    }
                }
            }
        }

        $this->db->commit();

        return $this->findProductLotOfId($product_lot_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProductLot(int $product_lot_id, int $deleted_by): void
    {
        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->where('deleted_by', 0);
        $this->db->where('status', 'draft');
        $this->db->update('eudr_production_product_lots', [
            'deleted_by' => $deleted_by,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function confirmProductLot(int $product_lot_id, int $confirmed_by): ?ProductLot
    {
        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->where('deleted_by', 0);
        $this->db->where('status', 'draft');
        $this->db->update('eudr_production_product_lots', [
            'status' => 'confirmed',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'updated_by' => $confirmed_by,
        ]);

        return $this->findProductLotOfId($product_lot_id);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelProductLot(int $product_lot_id, int $cancelled_by): ?ProductLot
    {
        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->where('deleted_by', 0);
        $this->db->where('status', ['draft', 'confirmed'], 'IN');
        $this->db->update('eudr_production_product_lots', [
            'status' => 'cancelled',
            'updated_by' => $cancelled_by,
        ]);

        return $this->findProductLotOfId($product_lot_id);
    }

    /**
     * {@inheritdoc}
     */
    public function findLandsByProductLotId(int $product_lot_id): array
    {
        $this->db->where('pll.product_lot_id', $product_lot_id);
        $this->db->join('eudr_lands l', 'l.plot_id = pll.plot_id', 'LEFT');
        $this->db->join('eudr_general_provinces p', 'p.province_id = l.province_id', 'LEFT');

        $cols = 'pll.*, l.plot_code, l.plot_name, l.farmer_name, l.coordinates, l.land_area, l.address, l.eudr_status, l.register_type, p.province_id, p.province_name';
        $records = $this->db->arraybuilder()->get('eudr_production_product_lot_lands pll', null, $cols);

        $items = [];
        if (!empty($records)) {
            foreach ($records as $row) {
                $row['coordinates'] = !empty($row['coordinates']) ? json_decode($row['coordinates'], true) : [];
                $items[] = $row;
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function findTransportByProductLotId(int $product_lot_id): ?array
    {
        $this->db->where('product_lot_id', $product_lot_id);
        $row = $this->db->getOne('eudr_production_product_lot_transports');

        if (empty($row)) {
            return null;
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function generateExternalCode(): string
    {
        $code = '';
        while (true) {
            $code = 'eplt-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $existing = $this->findProductLotOfCode($code);
            if (!$existing) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function findNonEudrItemsByProductLotId(int $product_lot_id): array
    {
        $this->db->where('product_lot_id', $product_lot_id);
        $this->db->orderBy('sort_order', 'ASC');
        $records = $this->db->get('eudr_production_product_lot_non_eudr_items');

        return $records ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function findAttachmentsByProductLotId(int $product_lot_id): array
    {
        $this->db->where('a.product_lot_id', $product_lot_id);
        $this->db->orderBy('a.created_at', 'ASC');
        $records = $this->db->get(
            'eudr_production_product_lot_attachments a'
            . ' LEFT JOIN eudr_general_files f ON f.file_id = a.file_id',
            null,
            'a.attachment_id, a.product_lot_id, a.file_id, a.attachment_type, a.label,'
            . ' a.created_by, a.created_at,'
            . ' f.file_code, f.file_name, f.file_path, f.file_type, f.file_size, f.image_size'
        );

        return $records ?: [];
    }
}
