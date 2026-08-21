<?php

declare(strict_types=1);

namespace App\Application\Actions\Grade;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class CreateGradeAction extends GradeAction
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
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to create grade
        $scope = Utils::resolveScope($permissions, 'grade', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $formData = $this->getFormData();

        $validator = new Validator($this->request);

        $validator->validate('name', $formData['name'] ?? null, 'required|string');
        $validator->validate('grade_code', $formData['grade_code'] ?? null, 'string');
        $validator->validate('description', $formData['description'] ?? null, 'string');

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
            'name' => 'string',
            'grade_code' => 'string',
            'description' => 'string',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $name = $cleanData['name'];
        $description = $cleanData['description'] ?? '';
        $grade_code = trim((string)($cleanData['grade_code'] ?? ''));
        if ($grade_code === '') {
            $grade_code = $this->gradeRepository->generateCode();
        }

        $existingGrade = $this->gradeRepository->findGradeOfCode($grade_code);
        if ($existingGrade) {
            throw new HttpBadRequestException($this->request, 'Mã grade đã tồn tại');
        }

        $existingGradeByName = $this->gradeRepository->findGradeOfName($name);
        if ($existingGradeByName) {
            throw new HttpBadRequestException($this->request, 'Tên grade đã tồn tại');
        }

        // Data Grade
        $data_update = [
            'grade_code' => $grade_code,
            'name' => $name,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => $this->auth_data['user_id'],
        ];

        $grade = $this->gradeRepository->createGrade($data_update);

        if (empty($grade)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo grade');
        }

        $action = 'create';
        $log = array(
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'grade',
            'action' => $action,
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$grade->getId(),
        );

        Utils::save_log($this->logger, $log);

        $res_return = ['result' => 'success'];
        $res_return['trace_id'] = $trace_id;
        $res_return['grade'] = $grade->jsonSerialize();

        return $this->respondWithData($res_return);
    }
}
