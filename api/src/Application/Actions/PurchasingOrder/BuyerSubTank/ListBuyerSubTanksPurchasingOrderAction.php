<?php

declare(strict_types=1);

namespace App\Application\Actions\PurchasingOrder\BuyerSubTank;

use App\Application\Actions\PurchasingOrder\PurchasingOrderAction;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ListBuyerSubTanksPurchasingOrderAction extends PurchasingOrderAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'purchasing_order', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $orderCode = addslashes(trim((string)$this->resolveArg('code')));
        $order = $this->purchasingOrderRepository->findOrderOfCode($orderCode);
        if (empty($order)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu thu mua');
        }
        $purchaseOrderId = (int)$order->getId();

        $orderData = $order->jsonSerialize();
        $this->assertBuyerOrSellerAccess($orderData, (string)$scope);

        $query = $this->request->getQueryParams();
        $validator = new Validator($this->request);
        $validator->validate('page', $query['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $query['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('status', $query['status'] ?? null, 'string|in:assigned,receiving,received,transferred,cancelled,all');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($query, [
            'page' => 'integer',
            'limit' => 'integer',
            'status' => 'string',
        ]);

        $data = $this->purchasingOrderRepository->listBuyerSubTanksByOrderId(
            $purchaseOrderId,
            [
                'page' => (int)$clean['page'],
                'page_limit' => (int)$clean['limit'],
                'status' => $clean['status'] ?? 'all',
            ]
        );

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'purchase_order_id' => $purchaseOrderId,
            'data' => $data,
        ]);
    }

    /**
     * @param array<string,mixed> $orderData
     */
    private function assertBuyerOrSellerAccess(array $orderData, string $scope): void
    {
        if ($scope === 'all') {
            return;
        }

        $authUserId = (int)($this->auth_data['user_id'] ?? 0);
        $authCompanyId = (int)($this->auth_data['company_id'] ?? 0);

        $buyerUserId = (int)($orderData['buyer_user_id'] ?? 0);
        $buyerCompanyId = (int)($orderData['buyer_company_id'] ?? 0);
        $orderCompanyId = (int)($orderData['company_id'] ?? 0);

        $sellerUserId = (int)($orderData['seller_user_id'] ?? 0);
        $sellerCompanyId = (int)($orderData['seller_company_id'] ?? 0);

        $isBuyerUser = $buyerUserId > 0 && $buyerUserId === $authUserId;
        $isSellerUser = $sellerUserId > 0 && $sellerUserId === $authUserId;
        $isBuyerCompany = $buyerCompanyId > 0 && $buyerCompanyId === $authCompanyId;
        $isSellerCompany = $sellerCompanyId > 0 && $sellerCompanyId === $authCompanyId;
        $isOrderCompany = $orderCompanyId > 0 && $orderCompanyId === $authCompanyId;

        if ($scope === 'self' && ($isBuyerUser || $isSellerUser)) {
            return;
        }

        if ($scope === 'own' && ($isBuyerCompany || $isSellerCompany || $isOrderCompany || $isBuyerUser || $isSellerUser)) {
            return;
        }

        throw new HttpForbiddenException($this->request, 'Bạn không phải buyer/seller của phiếu này');
    }
}
