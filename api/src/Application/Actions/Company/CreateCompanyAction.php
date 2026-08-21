<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use App\Application\Utility\CompanyGroupDefault;


class CreateCompanyAction extends CompanyAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to create companys
        $scope = Utils::resolveScope($permissions, 'company', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('company_name', $formData['company_name'] ?? null, 'required|string|max:255');
        $validator->validate('short_name', $formData['short_name'] ?? null, 'string|max:50');
        $validator->validate('tax_code', $formData['tax_code'] ?? null, 'integer');
        $validator->validate('address', $formData['address'] ?? null, 'string');
        $validator->validate('website', $formData['website'] ?? null, 'url|max:255');

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
            'company_name' => 'string',
            'short_name' => 'string',
            'tax_code' => 'string',
            'address' => 'string',
            'website' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $company_name = $cleanData['company_name'];
        $short_name = $cleanData['short_name'] ?? '';
        $tax_code = $cleanData['tax_code'] ?? '';
        $website = $cleanData['website'] ?? '';
        $address = $cleanData['address'] ?? '';

        // Create code
        $company_code = $this->companyRepository->generateCode();

        // Data Create
        $data_update = [
            "company_name" => $company_name,
            "company_code" => $company_code,
            "short_name" => $short_name,
            "tax_code" => $tax_code,
            "website" => $website,
            "address" => $address,
            "status" => 'active',
            "created_at" => date("Y-m-d H:i:s", time()),
            "created_by" => $this->auth_data['user_id'],
        ];

        $company = $this->companyRepository->createCompany($data_update);
        if (!$company) {
            throw new HttpBadRequestException($this->request, "Lỗi khi tạo công ty");
        }

        // Get default company groups and permissions
        $farmerPermissions = CompanyGroupDefault::getPermissionOfFarmer();
        $this->companyGroupRepository->createCompanyGroupDefault($farmerPermissions, $company->getId());
        $purchaserPermissions = CompanyGroupDefault::getPermissionOfPurchaser();
        $this->companyGroupRepository->createCompanyGroupDefault($purchaserPermissions, $company->getId());
        $traderPermissions = CompanyGroupDefault::getPermissionOfTrader();
        $this->companyGroupRepository->createCompanyGroupDefault($traderPermissions, $company->getId());
        $companyPermissions = CompanyGroupDefault::getPermissionOfCompany();
        $this->companyGroupRepository->createCompanyGroupDefault($companyPermissions, $company->getId());

        //$inspectorPermissions = CompanyGroupDefault::getPermissionOfInspector();
        //$this->companyGroupRepository->createCompanyGroupDefault($inspectorPermissions, $company->getId());
        

        $action = 'create';
        $log = array(
            "milliseconds" => floor(microtime(true) * 1000),
            "trace_id" => $trace_id,
            "log_type" => 'company',
            "action" => $action,
            "user_id" => (string)$this->auth_data['user_id'],
            "extra_1" => (string)$company->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ["result" => "success"];
        $res_return['trace_id'] = $trace_id;
        $res_return['company'] = $company->jsonSerialize();

        return $this->respondWithData($res_return);
        
    }
}
