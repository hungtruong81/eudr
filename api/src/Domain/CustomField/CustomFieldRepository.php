<?php

declare(strict_types=1);

namespace App\Domain\CustomField;

interface CustomFieldRepository
{
    // ──────────────────────────────────────────────
    // Definitions (meta-schema)
    // ──────────────────────────────────────────────

    /**
     * Danh sách định nghĩa trường tùy chỉnh, hỗ trợ phân trang.
     *
     * @return array{current_page:int,total_pages:int,total_records:int,page_limit:int,records:CustomFieldDefinition[]}
     */
    public function findAllDefinitions(
        array $params = [],
        string $scope = 'own',
        ?int $company_id = null,
        ?int $company_id_param = null
    ): array;

    /**
     * Tất cả định nghĩa active cho một entity_type (dùng khi render form / validate).
     *
     * @return CustomFieldDefinition[]
     */
    public function findDefinitionsByEntityType(string $entity_type, int $company_id): array;

    public function findDefinitionById(int $field_id): ?CustomFieldDefinition;

    public function findDefinitionByKey(string $field_key, string $entity_type, int $company_id): ?CustomFieldDefinition;

    public function findDefinitionByCode(string $field_code): ?CustomFieldDefinition;

    /**
     * Tạo mã field_code duy nhất (random, unique).
     */
    public function generateCode(): string;

    public function createDefinition(array $data): ?CustomFieldDefinition;

    public function updateDefinition(int $field_id, array $data): ?CustomFieldDefinition;

    /**
     * Soft-delete: cập nhật deleted_at / deleted_by.
     */
    public function deleteDefinition(int $field_id, int $deleted_by): bool;

    // ──────────────────────────────────────────────
    // Values
    // ──────────────────────────────────────────────

    /**
     * Lấy tất cả giá trị trường tùy chỉnh của một thực thể, kèm metadata định nghĩa.
     *
     * @return CustomFieldValue[]
     */
    public function getEntityValues(string $entity_type, int $entity_id, int $company_id): array;

    /**
     * Upsert nhiều giá trị cho một thực thể cùng lúc.
     * $values = [['field_id'=>X,'value'=>'...'], ...]
     */
    public function setEntityValues(
        string $entity_type,
        int $entity_id,
        int $company_id,
        array $values,
        int $user_id
    ): array;

    /**
     * Xóa tất cả giá trị custom field của một thực thể (dùng khi xóa thực thể).
     */
    public function deleteEntityValues(string $entity_type, int $entity_id): void;
}
