<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PurchasingOrder\Lifecycle;

use App\Domain\PurchasingOrder\PurchasingOrder;

trait PurchasingOrderLifecycleRepositoryTrait
{
    public function sendOrderWithPermission(
        int $purchase_order_id,
        array $update_data,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null
    ): ?PurchasingOrder {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $order = $this->findOrderOfIdWithPermission(
            $purchase_order_id,
            (int)$authUserId,
            $scope,
            (int)$companyId
        );
        if (empty($order)) {
            return null;
        }
        $orderData = $order->jsonSerialize();
        $toStatus = ($orderData['seller_source_type'] ?? '') === 'vendor'
            ? 'seller_confirmed'
            : 'sent_to_seller';
        $update_data['status'] = $toStatus;
        if ($toStatus === 'seller_confirmed') {
            $update_data['seller_confirmed_at'] = date('Y-m-d H:i:s');
        }
        $this->db->startTransaction();
        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, null, '');
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('status', 'draft');
        $this->db->update('eudr_purchasing_orders', $update_data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->insert('eudr_purchasing_order_status_logs', [
            'purchase_order_id' => $purchase_order_id,
            'from_status' => 'draft',
            'to_status' => $toStatus,
            'actor_user_id' => $authUserId,
            'actor_role' => 'buyer',
            'action_name' => $toStatus === 'seller_confirmed' ? 'vendor_bypass' : 'send',
            'notes' => $update_data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->commit();
        return $this->findOrderOfId($purchase_order_id);
    }

    public function confirmSellerById(
        int $purchase_order_id,
        int $actor_user_id,
        ?string $notes = null
    ): ?PurchasingOrder {
        $order = $this->findOrderOfId($purchase_order_id);
        $orderData = $order?->jsonSerialize() ?? [];
        if (
            empty($order)
            || $order->getStatus() !== 'sent_to_seller'
            || ($orderData['seller_source_type'] ?? '') !== 'system_user'
        ) {
            return null;
        }
        $this->db->startTransaction();
        $sellerAccountType = (string)($orderData['seller_account_type'] ?? '');
        if (in_array($sellerAccountType, ['purchaser', 'trader', 'company'], true)) {
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            if ((int)$this->db->getValue('eudr_purchasing_order_seller_sub_tanks', 'COUNT(*)') === 0) {
                $this->db->rollback();
                return null;
            }
        } elseif ($sellerAccountType === 'farmer') {
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            if ((int)$this->db->getValue('eudr_purchasing_order_lands', 'COUNT(*)') === 0) {
                $this->db->rollback();
                return null;
            }
        }
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->where('status', 'declared');
        $this->db->update('eudr_purchasing_order_seller_sub_tanks', [
            'status' => 'seller_confirmed',
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actor_user_id,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->where('status', 'sent_to_seller');
        $this->db->update('eudr_purchasing_orders', [
            'status' => 'seller_confirmed',
            'seller_confirmed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actor_user_id,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->insert('eudr_purchasing_order_status_logs', [
            'purchase_order_id' => $purchase_order_id,
            'from_status' => 'sent_to_seller',
            'to_status' => 'seller_confirmed',
            'actor_user_id' => $actor_user_id,
            'actor_role' => 'seller',
            'action_name' => 'confirm',
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->commit();
        return $this->findOrderOfId($purchase_order_id);
    }

    public function reconfirmBuyerById(
        int $purchase_order_id,
        int $actor_user_id,
        ?string $notes = null
    ): ?PurchasingOrder {
        $order = $this->findOrderOfId($purchase_order_id);
        if (empty($order) || $order->getStatus() !== 'seller_confirmed') {
            return null;
        }
        $orderData = $order->jsonSerialize();
        if (
            in_array(
                (string)($orderData['seller_account_type'] ?? ''),
                ['purchaser', 'trader', 'company'],
                true
            )
        ) {
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $buyerSubTankCount = (int)$this->db->getValue(
                'eudr_purchasing_order_buyer_sub_tanks',
                'COUNT(*)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $mappingCount = (int)$this->db->getValue(
                'eudr_purchasing_order_buyer_seller_sub_tank_maps',
                'COUNT(*)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $sellerWeight = (float)$this->db->getValue(
                'eudr_purchasing_order_seller_sub_tanks',
                'COALESCE(SUM(filled_weight_kg), 0)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $buyerWeight = (float)$this->db->getValue(
                'eudr_purchasing_order_buyer_sub_tanks',
                'COALESCE(SUM(planned_receive_weight_kg), 0)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $mappedWeight = (float)$this->db->getValue(
                'eudr_purchasing_order_buyer_seller_sub_tank_maps',
                'COALESCE(SUM(planned_transfer_weight_kg), 0)'
            );
            if (
                $buyerSubTankCount === 0
                || $mappingCount === 0
                || abs($sellerWeight - $buyerWeight) > 0.001
                || abs($buyerWeight - $mappedWeight) > 0.001
            ) {
                return null;
            }
        } elseif ((string)($orderData['seller_account_type'] ?? '') === 'farmer') {
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $buyerSubTankCount = (int)$this->db->getValue(
                'eudr_purchasing_order_buyer_sub_tanks',
                'COUNT(*)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $mappingCount = (int)$this->db->getValue(
                'eudr_purchasing_order_buyer_land_maps',
                'COUNT(*)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $landWeight = (float)$this->db->getValue(
                'eudr_purchasing_order_lands',
                'COALESCE(SUM(purchased_weight_kg), 0)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $buyerWeight = (float)$this->db->getValue(
                'eudr_purchasing_order_buyer_sub_tanks',
                'COALESCE(SUM(planned_receive_weight_kg), 0)'
            );
            $this->db->where('purchase_order_id', $purchase_order_id);
            $this->db->where('deleted_by', 0);
            $mappedWeight = (float)$this->db->getValue(
                'eudr_purchasing_order_buyer_land_maps',
                'COALESCE(SUM(planned_receive_weight_kg), 0)'
            );
            if (
                $buyerSubTankCount === 0
                || $mappingCount === 0
                || abs($landWeight - $buyerWeight) > 0.001
                || abs($buyerWeight - $mappedWeight) > 0.001
            ) {
                return null;
            }
        }
        $this->db->startTransaction();
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('deleted_by', 0);
        $this->db->where('status', 'seller_confirmed');
        $this->db->update('eudr_purchasing_orders', [
            'status' => 'buyer_reconfirmed',
            'buyer_reconfirmed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actor_user_id,
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->insert('eudr_purchasing_order_status_logs', [
            'purchase_order_id' => $purchase_order_id,
            'from_status' => 'seller_confirmed',
            'to_status' => 'buyer_reconfirmed',
            'actor_user_id' => $actor_user_id,
            'actor_role' => 'buyer',
            'action_name' => 're-confirm',
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->commit();
        return $this->findOrderOfId($purchase_order_id);
    }

    public function cancelDraftOrderWithPermission(
        int $purchase_order_id,
        array $update_data,
        ?int $auth_user_id,
        string $scope,
        ?int $company_id = null
    ): ?PurchasingOrder {
        $authUserId = $auth_user_id ?? ($this->currentUser->getUserId() ?? 0);
        $companyId = $company_id ?? ($this->currentUser->getCompanyId() ?? 0);
        $order = $this->findOrderOfIdWithPermission(
            $purchase_order_id,
            (int)$authUserId,
            $scope,
            (int)$companyId
        );
        if (empty($order) || $order->getStatus() !== 'draft') {
            return null;
        }
        $this->db->startTransaction();
        $this->scopeWhere($scope, (int)$authUserId, (int)$companyId, null, '');
        $this->db->where('purchase_order_id', $purchase_order_id);
        $this->db->where('status', 'draft');
        $this->db->update('eudr_purchasing_orders', $update_data);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->insert('eudr_purchasing_order_status_logs', [
            'purchase_order_id' => $purchase_order_id,
            'from_status' => 'draft',
            'to_status' => 'cancelled',
            'actor_user_id' => $authUserId,
            'actor_role' => 'buyer',
            'action_name' => 'cancel',
            'notes' => $update_data['cancel_reason'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->getLastErrno() !== 0) {
            $this->db->rollback();
            return null;
        }
        $this->db->commit();
        return $this->findOrderOfId($purchase_order_id);
    }
}
