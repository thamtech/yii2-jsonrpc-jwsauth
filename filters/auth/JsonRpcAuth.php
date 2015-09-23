<?php
/**
 * @copyright Copyright (c) 2015 Thamtech, LLC
 * @link http://github.com/thamtech/yii2-jsonrpc-jwsauth
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace thamtech\jwsauth\filters\auth;

use yii\filters\auth\AuthMethod;
use yii\base\InvalidConfigException;
use JsonRpc2\extensions\AuthException;

/**
 * JsonRpcAuth is an action filter that supports authentication based on an
 * access token passed through the "auth" member of a JSON RPC 2.0
 * request object.
 * 
 * You may use JsonRpcAuth by attaching it as a behavior to a controller like
 * the following:
 * 
 * ```php
 * public function behaviors()
 * {
 *   return [
 *     'authenticator' => [
 *       'class' => \thamtech\jwsauth\filters\auth\JsonRpcAuth::className(),
 *     ],
 *   ];
 * }
 * ```
 * 
 * @see http://www.jsonrpc.org/specification
 * @see https://jsonrpcx.org/AuthX/HomePage
 * 
 * @author Tyler Ham <tyler@thamtech.com>
 */
class JsonRpcAuth extends AuthMethod {
    
    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response) {
        // Ensure that this AuthMethod object is attached to a Controller
        // (owner) that provides the getAuthCredentials method.
        if (!method_exists($this->owner, 'getAuthCredentials')) {
            throw new InvalidConfigException(get_class($this->owner) . ' must implement the getAuthCredentials method.');
        }
        
        $token = $this->owner->getAuthCredentials();
        
        $identity = $user->loginByAccessToken($token, get_class($this));
        if ($identity !== null) {
            return $identity;
        }
        
        // if auth was provided but did not match an identity, then it is either
        // invalid or already expired.
        // 
        // the client will be responsible for refreshing a valid token or
        // re-authenticating the user using username/password credentials.
        if (!empty($token)) {
            throw new AuthException('Invalid or expired token', AuthException::INVALID_AUTH);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function handleFailure($response) {
        throw new AuthException('Missing auth', AuthException::MISSING_AUTH);
    }
}