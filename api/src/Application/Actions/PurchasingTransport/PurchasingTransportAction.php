<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingTransport;

use App\Application\Actions\Action;
use App\Application\Utility\Utils;
use App\Domain\PurchasingOrder\PurchasingOrderRepository;
use App\Domain\PurchasingTransport\PurchasingTransport;
use App\Domain\PurchasingTransport\PurchasingTransportRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

abstract class PurchasingTransportAction extends Action
{
    protected UserRepository $userRepository;
    protected PurchasingOrderRepository $purchasingOrderRepository;
    protected PurchasingTransportRepository $purchasingTransportRepository;

    /**
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     * @param PurchasingOrderRepository $purchasingOrderRepository
     * @param PurchasingTransportRepository $purchasingTransportRepository
     */
    public function __construct(
        LoggerInterface $logger,
        UserRepository $userRepository,
        PurchasingOrderRepository $purchasingOrderRepository,
        PurchasingTransportRepository $purchasingTransportRepository,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->purchasingOrderRepository = $purchasingOrderRepository;
        $this->purchasingTransportRepository = $purchasingTransportRepository;
    }

    /**
     * @return int
     * @throws HttpUnauthorizedException if user_id is missing or invalid
     */
    protected function userId(): int
    {
        $userId = (int)($this->auth_data['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }
        return $userId;
    }

    /**
     * @return int
     * @throws HttpForbiddenException if company_id is missing or invalid
     */
    protected function companyId(): int
    {
        $companyId = (int)($this->auth_data['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new HttpForbiddenException($this->request, 'Tài khoản chưa thuộc công ty hợp lệ');
        }
        return $companyId;
    }

    /**
     * @param string $action
     * @return string
     * @throws HttpForbiddenException if the user does not have permission for the action
     */
    protected function scope(string $action): string
    {
        $scope = Utils::resolveScope(
            $this->userRepository->getUserPermissions($this->userId()),
            'purchasing_order',
            $action
        );
        if ($scope === '') {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }
        return $scope;
    }

    /**
     * @param string $scope
     * @return PurchasingTransport
     * @throws HttpBadRequestException if transportCode is missing or invalid
     * @throws HttpNotFoundException if the transport is not found
     * @throws HttpForbiddenException if the user does not have permission to access the transport
     */
    protected function transport(string $scope): PurchasingTransport
    {
        $code = trim((string)$this->resolveArg('transportCode'));
        if ($code === '') {
            throw new HttpBadRequestException($this->request, 'transportCode không hợp lệ');
        }
        $transport = $this->purchasingTransportRepository->findByCode($code, $this->companyId());
        if ($transport === null) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy chuyến xe thu mua');
        }
        if ($scope === 'self' && (int)($transport->jsonSerialize()['created_by'] ?? 0) !== $this->userId()) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }
        return $transport;
    }

    /**
     * @param array<string, mixed> $errors
     * @return string
     */
    protected function errorMessages(array $errors): string
    {
        $messages = [];
        foreach ($errors as $fieldErrors) {
            $messages[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
        }
        return implode('; ', $messages);
    }
}
