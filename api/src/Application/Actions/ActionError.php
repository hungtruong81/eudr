<?php

declare(strict_types=1);

namespace App\Application\Actions;

use JsonSerializable;

class ActionError implements JsonSerializable
{
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
    public const NOT_ALLOWED = 'NOT_ALLOWED';
    public const NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const RESOURCE_ERROR = 'RESOURCE_ERROR';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const UNAUTHENTICATED = 'UNAUTHENTICATED';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const VERIFICATION_ERROR = 'VERIFICATION_ERROR';
    public const API_ERROR = 'API_ERROR';

    /**
     * @var string
     */
    private $type;
    /**
     * @var int
     */
    private $code;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $file;

    /**
    * @var int
    */
    private $line;
    /**
    * @var int
    */
    private $traceId;

    /**
     * @param string        $type
     * @param string|null   $description
     * @param int           $code
     */
    public function __construct(string $type, ?string $description, int $code=0)
    {
        $this->type = $type;
        $this->description = $description;
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    /**
     * @return string
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description = null): self
    {
        $this->description = $description;
        return $this;
    }
    /**
         * @param string|null $description
         * @return self
         */
    public function setFile(?string $file = null): self
    {
        $this->file = $file;
        return $this;
    }
    /**
         * @param int|null $line
         * @return self
         */
    public function setLine(?int $line = null): self
    {
        $this->line = $line;
        return $this;
    }
    /**
         * @param int|null $line
         * @return self
         */
    public function setTraceId(?int $id = null): self
    {
        $this->traceId = $id;
        return $this;
    }


    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $payload = [
            'type' => $this->type,
            'description' => $this->description,
        ];
        if ($this->code) {
            $payload['code'] = $this->code;
        }
        if ($this->line) {
            $payload['line'] = $this->line;
        }
        if ($this->file) {
            $payload['file'] = $this->file;
        }
        if ($this->traceId) {
            $payload['traceId'] = $this->traceId;
        }

        return $payload;
    }
}
