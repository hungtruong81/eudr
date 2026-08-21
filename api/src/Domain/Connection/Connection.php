<?php

declare(strict_types=1);

namespace App\Domain\Connection;

use JsonSerializable;

class Connection implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $connection_id;
    /**
     * @var int|null
    */
    private $user_id;
    /**
     * @var int|null
    */
    private $connected_user_id;
    /**
     * @var string
    */
    private $connection_type;
    /**
     * @var string
    */
    private $status;
    /**
     * @var date
     */
    private $created_at;

    /**
     * @param int|null  $connection_id
     * @param array    $data
     */
    public function __construct(?int $connection_id, array $data)
    {
        $this->connection_id = $connection_id;
        $this->user_id = $data['user_id'] ?? 0;
        $this->connected_user_id = $data['connected_user_id'] ?? 0;
        $this->connection_type = $data['connection_type'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->connection_id;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'connection_id' => $this->connection_id,
            'user_id' => $this->user_id,
            'connected_user_id' => $this->connected_user_id,
            'connection_type' => $this->connection_type,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
