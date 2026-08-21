<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductTank;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;

class DeleteProductTankAction extends ProductTankAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);
        
        // Check user authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to delete product tanks
        $scope = Utils::resolveScope($permissions, 'product_tank', 'delete');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $product_tank_code = addslashes(trim((string)$this->resolveArg('code')));

        $product_tank = $this->productTankRepository->findProductTankOfCodeWithPermission(
            $product_tank_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($product_tank)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy bồn chứa thành phẩm");
        }

        // Validate volume in tank
        if ($product_tank->getCurrentVolume() > 0) {
            throw new HttpBadRequestException($this->request, "Không thể xóa bồn chứa thành phẩm khi còn sản phẩm.");
        }

        // Delete product tank
        $this->productTankRepository->deleteProductTankWithPermission(
            $product_tank->getId(),
            $this->auth_data['user_id'],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_tank',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$product_tank->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
