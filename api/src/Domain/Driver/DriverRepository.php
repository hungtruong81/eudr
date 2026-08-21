<?php

declare(strict_types=1);

namespace App\Domain\Driver;

interface DriverRepository
{
    /**
     * @param array $params
     * @return Driver[]
     */
    public function findAll(array $params = []): array;
    /**
     * @return string
     */
    public function generateCode(): string;
    /**
     * @param int $driver_id
     * @return Driver
     * @throws DriverNotFoundException
     */
    public function findDriverOfId(int $driver_id): ?Driver;
    /**
     * @param string $code
     * @return Driver|null
     */
    public function findDriverOfCode(string $code): ?Driver;
    /**
     * @param array $data
     * @return Driver
     */
    public function createDriver(array $data): ?Driver;
    /**
     * @param int $driver_id
     * @param array $data_update
     * @return Driver
     */
    public function updateDriver(int $driver_id, array $data_update): Driver;
    /**
     * @param int $driver_id
     * @param int $deleted_by
     * @return void
     */
    public function deleteDriver(int $driver_id, int $deleted_by): void;

}
