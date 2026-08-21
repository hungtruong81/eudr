<?php

declare(strict_types=1);

namespace App\Application\Actions\TransactionTicket;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class TransactionTicketCancelAction extends TransactionTicketAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $validator = new Validator($this->request);

        $formData = $this->getFormData();

        $validator->validate('transaction_ticket_type', $formData['transaction_ticket_type'] ?? null, 'required|in:purchase,sale');
        $validator->validate('transaction_ticket_code', $formData['transaction_ticket_code'] ?? null, 'required|string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'transaction_ticket_type' => 'string',
            'transaction_ticket_code' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $transaction_ticket_type = $cleanData['transaction_ticket_type'];
        $transaction_ticket_code = $cleanData['transaction_ticket_code'];

        // Check permission
        if($transaction_ticket_type === 'purchase') {
            $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'transaction_ticket.purchase', 'update');
        } else {
            $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'transaction_ticket.sale', 'update');
        }
        
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $transaction_ticket = $this->transactionTicketRepository->findTransactionTicketOfCode($transaction_ticket_code);

        if (empty($transaction_ticket)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu giao dịch");
        }

        $isSelfTransaction = ($transaction_ticket->getBuyerUserId() === $transaction_ticket->getSellerUserId());

        if ($transaction_ticket->getStatus() === 'cancelled') {
            throw new HttpBadRequestException($this->request, "Phiếu giao dịch đã được hủy trước đó.");
        }

        if ($transaction_ticket->getStatus() === 'completed' && !$isSelfTransaction) {
            throw new HttpBadRequestException($this->request, "Phiếu giao dịch đã được xác nhận từ đối tác nên không thể hủy.");
        }

        $data_update = [
            "status" => "cancelled",
            "updated_at" => date("Y-m-d H:i:s"),
            "updated_by" => $this->auth_data['user_id']
        ];
        $this->transactionTicketRepository->updateTransactionTicket($transaction_ticket->getId(), $data_update);

        // Gửi thông báo cho đối tác (bỏ qua nếu giao dịch tự mua bán)
        if (!$isSelfTransaction) {
            $data_add_notification = [
                'user_id' => $transaction_ticket->getTargetUserId($this->auth_data['user_id']),
                'title' => "Hủy phiếu giao dịch",
                'type' => 'cancel',
                'message' => "Thông báo hủy phiếu giao dịch từ " . $this->auth_data['full_name'],
                'related_id' => $transaction_ticket->getId(),
                'related_code' => $transaction_ticket->getCode(),
                'related_type' => 'transaction_ticket',
            ];
            
            $this->notificationRepository->createNotification($data_add_notification);
        }

        $action = 'cancel';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'transaction_ticket',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$transaction_ticket->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
        
    }
}
