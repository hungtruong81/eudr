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


class ViewTransactionTicketAction extends TransactionTicketAction
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

        $transaction_ticket_code = addslashes(trim((string)$this->resolveArg('code')));

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);
        $validator->validate('transaction_ticket_type', $formData['transaction_ticket_type'] ?? null, 'required|in:purchase,sale');

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
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $transaction_ticket_type = $cleanData['transaction_ticket_type'];
        // Check permission
        if($transaction_ticket_type === 'purchase') {
            $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'transaction_ticket.purchase', 'view');
        } else {
            $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'transaction_ticket.sale', 'view');
        }
        
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $transaction_ticket = $this->transactionTicketRepository->findTransactionTicketOfCode($transaction_ticket_code);
        if (empty($transaction_ticket)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu giao dịch");
        }

        $action = 'view';
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
        $res_return['data'] = $transaction_ticket->jsonSerialize();
        

        return $this->respondWithData($res_return);
    }
}
