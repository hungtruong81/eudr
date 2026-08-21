<?php
declare(strict_types=1);

namespace App\Application\Utility;

class RequestCurrentUserContext implements CurrentUserContext
{
    private ?int $userId = null;
    private ?int $roleId = null;
    private ?int $companyId = null;

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function setRoleId(?int $roleId): void
    {
        $this->roleId = $roleId;
    }

    public function setCompanyId(?int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }
}