<?php

declare(strict_types=1);

namespace App\Domain\Grade;

interface GradeRepository
{
    /**
     * @param array $params
     * @return Grade[]
     */
    public function findAll(array $params = [], ?int $auth_user_id = null, string $scope = 'own', ?int $company_id = null, ?int $company_id_param = null): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $grade_id
     * @return Grade|null
     * @throws GradeNotFoundException
     */
    public function findGradeOfId(int $grade_id): ?Grade;
    public function findGradeOfIdWithPermission(int $grade_id, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Grade;
    /**
     * @param string $code
     * @return Grade|null
     */
    public function findGradeOfCode(string $code): ?Grade;
    public function findGradeOfName(string $name): ?Grade;
    public function findGradeOfCodeWithPermission(string $code, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): ?Grade;
    public function createGradePrice(int $grade_id, array $data): ?array;
    public function getCurrentPriceOfGrade(int $grade_id, ?string $at_datetime = null): ?array;
    public function getPriceHistoryOfGrade(int $grade_id, array $params = []): array;
    public function hasOverlappingGradePricePeriod(int $grade_id, string $effective_from, ?string $effective_to = null, ?int $exclude_grade_price_id = null): bool;
    /**
     * @param array $data
     * @return Grade|null
     */
    public function createGrade(array $data): ?Grade;
    /**
     * @param int $grade_id
     * @param array $data_update
     * @return Grade
     */
    public function updateGrade(int $grade_id, array $data_update): Grade;
    public function updateGradeWithPermission(int $grade_id, array $data_update, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): Grade;
    /**
     * @param int $grade_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteGrade(int $grade_id, int $deleted_by): void;
    public function deleteGradeWithPermission(int $grade_id, int $deleted_by, ?int $auth_user_id, string $scope, ?int $company_id = null, ?int $company_id_param = null): void;
}
