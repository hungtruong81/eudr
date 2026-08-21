<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Grade;

use App\Application\Utility\CurrentUserContext;
use App\Domain\Grade\Grade;
use App\Domain\Grade\GradeNotFoundException;
use App\Domain\Grade\GradeRepository;
use App\Application\Utility\Utils;

class InDatabaseGradeRepository implements GradeRepository
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
     * InDatabaseGradeRepository constructor.
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
     * Apply scope-based filtering (self/own/all).
     * Note: grade table does not contain company_id, so own behaves like self.
     */
    private function scopeWhere(string $scope, int $authUserId, ?int $companyId = null, ?int $companyIdParam = null, string $alias = 'g'): void
    {
        $prefix = $alias ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_by', 0);

        if ($scope === 'self' || $scope === 'own') {
            $this->db->where($prefix . 'created_by', $authUserId);
        }
        // scope "all" intentionally applies no creator/company restriction because table lacks company_id
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $search = $params['search'] ?? '';
        $grade_code = $params['grade_code'] ?? '';
        $grade_id = $params['grade_id'] ?? 0;

        // Count total records
        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'g');
        if (!empty($search)) {
            $this->db->where('(g.name LIKE ? OR g.grade_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($grade_code)) {
            $this->db->where('g.grade_code', $grade_code);
        }
        if (!empty($grade_id)) {
            $this->db->where('g.grade_id', $grade_id);
        }
        $total_records = (int)$this->db->getValue('eudr_production_grades g', 'count(*)');

        // Set pagination
        $this->db->pageLimit = $page_limit;

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'g');
        if (!empty($search)) {
            $this->db->where('(g.name LIKE ? OR g.grade_code LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($grade_code)) {
            $this->db->where('g.grade_code', $grade_code);
        }
        if (!empty($grade_id)) {
            $this->db->where('g.grade_id', $grade_id);
        }

        $cols = "g.*,
            (
                SELECT gp.domestic_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_domestic_price,
            (
                SELECT gp.international_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_international_price,
            (
                SELECT gp.effective_from
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_from,
            (
                SELECT gp.effective_to
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_to";

        if (!empty($params['order_by'])) {
            $this->db->orderBy('g.' . $params['order_by'], $params['order_type'] ?? 'ASC');
        } else {
            $this->db->orderBy('g.grade_id', 'DESC');
        }
        $records = $this->db->arraybuilder()->paginate('eudr_production_grades g', $page, $cols);

        $items = [];
        if ($this->db->count > 0) {
            foreach ($records as $item) {
                $items[] = new Grade($item['grade_id'], $item);
            }
        }

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $items,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findGradeOfId(int $grade_id): ?Grade
    {
        $this->db->where('g.grade_id', $grade_id);
        $this->db->where('g.deleted_by', 0);
        $cols = "g.*,
            (
                SELECT gp.domestic_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_domestic_price,
            (
                SELECT gp.international_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_international_price,
            (
                SELECT gp.effective_from
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_from,
            (
                SELECT gp.effective_to
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_to";
        $grade = $this->db->getOne('eudr_production_grades g', $cols);
        if (empty($grade)) {
            return null;
        }

        return new Grade($grade['grade_id'], $grade);
    }

    /**
     * {@inheritdoc}
     */
    public function findGradeOfIdWithPermission(int $grade_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Grade
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'g');
        $this->db->where('g.grade_id', $grade_id);

        $cols = "g.*,
            (
                SELECT gp.domestic_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_domestic_price,
            (
                SELECT gp.international_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_international_price,
            (
                SELECT gp.effective_from
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_from,
            (
                SELECT gp.effective_to
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_to";
        $grade = $this->db->getOne('eudr_production_grades g', $cols);
        if (empty($grade)) {
            return null;
        }

        return new Grade($grade['grade_id'], $grade);
    }

    /**
     * {@inheritdoc}
     */
    public function findGradeOfCode(string $code): ?Grade
    {
        $this->db->where('g.grade_code', $code);
        $this->db->where('g.deleted_by', 0);
        $cols = "g.*,
            (
                SELECT gp.domestic_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_domestic_price,
            (
                SELECT gp.international_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_international_price,
            (
                SELECT gp.effective_from
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_from,
            (
                SELECT gp.effective_to
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.created_at DESC
                LIMIT 1
            ) AS current_price_effective_to";
        $grade = $this->db->getOne('eudr_production_grades g', $cols);
        if (empty($grade)) {
            return null;
        }

        return new Grade($grade['grade_id'], $grade);
    }

    /**
     * {@inheritdoc}
     */
    public function findGradeOfName(string $name): ?Grade
    {
        $this->db->where('g.name', $name);
        $this->db->where('g.deleted_by', 0);
        $grade = $this->db->getOne('eudr_production_grades g', 'g.*');
        if (empty($grade)) {
            return null;
        }

        return new Grade($grade['grade_id'], $grade);
    }

    /**
     * {@inheritdoc}
     */
    public function findGradeOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Grade
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, 'g');
        $this->db->where('g.grade_code', $code);

        $cols = "g.*,
            (
                SELECT gp.domestic_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.effective_from DESC
                LIMIT 1
            ) AS current_domestic_price,
            (
                SELECT gp.international_price
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.effective_from DESC
                LIMIT 1
            ) AS current_international_price,
            (
                SELECT gp.effective_from
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.effective_from DESC
                LIMIT 1
            ) AS current_price_effective_from,
            (
                SELECT gp.effective_to
                FROM eudr_production_grade_prices gp
                WHERE gp.grade_id = g.grade_id
                    AND gp.deleted_by = 0
                    AND gp.effective_from <= NOW()
                    AND (gp.effective_to IS NULL OR gp.effective_to > NOW())
                ORDER BY gp.effective_from DESC
                LIMIT 1
            ) AS current_price_effective_to";
        $grade = $this->db->getOne('eudr_production_grades g', $cols);
        if (empty($grade)) {
            return null;
        }

        return new Grade($grade['grade_id'], $grade);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = 'grad-' . date('ymd') . '-' . Utils::generateRandomString(8);
            $grade = $this->findGradeOfCode($code);
            if (!$grade) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createGrade(array $data): ?Grade
    {
        $this->db->insert('eudr_production_grades', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $grade_id = $this->db->getInsertId();
        return $this->findGradeOfId((int)$grade_id);
    }

    /**
     * {@inheritdoc}
     */
    public function createGradePrice(int $grade_id, array $data): ?array
    {
        $data_insert = array_merge($data, ['grade_id' => $grade_id]);
        $this->db->insert('eudr_production_grade_prices', $data_insert);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }

        $grade_price_id = (int)$this->db->getInsertId();
        $this->db->where('gp.grade_price_id', $grade_price_id);
        $this->db->where('gp.deleted_by', 0);
        return $this->db->getOne('eudr_production_grade_prices gp', 'gp.*');
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPriceOfGrade(int $grade_id, ?string $at_datetime = null): ?array
    {
        $at = $at_datetime ?: date('Y-m-d H:i:s');

        $this->db->where('gp.grade_id', $grade_id);
        $this->db->where('gp.deleted_by', 0);
        $this->db->where('gp.effective_from <= ?', [$at]);
        $this->db->where('(gp.effective_to IS NULL OR gp.effective_to > ?)', [$at]);
        $this->db->orderBy('gp.created_at', 'DESC');
        $record = $this->db->getOne('eudr_production_grade_prices gp', 'gp.grade_price_id, gp.domestic_price, gp.international_price, gp.effective_from, gp.effective_to, gp.note');

        return $record ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceHistoryOfGrade(int $grade_id, array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $page_limit = $params['page_limit'] ?? 10;
        $effective_from = $params['effective_from'] ?? null;
        $effective_to = $params['effective_to'] ?? null;

        $this->db->where('gp.grade_id', $grade_id);
        $this->db->where('gp.deleted_by', 0);
        if (!empty($effective_from)) {
            $this->db->where('gp.effective_from >= ?', [$effective_from]);
        }
        if (!empty($effective_to)) {
            $this->db->where('(gp.effective_to IS NULL OR gp.effective_to <= ?)', [$effective_to]);
        }
        $total_records = (int)$this->db->getValue('eudr_production_grade_prices gp', 'count(*)');

        $this->db->pageLimit = $page_limit;
        $this->db->where('gp.grade_id', $grade_id);
        $this->db->where('gp.deleted_by', 0);
        if (!empty($effective_from)) {
            $this->db->where('gp.effective_from >= ?', [$effective_from]);
        }
        if (!empty($effective_to)) {
            $this->db->where('(gp.effective_to IS NULL OR gp.effective_to <= ?)', [$effective_to]);
        }
        $this->db->orderBy('gp.effective_from', 'DESC');
        $records = $this->db->arraybuilder()->paginate('eudr_production_grade_prices gp', $page, 'gp.grade_price_id, gp.domestic_price, gp.international_price, gp.effective_from, gp.effective_to, gp.note');

        return [
            'current_page' => $page,
            'total_pages' => $this->db->totalPages,
            'total_records' => $total_records,
            'page_limit' => $this->db->pageLimit,
            'records' => $records,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function hasOverlappingGradePricePeriod(int $grade_id, string $effective_from, ?string $effective_to = null, ?int $exclude_grade_price_id = null): bool
    {
        $effective_to = $effective_to ?: '9999-12-31 23:59:59';

        $sql = "
            SELECT COUNT(*) AS total
            FROM eudr_production_grade_prices gp
            WHERE gp.grade_id = ?
              AND gp.deleted_by = 0
              AND (? < COALESCE(gp.effective_to, '9999-12-31 23:59:59'))
              AND (gp.effective_from < ?)
        ";

        $bindings = [$grade_id, $effective_from, $effective_to];

        if (!empty($exclude_grade_price_id)) {
            $sql .= ' AND gp.grade_price_id <> ?';
            $bindings[] = $exclude_grade_price_id;
        }

        $rows = $this->db->rawQuery($sql, $bindings);
        $total = (int)($rows[0]['total'] ?? 0);

        return $total > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function updateGrade(int $grade_id, array $data_update): Grade
    {
        $this->db->where('grade_id', $grade_id);
        $this->db->update('eudr_production_grades', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new GradeNotFoundException("Grade not found with ID: $grade_id");
        }
        return $this->findGradeOfId($grade_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateGradeWithPermission(int $grade_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Grade
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, '');
        $this->db->where('grade_id', $grade_id);
        $this->db->update('eudr_production_grades', $data_update);
        if ($this->db->getLastErrno() !== 0) {
            throw new GradeNotFoundException("Grade not found with ID: $grade_id");
        }

        return $this->findGradeOfId($grade_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteGrade(int $grade_id, int $deleted_by): void
    {
        $this->db->where('grade_id', $grade_id);
        $this->db->update('eudr_production_grades', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteGradeWithPermission(int $grade_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void
    {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);

        $this->scopeWhere($scope, $authUserId, $company_id, $company_id_param, '');
        $this->db->where('grade_id', $grade_id);
        $this->db->update('eudr_production_grades', ['deleted_by' => $deleted_by, 'deleted_at' => date('Y-m-d H:i:s', time())]);
    }
}
