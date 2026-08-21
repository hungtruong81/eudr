<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use JsonSerializable;

class Notification implements JsonSerializable
{
    /**
     * @var int|null
    */
    private $notification_id;
    /**
     * @var date
     */
    private $created_at;

    /**
     * @param int|null  $notification_id
     * @param array    $data
     */
    public function __construct(?int $notification_id, array $data)
    {
        $this->notification_id = $notification_id;
        $this->created_at = $data['created_at'] ?? '';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->notification_id;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'notification_id' => $this->notification_id,
            'created_at' => $this->created_at,
        ];
    }
}
