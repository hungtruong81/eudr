<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CustomField;

use App\Application\Utility\CurrentUserContext;
use App\Domain\CustomField\CustomFieldDefinition;
use App\Domain\CustomField\CustomFieldRepository;
use App\Domain\CustomField\CustomFieldValue;
use App\Application\Utility\Utils;

class InDatabaseCustomFieldRepository implements CustomFieldRepository
{
    private const VALID_ENTITY_TYPES = ['land', 'plant', 'harvest', 'customer', 'product', 'sales_order', 'product_lot_import_none_eudr'];
    private const VALID_FIELD_TYPES  = ['text', 'textarea', 'number', 'date', 'datetime', 'boolean', 'select'];

    private \MysqliDb $db;
    private CurrentUserContext $currentUser;

    public function __construct(\MysqliDb $db, CurrentUserContext $currentUserContext)
    {
        $this->db          = $db;
        $this->currentUser = $currentUserContext;
    }

    // ──────────────────────────────────────────────────────────────
    // Scope helper
    // ──────────────────────────────────────────────────────────────

    private function scopeWhere(string $scope, int $companyId, ?int $companyIdParam = null, string $alias = 'd'): void
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $this->db->where($prefix . 'deleted_at', null, 'IS');

        if ($scope === 'self' || $scope === 'own') {
            $this->db->where($prefix . 'company_id', $companyId);
        } elseif ($scope === 'all' && !empty($companyIdParam)) {
            $this->db->where($prefix . 'company_id', $companyIdParam);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Definitions
    // ──────────────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     */
    public function findAllDefinitions(
        array $params = [],
        string $scope = 'own',
        ?int $company_id = null,
        ?int $company_id_param = null
    ): array {
        $companyId       = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $companyIdParam  = $company_id_param ?? ($params['company_id_param'] ?? null);
        $page            = (int)($params['page'] ?? 1);
        $pageLimit       = (int)($params['page_limit'] ?? 20);
        $search          = $params['search'] ?? '';
        $entityType      = $params['entity_type'] ?? '';
        $fieldType       = $params['field_type'] ?? '';
        $status          = $params['status'] ?? 'active';

        // Count
        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'd');
        if (!empty($search)) {
            $this->db->where('(d.field_key LIKE ? OR d.field_label LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($entityType) && in_array($entityType, self::VALID_ENTITY_TYPES, true)) {
            $this->db->where('d.entity_type', $entityType);
        }
        if (!empty($fieldType) && in_array($fieldType, self::VALID_FIELD_TYPES, true)) {
            $this->db->where('d.field_type', $fieldType);
        }
        if ($status !== 'all') {
            $this->db->where('d.status', $status !== '' ? $status : 'active');
        }
        $totalRecords = (int)$this->db->getValue('eudr_custom_field_definitions d', 'count(*)');

        // Paginate
        $this->db->pageLimit = $pageLimit;
        $this->scopeWhere($scope, (int)$companyId, $companyIdParam, 'd');
        if (!empty($search)) {
            $this->db->where('(d.field_key LIKE ? OR d.field_label LIKE ?)', ["%$search%", "%$search%"]);
        }
        if (!empty($entityType) && in_array($entityType, self::VALID_ENTITY_TYPES, true)) {
            $this->db->where('d.entity_type', $entityType);
        }
        if (!empty($fieldType) && in_array($fieldType, self::VALID_FIELD_TYPES, true)) {
            $this->db->where('d.field_type', $fieldType);
        }
        if ($status !== 'all') {
            $this->db->where('d.status', $status !== '' ? $status : 'active');
        }
        $this->db->orderBy('d.entity_type', 'ASC');
        $this->db->orderBy('d.sort_order', 'ASC');
        $this->db->orderBy('d.field_id', 'ASC');

        $rows = $this->db->arraybuilder()->paginate('eudr_custom_field_definitions d', $page, 'd.*');
        $records = [];
        if ($this->db->count > 0) {
            foreach ($rows as $row) {
                $records[] = new CustomFieldDefinition((int)$row['field_id'], $row);
            }
        }

        return [
            'current_page'  => $page,
            'total_pages'   => $this->db->totalPages,
            'total_records' => $totalRecords,
            'page_limit'    => $this->db->pageLimit,
            'records'       => $records,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findDefinitionsByEntityType(string $entity_type, int $company_id): array
    {
        $this->db->where('d.entity_type', $entity_type);
        $this->db->where('d.company_id', $company_id);
        $this->db->where('d.status', 'active');
        $this->db->where('d.deleted_at', null, 'IS');
        $this->db->orderBy('d.sort_order', 'ASC');
        $this->db->orderBy('d.field_id', 'ASC');

        $rows = $this->db->get('eudr_custom_field_definitions d', null, 'd.*');
        $records = [];
        foreach ($rows as $row) {
            $records[] = new CustomFieldDefinition((int)$row['field_id'], $row);
        }
        return $records;
    }

    /**
     * {@inheritdoc}
     */
    public function findDefinitionById(int $field_id): ?CustomFieldDefinition
    {
        $this->db->where('field_id', $field_id);
        $this->db->where('deleted_at', null, 'IS');
        $row = $this->db->getOne('eudr_custom_field_definitions');
        if (empty($row)) {
            return null;
        }
        return new CustomFieldDefinition((int)$row['field_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findDefinitionByKey(string $field_key, string $entity_type, int $company_id): ?CustomFieldDefinition
    {
        $this->db->where('field_key', $field_key);
        $this->db->where('entity_type', $entity_type);
        $this->db->where('company_id', $company_id);
        $this->db->where('deleted_at', null, 'IS');
        $row = $this->db->getOne('eudr_custom_field_definitions');
        if (empty($row)) {
            return null;
        }
        return new CustomFieldDefinition((int)$row['field_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function findDefinitionByCode(string $field_code): ?CustomFieldDefinition
    {
        $this->db->where('field_code', $field_code);
        $this->db->where('deleted_at', null, 'IS');
        $row = $this->db->getOne('eudr_custom_field_definitions');
        if (empty($row)) {
            return null;
        }
        return new CustomFieldDefinition((int)$row['field_id'], $row);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCode(): string
    {
        $code = '';
        while (true) {
            $code = "cusf-".date("ymd").'-'.Utils::generateRandomString(8);
            $definition = $this->findDefinitionByCode($code);
            if (!$definition) {
                break;
            }
        }
        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function createDefinition(array $data): ?CustomFieldDefinition
    {
        // Normalize options to JSON string when array provided
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
        }

        $this->db->insert('eudr_custom_field_definitions', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }
        $id = (int)$this->db->getInsertId();
        return $this->findDefinitionById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDefinition(int $field_id, array $data): ?CustomFieldDefinition
    {
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = json_encode($data['options'], JSON_UNESCAPED_UNICODE);
        }

        $this->db->where('field_id', $field_id);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_custom_field_definitions', $data);
        if ($this->db->getLastErrno() !== 0) {
            return null;
        }
        return $this->findDefinitionById($field_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDefinition(int $field_id, int $deleted_by): bool
    {
        $this->db->where('field_id', $field_id);
        $this->db->where('deleted_at', null, 'IS');
        $this->db->update('eudr_custom_field_definitions', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deleted_by,
        ]);
        return $this->db->getLastErrno() === 0;
    }

    // ──────────────────────────────────────────────────────────────
    // Values
    // ──────────────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     */
    public function getEntityValues(string $entity_type, int $entity_id, int $company_id): array
    {
        // Join definitions to get field metadata
        $this->db->where('v.entity_type', $entity_type);
        $this->db->where('v.entity_id', $entity_id);
        $this->db->where('v.company_id', $company_id);
        $this->db->join('eudr_custom_field_definitions d', 'd.field_id = v.field_id', 'INNER');
        $this->db->where('d.deleted_at', null, 'IS');
        $this->db->where('d.status', 'active');
        $this->db->orderBy('d.sort_order', 'ASC');

        $rows = $this->db->get(
            'eudr_custom_field_values v',
            null,
            'v.*, d.field_key, d.field_label, d.field_type'
        );

        $result = [];
        foreach ($rows as $row) {
            $result[] = new CustomFieldValue((int)$row['value_id'], $row);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * Upsert: nếu đã có value cho (field_id, entity_type, entity_id) thì UPDATE, ngược lại INSERT.
     */
    public function setEntityValues(
        string $entity_type,
        int $entity_id,
        int $company_id,
        array $values,
        int $user_id
    ): array {
        $now = date('Y-m-d H:i:s');

        foreach ($values as $entry) {
            $fieldId = (int)($entry['field_id'] ?? 0);
            if ($fieldId <= 0) {
                continue;
            }

            $definition = $this->findDefinitionById($fieldId);
            if ($definition === null || $definition->getCompanyId() !== $company_id) {
                continue;
            }

            [$valueText, $valueNumber, $valueDate] = $this->resolveValueColumns($definition->getFieldType(), $entry['value'] ?? null);

            // Check existing
            $this->db->where('field_id', $fieldId);
            $this->db->where('entity_type', $entity_type);
            $this->db->where('entity_id', $entity_id);
            $existing = $this->db->getOne('eudr_custom_field_values', 'value_id');

            if (!empty($existing)) {
                $this->db->where('value_id', (int)$existing['value_id']);
                $this->db->update('eudr_custom_field_values', [
                    'value_text'   => $valueText,
                    'value_number' => $valueNumber,
                    'value_date'   => $valueDate,
                    'updated_by'   => $user_id,
                    'updated_at'   => $now,
                ]);
            } else {
                $this->db->insert('eudr_custom_field_values', [
                    'field_id'     => $fieldId,
                    'entity_type'  => $entity_type,
                    'entity_id'    => $entity_id,
                    'company_id'   => $company_id,
                    'value_text'   => $valueText,
                    'value_number' => $valueNumber,
                    'value_date'   => $valueDate,
                    'created_by'   => $user_id,
                    'created_at'   => $now,
                ]);
            }
        }

        return $this->getEntityValues($entity_type, $entity_id, $company_id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteEntityValues(string $entity_type, int $entity_id): void
    {
        $this->db->where('entity_type', $entity_type);
        $this->db->where('entity_id', $entity_id);
        $this->db->delete('eudr_custom_field_values');
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Maps a raw user-provided value to the correct storage columns
     * based on field_type.
     *
     * @return array{0:string|null,1:float|null,2:string|null}
     */
    private function resolveValueColumns(string $fieldType, mixed $value): array
    {
        if ($value === null || $value === '') {
            return [null, null, null];
        }

        return match ($fieldType) {
            'number' => [null, (float)$value, null],
            'date', 'datetime' => [null, null, (string)$value],
            'boolean' => [(string)(int)(bool)$value, null, null],
            default   => [(string)$value, null, null],
        };
    }
}
