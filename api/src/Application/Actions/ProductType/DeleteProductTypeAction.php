<?php

declare(strict_types=1);

namespace App\Application\Actions\ProductType;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;


class DeleteProductTypeAction extends ProductTypeAction
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

        // Check permission to delete product types
        $productTypeScope = Utils::resolveScope($permissions, 'product_type', 'delete');
        if (empty($productTypeScope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $product_type_code = addslashes(trim((string)$this->resolveArg('code')));

        $product_type = $this->productTypeRepository->findProductTypeOfCodeWithPermission(
            $product_type_code,
            (int)$this->auth_data['user_id'],
            (string)$productTypeScope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($product_type)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy loại sản phẩm");
        }

        // Delete product type
        $this->productTypeRepository->deleteProductTypeWithPermission(
            $product_type->getId(),
            $this->auth_data['user_id'],
            (int)$this->auth_data['user_id'],
            (string)$productTypeScope,
            $this->auth_data['company_id'] ?? null
        );
        
        $action = 'delete';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'product_type',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$product_type->getId(),
        );
        
        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }
}
