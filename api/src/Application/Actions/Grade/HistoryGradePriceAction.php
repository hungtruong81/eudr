<?php

declare(strict_types=1);

namespace App\Application\Actions\Grade;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class HistoryGradePriceAction extends GradeAction
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

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        /*
        $scope = Utils::resolveScope($permissions, 'grade', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }
        */
        $scope = "all"; // Allow all scopes to view list of grades

        $grade_code = addslashes(trim((string)$this->resolveArg('code')));
        $grade = $this->gradeRepository->findGradeOfCodeWithPermission(
            $grade_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($grade)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy grade');
        }

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);
        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('effective_from', $formData['effective_from'] ?? null, 'date');
        $validator->validate('effective_to', $formData['effective_to'] ?? null, 'date');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $params = [
            'page' => (int)$formData['page'],
            'page_limit' => (int)$formData['limit'],
            'effective_from' => !empty($formData['effective_from']) ? date('Y-m-d H:i:s', strtotime((string)$formData['effective_from'])) : null,
            'effective_to' => !empty($formData['effective_to']) ? date('Y-m-d H:i:s', strtotime((string)$formData['effective_to'])) : null,
        ];

        $history = $this->gradeRepository->getPriceHistoryOfGrade((int)$grade->getId(), $params);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $history,
        ]);
    }
}
