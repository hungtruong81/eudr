<?php

declare(strict_types=1);

namespace App\Application\Actions\TransactionTicket;

use App\Application\Utility\Utils;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class ViewTransactionTicketByContractCodeAction extends TransactionTicketAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $contract_code = addslashes(trim((string)$this->resolveArg('contract_code')));
        if (empty($contract_code)) {
            throw new HttpBadRequestException($this->request, 'Thiếu mã hợp đồng');
        }

        $transaction_ticket = $this->transactionTicketRepository->findTransactionTicketOfContractCode($contract_code);
        if (empty($transaction_ticket)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy phiếu giao dịch');
        }

        $transactionType = $transaction_ticket->getType();

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $purchase_scope = Utils::resolveScope($permissions, 'transaction_ticket.purchase', 'view');
        $sale_scope = Utils::resolveScope($permissions, 'transaction_ticket.sale', 'view');

        if (empty($purchase_scope) && empty($sale_scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $scope = 'self';
        if ($purchase_scope === 'own' || $sale_scope === 'own') {
            $scope = 'own';
        }
        if ($purchase_scope === 'all' || $sale_scope === 'all') {
            $scope = 'all';
        }

        if ($scope === 'self') {
            if ($transaction_ticket->getBuyerUserId() != (int)$this->auth_data['user_id'] &&
                $transaction_ticket->getSellerUserId() != (int)$this->auth_data['user_id']) {
                throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
            }

        }
        if ($scope === 'own') {
            if ($transaction_ticket->getBuyerCompanyId() != (int)$this->auth_data['company_id'] &&
                $transaction_ticket->getSellerCompanyId() != (int)$this->auth_data['company_id']) {
                throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
            }
        }
        
        $action = 'view';
        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'transaction_ticket',
            'action' => $action,
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$transaction_ticket->getId(),
        ];

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['data'] = $transaction_ticket->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}