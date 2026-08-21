<?php

declare(strict_types=1);

namespace App\Application\Utility;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Validation class with multi-language support
 */
class Validator {
    
    private $errors = [];
    private $lang = 'vi'; // Default language
    private $arr_lang = ['vi', 'en'];
    
    /**
     * Constructor
     * @param ServerRequestInterface|string|null $request_or_lang Request object or language code ('vi' or 'en')
     */
    public function __construct($request_or_lang = 'vi') {
        if ($request_or_lang instanceof ServerRequestInterface) {
            $this->lang = $this->getLanguageFromRequest($request_or_lang);
        } elseif (is_string($request_or_lang)) {
            $this->lang = in_array($request_or_lang, $this->arr_lang) ? $request_or_lang : 'vi';
        } else {
            $this->lang = 'vi';
        }
    }
    
    /**
     * Get language from request (header, query param, or default)
     */
    private function getLanguageFromRequest(ServerRequestInterface $request): string
    {
        // Try to get language from Accept-Language header
        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        if (strpos($acceptLanguage, 'vi') !== false) {
            return 'vi';
        }
        if (strpos($acceptLanguage, 'en') !== false) {
            return 'en';
        }
        
        // Try to get from custom Language header
        $languageHeader = $request->getHeaderLine('Language');
        if (in_array($languageHeader, $this->arr_lang)) {
            return $languageHeader;
        }
        
        // Try to get from query parameter
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['lang']) && in_array($queryParams['lang'], $this->arr_lang)) {
            return $queryParams['lang'];
        }
        
        // Try to get from form data (if available)
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody['lang']) && in_array($parsedBody['lang'], $this->arr_lang)) {
            return $parsedBody['lang'];
        }
        
        // Default to Vietnamese
        return 'vi';
    }
    
    /**
     * Get error message by key and language
     */
    private function getMessage(string $key, array $params = []): string {
        $messages = [
            'vi' => [
                'required' => 'Trường {field} là bắt buộc.',
                'string' => 'Trường {field} phải là chuỗi ký tự.',
                'integer' => 'Trường {field} phải là số nguyên.',
                'numeric' => 'Trường {field} phải là số.',
                'email' => 'Trường {field} phải là địa chỉ email hợp lệ.',
                'url' => 'Trường {field} phải là URL hợp lệ.',
                'min_string' => 'Trường {field} phải có ít nhất {min} ký tự.',
                'min_numeric' => 'Trường {field} phải có giá trị ít nhất {min}.',
                'max_string' => 'Trường {field} không được vượt quá {max} ký tự.',
                'max_numeric' => 'Trường {field} không được vượt quá {max}.',
                'between_string' => 'Trường {field} phải có từ {min} đến {max} ký tự.',
                'between_numeric' => 'Trường {field} phải có giá trị từ {min} đến {max}.',
                'in' => 'Giá trị được chọn cho {field} không hợp lệ. Các tùy chọn hợp lệ: {options}',
                'not_in' => 'Giá trị được chọn cho {field} không hợp lệ. Không thể là: {options}',
                'confirmed' => 'Xác nhận {field} không khớp.',
                'same' => 'Trường {field} và {other} phải giống nhau.',
                'different' => 'Trường {field} và {other} phải khác nhau.',
                'array' => 'Trường {field} phải là mảng.',
                'boolean' => 'Trường {field} phải là true hoặc false.',
                'date' => 'Trường {field} phải là ngày hợp lệ.',
                'json' => 'Trường {field} phải là chuỗi JSON hợp lệ.',
                'regex' => 'Định dạng trường {field} không hợp lệ.',
                'phone_vietnam' => 'Số điện thoại phải là số Việt Nam hợp lệ (10 chữ số, bắt đầu bằng 0).',
                'duplicate_email' => 'Địa chỉ Email đã tồn tại trong hệ thống.',
                'duplicate_phone' => 'Số điện thoại đã tồn tại trong hệ thống.',
                /** Connection module errors */
                'connection_module' => [
                    'user_target_not_found' => 'Người dùng không được tìm thấy.',
                    'connection_exists' => 'Kết nối đã tồn tại.',
                    'cannot_connect_self' => 'Không thể kết nối với chính mình.',
                    'connection_limit_reached' => 'Đã đạt giới hạn kết nối.',
                    'request_failed' => 'Yêu cầu kết nối thất bại.',
                    'request_connection_exists' => 'Yêu cầu kết nối hoặc kết nối đã tồn tại.',
                    'user_cancel_failed' => 'Không thể hủy yêu cầu kết nối.',
                    'user_respond_failed' => 'Không thể phản hồi yêu cầu kết nối.',
                    'user_update_connection_failed' => 'Không thể cập nhật trạng thái kết nối.',
                ],
                /** User module errors */
                'user_module' => [
                    'invalid_credentials' => 'Thông tin đăng nhập không hợp lệ.',
                    'account_locked' => 'Tài khoản đã bị khóa.',
                    'account_not_verified' => 'Tài khoản chưa được xác minh.',
                    'user_not_found' => 'Người dùng không tồn tại.',
                    'profile_update_failed' => 'Cập nhật hồ sơ thất bại.',
                ],
                /** Auth module errors */
                'auth_module' => [
                    'token_expired' => 'Token đã hết hạn.',
                    'invalid_token' => 'Token không hợp lệ.',
                    'permission_denied' => 'Không có quyền truy cập.',
                    'unauthorized' => 'Chưa được xác thực.',
                    'insufficient_permissions' => 'Không đủ quyền hạn.',
                ],
                /** File module errors */
                'file_module' => [
                    'upload_failed' => 'Tải lên tệp thất bại.',
                    'invalid_format' => 'Định dạng tệp không hợp lệ.',
                    'file_too_large' => 'Tệp quá lớn.',
                    'file_not_found' => 'Không tìm thấy tệp.',
                    'storage_limit_exceeded' => 'Vượt quá giới hạn lưu trữ.',
                ],
                /** Validation module errors */
                'validation_module' => [
                    'invalid_input' => 'Dữ liệu đầu vào không hợp lệ.',
                    'missing_required_fields' => 'Thiếu các trường bắt buộc.',
                    'data_format_error' => 'Lỗi định dạng dữ liệu.',
                ],
            ],
            'en' => [
                'required' => 'The {field} field is required.',
                'string' => 'The {field} field must be a string.',
                'integer' => 'The {field} field must be an integer.',
                'numeric' => 'The {field} field must be numeric.',
                'email' => 'The {field} field must be a valid email address.',
                'url' => 'The {field} field must be a valid URL.',
                'min_string' => 'The {field} field must be at least {min} characters.',
                'min_numeric' => 'The {field} field must be at least {min}.',
                'max_string' => 'The {field} field may not be greater than {max} characters.',
                'max_numeric' => 'The {field} field may not be greater than {max}.',
                'between_string' => 'The {field} field must be between {min} and {max} characters.',
                'between_numeric' => 'The {field} field must be between {min} and {max}.',
                'in' => 'The selected {field} is invalid. Valid options: {options}',
                'not_in' => 'The selected {field} is invalid. Cannot be: {options}',
                'confirmed' => 'The {field} confirmation does not match.',
                'same' => 'The {field} and {other} must match.',
                'different' => 'The {field} and {other} must be different.',
                'array' => 'The {field} field must be an array.',
                'boolean' => 'The {field} field must be true or false.',
                'date' => 'The {field} field must be a valid date.',
                'json' => 'The {field} field must be a valid JSON string.',
                'regex' => 'The {field} field format is invalid.',
                'phone_vietnam' => 'Phone number must be a valid Vietnamese number (10 digits, starting with 0).',
                'duplicate_email' => 'Email address already exists in the system.',
                'duplicate_phone' => 'Phone number already exists in the system.',
                /** Connection module errors */
                'connection_module' => [
                    'user_target_not_found' => 'User not found.',
                    'connection_exists' => 'Connection already exists.',
                    'cannot_connect_self' => 'Cannot connect to yourself.',
                    'connection_limit_reached' => 'Connection limit reached.',
                    'request_failed' => 'Connection request failed.',
                    'request_connection_exists' => 'Connection request or target connection already exists.',
                    'user_cancel_failed' => 'Failed to cancel connection request.',
                    'user_respond_failed' => 'Failed to respond to connection request.',
                    'user_update_connection_failed' => 'Failed to update connection status.',
                ],
                /** User module errors */
                'user_module' => [
                    'invalid_credentials' => 'Invalid credentials provided.',
                    'account_locked' => 'Account has been locked.',
                    'account_not_verified' => 'Account has not been verified.',
                    'user_not_found' => 'User does not exist.',
                    'profile_update_failed' => 'Profile update failed.',
                ],
                /** Auth module errors */
                'auth_module' => [
                    'token_expired' => 'Token has expired.',
                    'invalid_token' => 'Invalid token provided.',
                    'permission_denied' => 'Access denied.',
                    'unauthorized' => 'Unauthorized access.',
                    'insufficient_permissions' => 'Insufficient permissions.',
                ],
                /** File module errors */
                'file_module' => [
                    'upload_failed' => 'File upload failed.',
                    'invalid_format' => 'Invalid file format.',
                    'file_too_large' => 'File is too large.',
                    'file_not_found' => 'File not found.',
                    'storage_limit_exceeded' => 'Storage limit exceeded.',
                ],
                /** Validation module errors */
                'validation_module' => [
                    'invalid_input' => 'Invalid input data.',
                    'missing_required_fields' => 'Missing required fields.',
                    'data_format_error' => 'Data format error.',
                ],
            ]
        ];
        
        // Handle dot notation for nested messages (e.g., 'connection_module.user_target_not_found')
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $message = $messages[$this->lang] ?? $messages['vi'];
            
            // Traverse nested array structure
            foreach ($keys as $nestedKey) {
                if (is_array($message) && isset($message[$nestedKey])) {
                    $message = $message[$nestedKey];
                } else {
                    // Fallback to Vietnamese if current language doesn't have the key
                    $fallbackMessage = $messages['vi'];
                    foreach ($keys as $fallbackKey) {
                        if (is_array($fallbackMessage) && isset($fallbackMessage[$fallbackKey])) {
                            $fallbackMessage = $fallbackMessage[$fallbackKey];
                        } else {
                            // If key not found in either language, return the original key
                            $message = $key;
                            break 2;
                        }
                    }
                    $message = $fallbackMessage;
                    break;
                }
            }
            
            // Ensure we have a string message
            if (!is_string($message)) {
                $message = $key;
            }
        } else {
            // Original logic for simple keys
            $message = $messages[$this->lang][$key] ?? $messages['vi'][$key] ?? $key;
        }
        
        // Replace placeholders
        foreach ($params as $param => $value) {
            $message = str_replace('{' . $param . '}', (string)$value, $message);
        }
        
        return $message;
    }
    
    /**
     * Validate dữ liệu theo rules
     */
    public function validate($field_or_data, $value_or_rules, $rules = null) {
        // Support both old and new API
        if ($rules === null) {
            // New API: validate(data, rules)
            $this->validateBatch($field_or_data, $value_or_rules);
        } else {
            // Old API: validate(field, value, rules)
            $this->validateField($field_or_data, $value_or_rules, $rules);
        }
    }
    
    /**
     * Validate single field
     */
    private function validateField($field, $value, $rules) {
        // Parse rules - handle regex specially to avoid pipe conflicts
        if (is_string($rules)) {
            // Check if contains regex rule
            if (preg_match('/regex:([^|]+)/', $rules, $regexMatch)) {
                $regexRule = $regexMatch[0]; // Full regex:pattern
                $otherRules = str_replace($regexRule, '', $rules);
                $otherRules = array_filter(explode('|', $otherRules), function($rule) {
                    return trim($rule) !== '';
                });
                $rulesArray = array_merge($otherRules, [$regexRule]);
            } else {
                $rulesArray = explode('|', $rules);
            }
        } else {
            $rulesArray = $rules;
        }
        
        foreach ($rulesArray as $rule) {
            $this->applyRule($field, $value, $rule, [$field => $value]);
        }
    }
    
    /**
     * Validate batch of data
     */
    private function validateBatch($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            // Parse rules - handle regex specially to avoid pipe conflicts
            if (is_string($fieldRules)) {
                // Check if contains regex rule
                if (preg_match('/regex:([^|]+)/', $fieldRules, $regexMatch)) {
                    $regexRule = $regexMatch[0]; // Full regex:pattern
                    $otherRules = str_replace($regexRule, '', $fieldRules);
                    $otherRules = array_filter(explode('|', $otherRules), function($rule) {
                        return trim($rule) !== '';
                    });
                    $rulesArray = array_merge($otherRules, [$regexRule]);
                } else {
                    $rulesArray = explode('|', $fieldRules);
                }
            } else {
                $rulesArray = $fieldRules;
            }
            
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }
    }
    
    /**
     * Áp dụng rule cho field
     */
    private function applyRule($field, $value, $rule, $allData) {
        // Parse rule và parameters
        if (strpos($rule, 'regex:') === 0) {
            // Special handling for regex rules
            $pattern = substr($rule, 6); // Remove 'regex:' prefix
            // Allow empty/null when field is optional (no required rule)
            if ($value === null || $value === '') {
                return;
            }

            if (is_string($value) && !preg_match($pattern, $value)) {
                $this->addError($field, $this->getMessage('regex', ['field' => $field]));
            } elseif (!is_string($value)) {
                // Value is not a string, add error
                $this->addError($field, $this->getMessage('string', ['field' => $field]));
            }
            return;
        }
        
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $parameters = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];

        // Skip non-required validations when value is null or empty string
        if ($ruleName !== 'required' && ($value === null || $value === '')) {
            return;
        }
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, $this->getMessage('required', ['field' => $field]));
                }
                break;
                
            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, $this->getMessage('string', ['field' => $field]));
                }
                break;
                
            case 'integer':
            case 'int':
                if ($value !== null && !is_int($value) && !ctype_digit((string)$value)) {
                    $this->addError($field, $this->getMessage('integer', ['field' => $field]));
                }
                break;
                
            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->addError($field, $this->getMessage('numeric', ['field' => $field]));
                }
                break;
                
            case 'email':
                if ($value !== null) {
                    if (!is_string($value)) {
                        $this->addError($field, $this->getMessage('string', ['field' => $field]));
                    } elseif (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, $this->getMessage('email', ['field' => $field]));
                    }
                }
                break;
                
            case 'url':
                if ($value !== null) {
                    if (!is_string($value)) {
                        $this->addError($field, $this->getMessage('string', ['field' => $field]));
                    } elseif (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $this->addError($field, $this->getMessage('url', ['field' => $field]));
                    }
                }
                break;
                
            case 'min':
                $min = (int)$parameters[0];
                if ($value !== null) {
                    if (is_string($value) && strlen($value) < $min) {
                        $this->addError($field, $this->getMessage('min_string', ['field' => $field, 'min' => $min]));
                    } elseif (!is_string($value) && is_numeric($value) && $value < $min) {
                        $this->addError($field, $this->getMessage('min_numeric', ['field' => $field, 'min' => $min]));
                    }
                }
                break;
                
            case 'max':
                $max = (int)$parameters[0];
                if ($value !== null) {
                    if (is_string($value) && strlen($value) > $max) {
                        $this->addError($field, $this->getMessage('max_string', ['field' => $field, 'max' => $max]));
                    } elseif (!is_string($value) && is_numeric($value) && $value > $max) {
                        $this->addError($field, $this->getMessage('max_numeric', ['field' => $field, 'max' => $max]));
                    }
                }
                break;
                
            case 'between':
                $min = (int)$parameters[0];
                $max = (int)$parameters[1];
                if ($value !== null) {
                    if (is_string($value)) {
                        $length = strlen($value);
                        if ($length < $min || $length > $max) {
                            $this->addError($field, $this->getMessage('between_string', ['field' => $field, 'min' => $min, 'max' => $max]));
                        }
                    } elseif (is_numeric($value)) {
                        if ($value < $min || $value > $max) {
                            $this->addError($field, $this->getMessage('between_numeric', ['field' => $field, 'min' => $min, 'max' => $max]));
                        }
                    }
                }
                break;
                
            case 'in':
                if ($value !== null) {
                    $options = implode(', ', $parameters);

                    if (is_array($value)) {
                        $invalid = array_diff($value, $parameters);
                        if (!empty($invalid)) {
                            $this->addError(
                                $field,
                                $this->getMessage('in', ['field' => $field, 'options' => $options])
                            );
                        }
                    } elseif (!in_array($value, $parameters)) {
                        $this->addError($field, $this->getMessage('in', ['field' => $field, 'options' => $options]));
                    }
                }
                break;
                
            case 'not_in':
                if ($value !== null) {
                    $options = implode(', ', $parameters);

                    if (is_array($value)) {
                        $intersect = array_intersect($value, $parameters);
                        if (!empty($intersect)) {
                            $this->addError(
                                $field,
                                $this->getMessage('not_in', ['field' => $field, 'options' => $options])
                            );
                        }
                    } elseif (in_array($value, $parameters)) {
                        $this->addError($field, $this->getMessage('not_in', ['field' => $field, 'options' => $options]));
                    }
                }
                break;
                
            case 'unique':
                // Kiểm tra unique trong database (cần implement theo nhu cầu)
                break;
                
            case 'exists':
                // Kiểm tra tồn tại trong database (cần implement theo nhu cầu)
                break;
                
            case 'confirmed':
                $confirmationField = $field . '_confirmation';
                if ($value !== ($allData[$confirmationField] ?? null)) {
                    $this->addError($field, $this->getMessage('confirmed', ['field' => $field]));
                }
                break;
                
            case 'same':
                $otherField = $parameters[0];
                if ($value !== ($allData[$otherField] ?? null)) {
                    $this->addError($field, $this->getMessage('same', ['field' => $field, 'other' => $otherField]));
                }
                break;
                
            case 'different':
                $otherField = $parameters[0];
                if ($value === ($allData[$otherField] ?? null)) {
                    $this->addError($field, $this->getMessage('different', ['field' => $field, 'other' => $otherField]));
                }
                break;
                
            case 'array':
                if ($value !== null && !is_array($value)) {
                    $this->addError($field, $this->getMessage('array', ['field' => $field]));
                }
                break;
                
            case 'boolean':
            case 'bool':
                if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                    $this->addError($field, $this->getMessage('boolean', ['field' => $field]));
                }
                break;
                
            case 'date':
                if ($value !== null && !strtotime($value)) {
                    $this->addError($field, $this->getMessage('date', ['field' => $field]));
                }
                break;
                
            case 'json':
                if ($value !== null) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->addError($field, $this->getMessage('json', ['field' => $field]));
                    }
                }
                break;
        }
    }
    
    /**
     * Thêm lỗi
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Lấy tất cả lỗi
     */
    public function getErrors() {
        return $this->errors ?? [];
    }
    
    /**
     * Lấy lỗi của field cụ thể
     */
    public function getFieldErrors($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Kiểm tra có lỗi không
     */
    public function hasErrors() {
        return !empty($this->errors) && is_array($this->errors);
    }
    
    /**
     * Sanitize dữ liệu
     */
    public function sanitize($data, $rules) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (isset($rules[$key])) {
                $sanitized[$key] = $this->sanitizeValue($value, $rules[$key]);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value, 'string');
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize một giá trị
     */
    private function sanitizeValue($value, $type) {
        switch ($type) {
            case 'string':
                return $this->sanitizeTextfield($value);
            case 'array':
                if (!is_array($value)) {
                    return [];
                }
                return array_map(function ($item) {
                    return $this->sanitizeTextfield((string)$item);
                }, $value);
            case 'email':
                return $this->sanitizeEmail($value);
            case 'url':
                return $this->sanitizeUrl($value);
            case 'html':
                return $this->sanitizeHtml($value);
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            default:
                return $this->sanitizeTextfield($value);
        }
    }
    
    /**
     * Sanitize text field - thay thế sanitize_text_field của WordPress
     */
    private function sanitizeTextfield($value) {
        if (!is_string($value)) {
            return '';
        }
        
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Strip HTML tags
        $value = strip_tags($value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Remove multiple spaces
        $value = preg_replace('/\s+/', ' ', $value);
        
        return $value;
    }
    
    /**
     * Sanitize email - thay thế sanitize_email của WordPress
     */
    private function sanitizeEmail($value) {
        if (!is_string($value)) {
            return '';
        }
        
        // Remove all characters except letters, numbers and !#$%&'*+-=?^_`{|}~@.[]
        $value = preg_replace('/[^a-zA-Z0-9!#$%&\'*+\-=?^_`{|}~@.\[\]]/', '', $value);
        
        // Validate email format
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        
        return strtolower($value);
    }
    
    /**
     * Sanitize URL
     */
    private function sanitizeUrl($value) {
        if (!is_string($value)) {
            return '';
        }
        
        // Trim whitespace
        $value = trim($value);
        
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Validate URL format
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }
        
        // Encode special characters
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return $value;
    }
    
    /**
     * Sanitize HTML content
     */
    private function sanitizeHtml($value) {
        if (!is_string($value)) {
            return '';
        }
        
        // Allowed HTML tags và attributes
        $allowedTags = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'span' => ['class', 'style'],
            'div' => ['class', 'style'],
            'h1' => ['class'],
            'h2' => ['class'],
            'h3' => ['class'],
            'h4' => ['class'],
            'h5' => ['class'],
            'h6' => ['class'],
            'a' => ['href', 'title', 'target'],
            'ul' => ['class'],
            'ol' => ['class'],
            'li' => ['class'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'class'],
            'blockquote' => ['cite'],
            'code' => [],
            'pre' => [],
        ];
        
        // Strip dangerous tags
        $value = $this->stripDangerousTags($value);
        
        // Filter allowed tags
        $value = $this->filterAllowedTags($value, $allowedTags);
        
        return $value;
    }
    
    /**
     * Strip dangerous HTML tags
     */
    private function stripDangerousTags($html) {
        $dangerousTags = [
            'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
            'button', 'select', 'textarea', 'link', 'meta', 'title', 'head',
            'html', 'body', 'base', 'frame', 'frameset', 'applet'
        ];
        
        foreach ($dangerousTags as $tag) {
            $html = preg_replace('/<\s*' . $tag . '[^>]*>.*?<\s*\/\s*' . $tag . '\s*>/is', '', $html);
            $html = preg_replace('/<\s*' . $tag . '[^>]*\/?>/i', '', $html);
        }
        
        // Remove javascript: và data: URLs
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/data\s*:/i', '', $html);
        
        // Remove on* event attributes
        $html = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        return $html;
    }
    
    /**
     * Filter allowed HTML tags and attributes
     */
    private function filterAllowedTags($html, $allowedTags) {
        if (empty($allowedTags)) {
            return strip_tags($html);
        }
        
        $allowedTagsList = array_keys($allowedTags);
        $allowedTagsString = '<' . implode('><', $allowedTagsList) . '>';
        
        // Strip tags not in allowed list
        $html = strip_tags($html, $allowedTagsString);
        
        // Filter attributes
        foreach ($allowedTags as $tag => $allowedAttrs) {
            if (empty($allowedAttrs)) {
                // Remove all attributes from this tag
                $html = preg_replace('/<(' . $tag . ')\s[^>]*>/i', '<$1>', $html);
            } else {
                // Keep only allowed attributes
                $pattern = '/<(' . $tag . ')([^>]*)>/i';
                $html = preg_replace_callback($pattern, function($matches) use ($allowedAttrs) {
                    $tag = $matches[1];
                    $attrs = $matches[2];
                    
                    $filteredAttrs = '';
                    foreach ($allowedAttrs as $attr) {
                        if (preg_match('/\s' . $attr . '\s*=\s*["\']([^"\']*)["\']/', $attrs, $attrMatch)) {
                            $filteredAttrs .= ' ' . $attr . '="' . htmlspecialchars($attrMatch[1], ENT_QUOTES, 'UTF-8') . '"';
                        }
                    }
                    
                    return '<' . $tag . $filteredAttrs . '>';
                }, $html);
            }
        }
        
        return $html;
    }
    
    /**
     * Get error message for a specific key - public method for external use
     */
    public function getErrorMessage(string $key, array $params = []): string
    {
        return $this->getMessage($key, $params);
    }

    /**
     * Clear all errors
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Get the current language
     */
    public function getLanguage(): string
    {
        return $this->lang;
    }

    /**
     * Get error message for a specific module
     * 
     * @param string $module The module name (e.g., 'connection_module', 'user_module')
     * @param string $key The error key within the module
     * @param array $params Parameters for message replacement
     * @return string The localized error message
     */
    public function getModuleErrorMessage(string $module, string $key, array $params = []): string
    {
        return $this->getErrorMessage("{$module}.{$key}", $params);
    }

    /**
     * Get multiple error messages at once
     * 
     * @param array $keys Array of message keys (can include dot notation)
     * @param array $globalParams Global parameters to apply to all messages
     * @return array Array of localized messages with keys preserved
     */
    public function getMultipleErrorMessages(array $keys, array $globalParams = []): array
    {
        $messages = [];
        foreach ($keys as $key) {
            $messages[$key] = $this->getErrorMessage($key, $globalParams);
        }
        return $messages;
    }

    /**
     * Check if a message key exists (supports dot notation)
     * 
     * @param string $key The message key to check
     * @return bool True if the message exists, false otherwise
     */
    public function hasMessage(string $key): bool
    {
        $message = $this->getMessage($key, []);
        // If getMessage returns the original key, it means the message wasn't found
        return $message !== $key;
    }

    /**
     * Get all available messages for a specific module
     * 
     * @param string $module The module name
     * @return array Array of available messages for the module
     */
    public function getModuleMessages(string $module): array
    {
        $messages = [
            'vi' => [
                'connection_module' => [
                    'user_target_not_found' => 'Người dùng không được tìm thấy.',
                    'connection_exists' => 'Kết nối đã tồn tại.',
                    'cannot_connect_self' => 'Không thể kết nối với chính mình.',
                    'connection_limit_reached' => 'Đã đạt giới hạn kết nối.',
                ],
                'user_module' => [
                    'invalid_credentials' => 'Thông tin đăng nhập không hợp lệ.',
                    'account_locked' => 'Tài khoản đã bị khóa.',
                    'account_not_verified' => 'Tài khoản chưa được xác minh.',
                    'user_not_found' => 'Người dùng không tồn tại.',
                    'profile_update_failed' => 'Cập nhật hồ sơ thất bại.',
                ],
                'auth_module' => [
                    'token_expired' => 'Token đã hết hạn.',
                    'invalid_token' => 'Token không hợp lệ.',
                    'permission_denied' => 'Không có quyền truy cập.',
                    'unauthorized' => 'Chưa được xác thực.',
                    'insufficient_permissions' => 'Không đủ quyền hạn.',
                ],
                'file_module' => [
                    'upload_failed' => 'Tải lên tệp thất bại.',
                    'invalid_format' => 'Định dạng tệp không hợp lệ.',
                    'file_too_large' => 'Tệp quá lớn.',
                    'file_not_found' => 'Không tìm thấy tệp.',
                    'storage_limit_exceeded' => 'Vượt quá giới hạn lưu trữ.',
                ],
                'validation_module' => [
                    'invalid_input' => 'Dữ liệu đầu vào không hợp lệ.',
                    'missing_required_fields' => 'Thiếu các trường bắt buộc.',
                    'data_format_error' => 'Lỗi định dạng dữ liệu.',
                ],
            ],
            'en' => [
                'connection_module' => [
                    'user_target_not_found' => 'User not found.',
                    'connection_exists' => 'Connection already exists.',
                    'cannot_connect_self' => 'Cannot connect to yourself.',
                    'connection_limit_reached' => 'Connection limit reached.',
                ],
                'user_module' => [
                    'invalid_credentials' => 'Invalid credentials provided.',
                    'account_locked' => 'Account has been locked.',
                    'account_not_verified' => 'Account has not been verified.',
                    'user_not_found' => 'User does not exist.',
                    'profile_update_failed' => 'Profile update failed.',
                ],
                'auth_module' => [
                    'token_expired' => 'Token has expired.',
                    'invalid_token' => 'Invalid token provided.',
                    'permission_denied' => 'Access denied.',
                    'unauthorized' => 'Unauthorized access.',
                    'insufficient_permissions' => 'Insufficient permissions.',
                ],
                'file_module' => [
                    'upload_failed' => 'File upload failed.',
                    'invalid_format' => 'Invalid file format.',
                    'file_too_large' => 'File is too large.',
                    'file_not_found' => 'File not found.',
                    'storage_limit_exceeded' => 'Storage limit exceeded.',
                ],
                'validation_module' => [
                    'invalid_input' => 'Invalid input data.',
                    'missing_required_fields' => 'Missing required fields.',
                    'data_format_error' => 'Data format error.',
                ],
            ]
        ];

        $currentLangMessages = $messages[$this->lang][$module] ?? [];
        $fallbackMessages = $messages['vi'][$module] ?? [];
        
        return array_merge($fallbackMessages, $currentLangMessages);
    }

    /**
     * Get a list of all available modules
     * 
     * @return array List of module names
     */
    public function getAvailableModules(): array
    {
        return [
            'connection_module',
            'user_module',
            'auth_module',
            'file_module',
            'validation_module'
        ];
    }
}
