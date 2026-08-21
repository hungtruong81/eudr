<?php
declare(strict_types=1);

namespace App\Application\Utility;

interface CurrentUserContext
{
    public function setUserId(?int $userId): void;
    public function setRoleId(?int $roleId): void;
    public function setCompanyId(?int $companyId): void;

    public function getUserId(): ?int;
    public function getRoleId(): ?int;
    public function getCompanyId(): ?int;
}