<?php
declare(strict_types=1);

namespace App\Domain\User;

use JsonSerializable;

class User implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $user_id;
    /**
     * @var int|null
     */
    private $parent_user_id;
    /**
     * @var string
     */
    private $user_code;
    /**
     * @var int
     */
    private $company_id;
    /**
     * @var string
     */
    private $company_name;
    /**
     * @var string
     */
    private $company_short_name;
    /**
     * @var string
     */
    private $company_code;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $register_type;
    /**
     * @var string
     */
    private $full_name;
    /**
     * @var string
     */
    private $avatar;
    /**
     * @var string
     */
    private $salt;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $phone;
    /**
     * @var array
     */
    private $permissions;
    /**
     * @var array
     */
    private $roles;
    /**
     * @var int
     */
    private $is_approved;
    /**
     * @var int
     */
    private $is_active;
    /**
     * @var string
     */
    private $created_at;
    /**
     * @var string
     */
    private $updated_at;
    /**
     * @var bool
     */
    private $simple;

    /**
     * @param int|null  $user_id
     * @param array     $data_user
     * @param bool      $simple
     */
    public function __construct(?int $user_id, array $data_user, bool $simple = false)
    {
        $this->simple = $simple;
        $this->user_id = $user_id;
        $this->user_code = $data_user['user_code'] ?? '';
        $this->company_id = $data_user['company_id'] ?? 0;
        $this->company_code = $data_user['company_code'] ?? '';
        $this->company_name = $data_user['company_name'] ?? '';
        $this->company_short_name = $data_user['short_name'] ?? '';
        $this->username = $data_user['username'] ?? '';
        $this->register_type = $data_user['register_type'] ?? '';
        $this->full_name = $data_user['full_name'] ?? '';
        $this->avatar = $data_user['avatar'] ?? '';
        $this->email = $data_user['email'] ?? '';
        $this->phone = $data_user['phone'] ?? '';
        $this->is_approved = $data_user['is_approved'] ?? 0;
        $this->is_active = $data_user['is_active'] ?? 0;
        $this->salt = $data_user['salt'] ?? '';
        $this->created_at = $data_user['created_at'] ?? NULL;
        $this->updated_at = $data_user['updated_at'] ?? NULL;
        $this->parent_user_id = $data_user['parent_user_id'] ?? 0;
        $this->permissions = $data_user['permissions'] ?? [];
        $this->roles = $data_user['roles'] ?? [];
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->user_id;
    }
    /**
     * @return int|null
     */
    public function getParentUserId(): ?int
    {
        return $this->parent_user_id;
    }
    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }
    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->user_code;
    }
    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }
    /**
     * @return string
     */
    public function getAvatar(): string
    {
        return $this->avatar;
    }
    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }
    /**
     * @return string
     */
    public function getAccountType(): string
    {
        return $this->register_type;
    }
    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
    /**
     * @return int
     */
    public function getCompanyId(): int
    {
        return $this->company_id;
    }
    /**
     * @return int
     */
    public function getIsActive(): int
    {
        return $this->is_active;
    }
    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->company_name;
    }
    /**
     * @return string
     */
    public function getCompanyShortName(): string
    {
        return $this->company_short_name;
    }
    /**
     * @return string
     */
    public function getCompanyCode(): string
    {
        return $this->company_code;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        if ($this->simple) {
            return [
            'user_code' => $this->user_code,
            'avatar' => $this->avatar,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'parent_user_id' => $this->parent_user_id,
        ];
        }

        return [
            'user_id' => $this->user_id,
            'user_code' => $this->user_code,
            'company_id' => $this->company_id,
            'company_code' => $this->company_code,
            'company_name' => $this->company_name,
            'company_short_name' => $this->company_short_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'register_type' => $this->register_type,
            'roles' => $this->roles,
            //'username' => $this->username,
            'full_name' => $this->full_name,
            //'permissions' => $this->permissions,
            'is_approved' => $this->is_approved,
            'is_active' => $this->is_active,
            'parent_user_id' => $this->parent_user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
