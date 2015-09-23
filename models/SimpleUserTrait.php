<?php
/**
 * @copyright Copyright (c) 2015 Thamtech, LLC
 * @link http://github.com/thamtech/yii2-jsonrpc-jwsauth
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace thamtech\jwsauth\models;

use Yii;
use thamtech\jwsauth\filters\auth\JsonRpcAuth;

/**
 * Trait providing a default implementation of the
 * [[\thamtech\jwsauth\models\IdentityInterface]] methods related to auth keys
 * and access tokens.
 * 
 * This is a suitable implementation if:
 * 1. You have established a [[\thamtech\jwsauth\components\JwsManager]] as
 *    an application component at Yii::$app->jwsManager
 *    
 * 2. Your user model has both id and username properties.
 * 
 * This implementation also includes empty arrays of authorizations and other
 * user info. You may override the [[getTokenAuthorizations()]] and
 * [[getTokenInfo()]] methods to provide populated arrays with values
 * meaningful to your application.
 * 
 * @author Tyler Ham <tyler@thamtech.com>
 */
trait SimpleUserTrait
{
    /**
     * Issue time of the auth token
     * 
     * @var integer unix timestamp
     */
    public $iat;
    
    /**
     * Finds an identity by the given token.
     * 
     * @param mixed $token the token to be looked for
     * 
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * 
     * @return IdentityInterface the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null, $checkExpiration = true)
    {
        if ($type == JsonRpcAuth::className()) {
            if (!is_string($token)) {
                return null;
            }
            
            $jws = Yii::$app->jwsManager->load($token);
            
            if ($checkExpiration) {
                $valid = Yii::$app->jwsManager->isValid($jws);
            } else {
                $valid = Yii::$app->jwsManager->verify($jws);
            }
            
            if ($valid) {
                $payload = $jws->getPayload();
                
                unset($payload['exp']);
                
                return new static($payload);
            }
        }
        
        return null;
    }
    
    /**
     * Returns a unix timestamp indicating when the user's auth key was issued.
     * 
     * @return integer unix timestamp or null if there is no auth key
     */
    public function getAuthKeyIssueTime()
    {
        if (isset($this->iat) && is_numeric($this->iat)) {
            return $this->iat;
        }
        
        return null;
    }
    
    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * 
     * @return string a key that is used to check the validity of a given identity ID.
     */
    public function getAuthKey()
    {
        $payload = [
            'id' => $this->id,
            'username' => $this->username,
        ];
        
        $authorizations = $this->getTokenAuthorizations();
        if (!empty($authorizations)) {
            $payload['authorizations'] = $authorizations;
        }
        
        $info = $this->getTokenInfo();
        if (!empty($info)) {
            $payload['info'] = $info;
        }
        
        return Yii::$app->jwsManager->newToken($payload);
    }
    
    /**
     * Provides an array of authorizations to include in the token.
     * 
     * @return array
     */
    protected function getTokenAuthorizations()
    {
        return [];
    }
    
    /**
     * Provides an array of additional user info to include in the token.
     * 
     * @return array
     */
    protected function getTokenInfo() {
        return [];
    }
}