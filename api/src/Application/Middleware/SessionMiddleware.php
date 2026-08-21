<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Application\Settings\SettingsInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Predis\Client as PredisClient;
use App\Application\Utility\CurrentUserContext;

use Slim\Exception\HttpForbiddenException;

class SessionMiddleware implements Middleware
{
    private $_passThrough = array(
        '/v1/auth/login',
        '/v1/auth/register',
        '/v1/auth/request-otp/',
        '/v1/auth/verify-otp/',
        '/v1/production/dds-export',
        '/v1/sms/send/',
        '/v1/general/company',
    );
    private $_external_api = array(
        '/v1/external',
    );

    /**
     * @var SettingsInterface
     */
    private $settings;
    /**
     * @var MysqliDb
     */
    private $db;
    /**
     * @var Memcached
     */
    private $memcached;
    /**
     * @var Predis
     */
    // private $predis;
    /**
     * @var CurrentUserContext
     */
    private $userContext;

    /**
     * @param SettingsInterface $settings
     * @param MysqliDb $db
     * @param Memcached $memcached
     * @param PredisClient $predis
     * @param CurrentUserContext $userContext
    */
    public function __construct(
        SettingsInterface $settings,
        \MysqliDb $db,
        CurrentUserContext $userContext,
        // \Memcached $memcached
    ) {
        $this->settings = $settings;
        $this->db = $db;
        $this->userContext = $userContext;
        // $this->memcached = $memcached;
        // $this->predis = $predis;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {


        // LIMIT API RATE
        if ($this->settings->get('env')=='production') {
            $max_calls_limit  = 100; // 5
            $time_period      = 2; // 2 Seconds
            $total_user_calls = 0;
        } else {
            $max_calls_limit  = 10_000;
            $time_period      = 2; // 2 Seconds
            $total_user_calls = 0;
        }
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $user_ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $user_ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $user_ip_address = $_SERVER['REMOTE_ADDR'];
        }
        /*
        if (!$this->memcached->get($user_ip_address)) {
            $this->memcached->set($user_ip_address, 1, $time_period);
            $total_user_calls = 1;
        } else {
            $this->memcached->increment($user_ip_address);
            $total_user_calls =  $this->memcached->get($user_ip_address);
            if ($total_user_calls > $max_calls_limit) {
                throw new HttpForbiddenException($request, 'API limit exceeded.');
            }
        }
        */
        $data_cache = [];

        $token = !empty($request->getHeader('token')) ? $request->getHeader('token')[0] : null;
        $access_token = !empty($request->getHeader('Authorization')) ? $request->getHeader('Authorization')[0] : null;
        // Add keyword Bearer
        if (!empty($access_token) && strpos($access_token, 'Bearer')!==false) {
            $access_token = str_replace('Bearer ', '', $access_token);
        }


        $allowRouter = $this->_allowRoutePass($request);

        if (!empty($token)) {
            $request = $request->withAttribute('token', $token);
        }
        if (!empty($access_token)) {
            $request = $request->withAttribute('access_token', $access_token);
        }

        // Caching Data
        if (!empty($token) || !empty($access_token)) {
            $request = $request->withAttribute('cache', $data_cache);
        }

