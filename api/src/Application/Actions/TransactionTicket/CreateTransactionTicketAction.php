<?php

declare(strict_types=1);

namespace App\Application\Actions\TransactionTicket;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class CreateTransactionTicketAction extends TransactionTicketAction
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

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('transaction_ticket_type', $formData['transaction_ticket_type'] ?? null, 'required|in:purchase,sale');
        // $validator->validate('connection_id', $formData['connection_id'] ?? null, 'required|integer');
        $validator->validate('buyer_user_id', $formData['buyer_user_id'] ?? null, 'required|integer');
        $validator->validate('buyer_name', $formData['buyer_name'] ?? null, 'required|string');
        $validator->validate('buyer_phone', $formData['buyer_phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
        $validator->validate('buyer_account_type', $formData['buyer_account_type'] ?? null, 'required|in:purchaser,trader,company');
        $validator->validate('buyer_address', $formData['buyer_address'] ?? null, 'string');
        $validator->validate('seller_user_id', $formData['seller_user_id'] ?? null, 'required|integer');
        $validator->validate('seller_name', $formData['seller_name'] ?? null, 'required|string');
        $validator->validate('seller_phone', $formData['seller_phone'] ?? null, ['required', 'regex:/^0[0-9]{9}$/']);
        $validator->validate('seller_account_type', $formData['seller_account_type'] ?? null, 'required|in:farmer,purchaser,trader,company');
        $validator->validate('seller_address', $formData['seller_address'] ?? null, 'string');
        $validator->validate('latex_weight', $formData['latex_weight'] ?? null, 'required|numeric|min:0');
        $validator->validate('latex_tsc_grade', $formData['latex_tsc_grade'] ?? null, 'required|numeric|min:0');
        $validator->validate('latex_price_per_tsc', $formData['latex_price_per_tsc'] ?? null, 'required|integer|min:0');
        $validator->validate('latex_total_amount', $formData['latex_total_amount'] ?? null, 'required|integer|min:0');
        $validator->validate('latex_notes', $formData['latex_notes'] ?? null, 'string');
        $validator->validate('scrap_rubber_weight', $formData['scrap_rubber_weight'] ?? null, 'required|numeric|min:0');
        //$validator->validate('scrap_rubber_drc_grade', $formData['scrap_rubber_drc_grade'] ?? null, 'numeric|min:0');
        $validator->validate('scrap_rubber_price_per_drc', $formData['scrap_rubber_price_per_drc'] ?? null, 'required|integer|min:0');
        $validator->validate('scrap_rubber_total_amount', $formData['scrap_rubber_total_amount'] ?? null, 'required|integer|min:0');
        $validator->validate('scrap_rubber_notes', $formData['scrap_rubber_notes'] ?? null, 'string');
        $validator->validate('payment_terms', $formData['payment_terms'] ?? null, 'string');
        $validator->validate('delivery_terms', $formData['delivery_terms'] ?? null, 'string');
        $validator->validate('plot_ids', $formData['plot_ids'] ?? null, 'array');
        $validator->validate('purchase_ticket_ids', $formData['purchase_ticket_ids'] ?? null, 'array');

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
            // 'connection_id' => 'integer',
            'buyer_user_id' => 'integer',
            'buyer_name' => 'string',
            'buyer_phone' => 'string',
            'buyer_account_type' => 'string',
            'buyer_address' => 'string',
            'seller_user_id' => 'integer',
            'seller_name' => 'string',
            'seller_phone' => 'string',
            'seller_account_type' => 'string',
            'seller_address' => 'string',
            'latex_weight' => 'float',
            'latex_tsc_grade' => 'float',
            'latex_price_per_tsc' => 'integer',
            'latex_total_amount' => 'integer',
            'latex_notes' => 'string',
            'scrap_rubber_weight' => 'float',
            //'scrap_rubber_drc_grade' => 'float',
            'scrap_rubber_price_per_drc' => 'integer',
            'scrap_rubber_total_amount' => 'integer',
            'scrap_rubber_notes' => 'string',
            'payment_terms' => 'string',
            'delivery_terms' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $transaction_ticket_type = $cleanData['transaction_ticket_type'];
        // $connection_id = $cleanData['connection_id'];
        $buyer_user_id = $cleanData['buyer_user_id'];
        $buyer_name = $cleanData['buyer_name'];
        $buyer_phone = $cleanData['buyer_phone'];
        $buyer_account_type = $cleanData['buyer_account_type'];
        $buyer_address = $cleanData['buyer_address'] ?? '';
        $seller_user_id = $cleanData['seller_user_id'];
        $seller_name = $cleanData['seller_name'];
        $seller_phone = $cleanData['seller_phone'];
        $seller_account_type = $cleanData['seller_account_type'];
        $seller_address = $cleanData['seller_address'] ?? '';
        $latex_weight = $cleanData['latex_weight'] ?? 0;
        $latex_tsc_grade = $cleanData['latex_tsc_grade'];
        $latex_price_per_tsc = $cleanData['latex_price_per_tsc'];
        $latex_total_amount = $cleanData['latex_total_amount'];
        $latex_notes = $cleanData['latex_notes'] ?? '';
        $scrap_rubber_weight = $cleanData['scrap_rubber_weight'] ?? 0;
        //$scrap_rubber_drc_grade = $cleanData['scrap_rubber_drc_grade'];
        $scrap_rubber_price_per_drc = $cleanData['scrap_rubber_price_per_drc'];
        $scrap_rubber_total_amount = $cleanData['scrap_rubber_total_amount'];
        $scrap_rubber_notes = $cleanData['scrap_rubber_notes'] ?? '';
        $payment_terms = $cleanData['payment_terms'] ?? '';
        $delivery_terms = $cleanData['delivery_terms'] ?? '';
        $plot_ids = $formData['plot_ids'] ?? [];
        $purchase_ticket_ids = $formData['purchase_ticket_ids'] ?? [];

        // Check permission
        if($transaction_ticket_type === 'purchase') {
            $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'transaction_ticket.purchase', 'create');
        } else {
            $scope = $this->userRepository->getCURDPermissionStatus($this->auth_data['user_id'], 'transaction_ticket.sale', 'create');
        }
        
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $isSelfTransaction = ($buyer_user_id === $seller_user_id);

        if ($isSelfTransaction) {
            // Giao dịch tự mua bán: validate user có quyền cả mua và bán
            $hasBuyPermission = $this->userRepository->userHasPermission($buyer_user_id, 'transaction_ticket.purchase.create');
            $hasSellPermission = $this->userRepository->userHasPermission($buyer_user_id, 'transaction_ticket.sale.create');
            if (!$hasBuyPermission || !$hasSellPermission) {
                throw new HttpBadRequestException(
                    $this->request,
                    "Tài khoản không có đủ quyền để tự giao dịch (cần quyền tạo phiếu mua và phiếu bán)"
                );
            }
        } else {

            // Cần có connection hợp lệ
            $data_connection = $this->connectionRepository->findConnectionBetweenUsers($buyer_user_id, $seller_user_id, 'accepted');
            if (empty($data_connection)) {
                throw new HttpBadRequestException($this->request, "Kết nối giữa người mua và người bán không hợp lệ");
            }

            // Giao dịch giữa 2 user khác nhau
            $buyerHasPermission = $this->userRepository->userHasPermission($buyer_user_id, 'transaction_ticket.purchase.create');
            if (!$buyerHasPermission) {
                throw new HttpBadRequestException(
                    $this->request,
                    "Tài khoản người mua không có quyền tạo phiếu giao dịch mua"
                );
            }

            // Kiểm tra seller có quyền tạo phiếu bán
            $sellerHasPermission = $this->userRepository->userHasPermission($seller_user_id, 'transaction_ticket.sale.create');
            if (!$sellerHasPermission) {
                throw new HttpBadRequestException(
                    $this->request,
                    "Tài khoản người bán không có quyền tạo phiếu giao dịch bán"
                );
            }
        }

        // Validate buyer and seller users
        $buyer_user = $this->userRepository->findUserOfId($buyer_user_id);
        if (empty($buyer_user)) {
            throw new HttpBadRequestException($this->request, "Tài khoản người mua không hợp lệ");
        }

        $seller_user = $this->userRepository->findUserOfId($seller_user_id);
        if (empty($seller_user)) {
            throw new HttpBadRequestException($this->request, "Tài khoản người bán không hợp lệ");
        }



        // Validate buyer and seller account types

        /*
        $isValidTransaction = Utils::isValidTransaction($buyer_account_type, $seller_account_type);
        if (!$isValidTransaction) {
            throw new HttpBadRequestException($this->request, "Loại tài khoản người mua và người bán không hợp lệ");
        }
        */

        // If seller is farmer, plot_ids should be provided
        /*
        if($seller_account_type == 'farmer') {
            if(empty($plot_ids)) {
                throw new HttpBadRequestException($this->request, "Vui lòng chọn ít nhất 1 vườn của Nông hộ.");
            }
            if (!is_array($plot_ids)) {
                throw new HttpBadRequestException($this->request, "Danh sách vườn không hợp lệ");
            }
            $data_plots = $this->landRepository->findLandIdsOfOwner($plot_ids, $seller_user_id);
            if (count($data_plots) != count($plot_ids)) {
                throw new HttpBadRequestException($this->request, "Danh sách vườn không hợp lệ");
            }
        }
        */

        // If seller is purchaser/trader/company, plot_ids should be empty and purchase_ticket_ids should be provided

        /*
        if (in_array($seller_account_type, ['purchaser', 'trader', 'company']) 
            //&& $transaction_ticket_type == 'sale'
            && $seller_user_id == $this->auth_data['user_id']) {
            if (!empty($plot_ids)) {
                throw new HttpBadRequestException($this->request, "Người bán không phải Nông hộ, không được chọn vườn");
            }
            if (empty($purchase_ticket_ids)) {
                throw new HttpBadRequestException($this->request, "Vui lòng chọn ít nhất 1 phiếu mua");
            }
            if (!is_array($purchase_ticket_ids)) {
                throw new HttpBadRequestException($this->request, "Danh sách phiếu mua không hợp lệ");
            }
            // Validate purchase tickets
            $data_purchase_tickets = $this->transactionTicketRepository->findPurchaseTicketsByIds($purchase_ticket_ids, $seller_user_id);
            if (count($data_purchase_tickets) != count($purchase_ticket_ids)) {
                throw new HttpBadRequestException($this->request, "Danh sách phiếu mua không hợp lệ");
            }
            // Validate total weight
            $total_weight = $latex_weight + $scrap_rubber_weight;
            $total_weight_by_ticket = $this->transactionTicketRepository->sumWeightOfTransactionTickets($purchase_ticket_ids);
            if($total_weight > $total_weight_by_ticket || $total_weight_by_ticket <= 0) {
                throw new HttpBadRequestException($this->request, "Tổng khối lượng trên phiếu mua không đủ để tạo phiếu giao dịch.");
            }
        }
        */
        
        // Create transaction ticket code
        $transaction_ticket_code = $this->transactionTicketRepository->generateCode();

        // Transaction ticket data
        $data_update = [
            "transaction_ticket_code" => $transaction_ticket_code,
            "transaction_ticket_type" => $transaction_ticket_type,
            "connection_id" => $data_connection['connection_id'] ?? 0,
            "buyer_company_id" => $buyer_user->getCompanyId() ?? 0,
            "buyer_user_id" => $buyer_user_id,
            "buyer_name" => $buyer_name,
            "buyer_phone" => $buyer_phone,
            "buyer_account_type" => $buyer_account_type,
            "buyer_address" => $buyer_address,
            "seller_company_id" => $seller_user->getCompanyId() ?? 0,
            "seller_user_id" => $seller_user_id,
            "seller_name" => $seller_name,
            "seller_phone" => $seller_phone,
            "seller_account_type" => $seller_account_type,
            "seller_address" => $seller_address,
            "latex_weight" => $latex_weight,
            "latex_tsc_grade" => $latex_tsc_grade,
            "latex_price_per_tsc" => $latex_price_per_tsc,
            "latex_total_amount" => $latex_total_amount,
            "latex_notes" => $latex_notes,
            "scrap_rubber_weight" => $scrap_rubber_weight,
            //"scrap_rubber_drc_grade" => $scrap_rubber_drc_grade,
            "scrap_rubber_price_per_drc" => $scrap_rubber_price_per_drc,
            "scrap_rubber_total_amount" => $scrap_rubber_total_amount,
            "scrap_rubber_notes" => $scrap_rubber_notes,
            "payment_terms" => $payment_terms,
            "delivery_terms" => $delivery_terms,
            "status" => $isSelfTransaction ? 'completed' : 'pending',
            "created_by" => $this->auth_data['user_id'],
            "created_at" => date("Y-m-d H:i:s"),
            "sent_at" => date("Y-m-d H:i:s"),
            "plot_ids" => $plot_ids,
            "purchase_ticket_ids" => $purchase_ticket_ids
        ];
        // If purchase ticket and seller is Farmer, then status is auto completed
        /*
        if($transaction_ticket_type == 'purchase' && $seller_account_type == 'farmer') {
            $data_update['status'] = 'completed';
            
        }
        */
        $transaction_ticket = $this->transactionTicketRepository->createTransactionTicket($data_update);
        
        // Giao dịch với người khác: gửi thông báo cho đối tác
        // Giao dịch tự mua bán: không cần thông báo (đã auto-complete)
        if (!$isSelfTransaction) {
            $data_add_notification = [
                'user_id' => $transaction_ticket->getTargetUserId($this->auth_data['user_id']),
                'title' => "Yêu cầu xác nhận phiếu giao dịch mới",
                'type' => 'create',
                'message' => "Bạn có một yêu cầu xác nhận phiếu giao dịch mới từ " . $this->auth_data['full_name'],
                'related_id' => $transaction_ticket->getId(),
                'related_code' => $transaction_ticket->getCode(),
                'related_type' => 'transaction_ticket',
            ];
            
            $this->notificationRepository->createNotification($data_add_notification);
        }
        
        // Log action
        $action = 'create';
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
        $res_return['transaction_ticket'] = $transaction_ticket->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
