<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingFactoryReceipt;

use App\Application\Actions\Action;
use App\Application\Utility\Utils;
use App\Domain\PurchasingFactoryReceipt\PurchasingFactoryReceipt;
use App\Domain\PurchasingFactoryReceipt\PurchasingFactoryReceiptRepository;
use App\Domain\PurchasingTransport\PurchasingTransport;
use App\Domain\PurchasingTransport\PurchasingTransportRepository;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

abstract class PurchasingFactoryReceiptAction extends Action
{
    protected UserRepository $userRepository;
    protected PurchasingTransportRepository $purchasingTransportRepository;
    protected PurchasingFactoryReceiptRepository $purchasingFactoryReceiptRepository;

    /**
     * @param LoggerInterface $logger
     * @param UserRepository $userRepository
     * @param PurchasingTransportRepository $purchasingTransportRepository
     * @param PurchasingFactoryReceiptRepository $purchasingFactoryReceiptRepository
     */
    public function __construct(
        LoggerInterface $logger,
        UserRepository $userRepository,
        PurchasingTransportRepository $purchasingTransportRepository,
        PurchasingFactoryReceiptRepository $purchasingFactoryReceiptRepository,
    ) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->purchasingTransportRepository = $purchasingTransportRepository;
        $this->purchasingFactoryReceiptRepository = $purchasingFactoryReceiptRepository;
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
     * @param string $scope
     * @return PurchasingFactoryReceipt
     * @throws HttpBadRequestException if receiptCode is missing or invalid
     * @throws HttpNotFoundException if the receipt is not found
     * @throws HttpForbiddenException if the user does not have permission to access the receipt
     */
    protected function receipt(string $scope): PurchasingFactoryReceipt
    {
        $code = trim((string)$this->resolveArg('receiptCode'));
        if ($code === '') {
            throw new HttpBadRequestException($this->request, 'receiptCode không hợp lệ');
        }
        $receipt = $this->purchasingFactoryReceiptRepository->findByCode($code, $this->companyId());
        if ($receipt === null) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu nhập nhà máy');
        }
        if ($scope === 'self' && (int)($receipt->jsonSerialize()['created_by'] ?? 0) !== $this->userId()) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }
        return $receipt;
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