        if (!empty($access_token)) {
            // Read information from access token
            $tks = explode('.', $access_token);
            list($headb64, $bodyb64, $cryptob64) = $tks;
            $data_jwt = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
            
            // For Application call API
            if ($data_jwt->type=='app') {
                    $this->db->where("service_code", $data_jwt->service_code);
                    $this->db->where("is_active", 1);
                    $auth_data = $this->db->getOne("w_service");
                    if (empty($auth_data)) {
                        throw new HttpForbiddenException($request, 'You are forbidden to access this page');
                    }

                    $auth_data["workspaces"] = json_decode($auth_data["workspaces"], true);

                // Get secret key by product_code ($data_jwt) in database/cache to verify this access token
                $secret_jwt = $auth_data["secret_key"];
                $type_jwt = "HS256";
            } elseif ($data_jwt->type=='auth' && $data_jwt->phone) {
                $secret_jwt = base64_decode($this->settings->get('authentication_public_key'));
                $type_jwt = "RS256";
                $this->db->where("phone", $data_jwt->phone);
                $this->db->where("is_approved", 1);
                $this->db->where("is_active", 1);
                $this->db->where("deleted_by", 0);
                $auth_data = $this->db->getOne("eudr_users", array("user_id", "full_name", "email", "phone", "parent_user_id", "avatar", "register_type", "is_approved", "is_active", "company_id"));
                
                if (!empty($auth_data)) {
                    $this->db->where("u_p.user_id", $auth_data['user_id']);
                    $this->db->join("eudr_permissions p", "p.permission_id=u_p.permission_id", "LEFT");
                    $user_permissions = $this->db->get("eudr_user_permissions u_p", null, "p.name, p.module");
                    $auth_data['permissions'] = [];
                    if(!empty($user_permissions)) {
                        $permissions = [];
                        foreach($user_permissions as $permission) {
                            $permissions[$permission['name']] = true;
                        }
                        $auth_data['permissions'] = $permissions;
                    }

                    // Load user roles (multi-role support)
                    $this->db->where("ur.user_id", $auth_data['user_id']);
                    $this->db->join("eudr_roles r", "r.role_id=ur.role_id", "LEFT");
                    $this->db->orderBy("r.sort_order", "ASC");
                    $userRoles = $this->db->get("eudr_user_roles ur", null, "r.role_id, r.name, r.description");
                    $auth_data['roles'] = $userRoles ?: [];
                    $auth_data['user_roles'] = array_map(fn($r) => $r['name'], $auth_data['roles']);

                    /* Set current user context to InDatabaseRepository using  */
                    $userId = isset($auth_data['user_id']) ? (int)$auth_data['user_id'] : null;
                    // Multi-role: derive primary roleId from loaded roles (first by sort_order)
                    $roleId = !empty($userRoles) ? (int)$userRoles[0]['role_id'] : null;
                    $companyId = isset($auth_data['company_id']) ? (int)$auth_data['company_id'] : null;
                    $this->userContext->setUserId($userId);
                    $this->userContext->setRoleId($roleId);
                    $this->userContext->setCompanyId($companyId);
                    
                }
            } else {
                throw new HttpForbiddenException($request, 'You are forbidden to access this page');
            }

            // Validate access token
            try {
                $access_token_decode = JWT::decode($access_token, new Key($secret_jwt, $type_jwt));
                $access_token_decode = json_decode(json_encode($access_token_decode));
            } catch (\Exception $e) {
                throw new HttpForbiddenException($request, 'You are forbidden to access this page');
            }
            
            //$request = $request->withAttribute('permission', $access_token_decode->permissions??[]);
            $request = $request->withAttribute('permission', $auth_data['permissions'] ?? []);
            unset($access_token_decode->permissions);
            $request = $request->withAttribute('auth', $access_token_decode);
            if (!empty($auth_data)) {
                $request = $request->withAttribute('auth_data', $auth_data);
            }
        } elseif ($allowRouter) {
        } else {
            throw new HttpForbiddenException($request, 'You are forbidden to access this page');
        }

        return $handler->handle($request);
    }

    private function _allowRoutePass($request)
    {
        $reqPath = $request->getUri()->getPath();
        $reqMethod = $request->getMethod();
        if ($reqMethod=='OPTIONS') {
            return true;
        }
        if ($reqPath=='/') {
            return false;
        }
        $method = strtolower($request->getMethod());
        foreach ($this->_passThrough as $k => $v) {
            if (!is_array($v) && strpos($reqPath, $v)!==false) {
                return true;
            } elseif (is_array($v) && strpos($reqPath, (string)$k)!==false) {
                if (isset($v[$method]) && $v[$method]) {
                    return $v[$method];
                } else {
                    return false;
                }
            }
        }
        return false;
    }
    private function _allowExternalRoutePass($request)
    {
        $reqPath = $request->getUri()->getPath();
        $reqMethod = $request->getMethod();
        if ($reqMethod=='OPTIONS') {
            return true;
        }
        if ($reqPath=='/') {
            return false;
        }
        $method = strtolower($request->getMethod());
        foreach ($this->_external_api as $k => $v) {
            if (!is_array($v) && strpos($reqPath, $v)!==false) {
                return true;
            } elseif (is_array($v) && strpos($reqPath, (string)$k)!==false) {
                if (isset($v[$method]) && $v[$method]) {
                    return $v[$method];
                } else {
                    return false;
                }
            }
        }
        return false;
    }
}
