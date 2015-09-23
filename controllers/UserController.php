<?php
/**
 * @copyright Copyright (c) 2015 Thamtech, LLC
 * @link http://github.com/thamtech/yii2-jsonrpc-jwsauth
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace thamtech\jwsauth\controllers;

use Yii;
use thamtech\jwsauth\filters\auth\JsonRpcAuth;
use JsonRpc2\extensions\AuthException;

/**
 * UserController is a controller providing methods to authenticate a user
 * with a username/password combination and refresh an existing authentication
 * token.
 * 
 * The identityClass of the Yii application's user component must implement the
 * [[\thamtech\jwsauth\models\IdentityInterface]] interface.
 * 
 * @author Tyler Ham <tyler@thamtech.com>
 */
class UserController extends \JsonRpc2\Controller
{
    use \JsonRpc2\extensions\AuthTrait;
    
    /**
     * @var \thamtech\jwsauth\components\JwsManager [[getRefreshExp()]]
     * will look for a [[\thamtech\jwsauth\components\JwsManager]] component in this
     * property. If it is not set, [[getRefreshExp()]] will look for one at
     * Yii::$app->jwsManager instead.
     */
    protected $jwsManager;
    
    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'authenticator' => [
                'class' => JsonRpcAuth::className(),
                'except' => ['authenticate', 'refresh-token'],
            ],
        ];
    }
    
    /**
     * Authenticates a user from a username and password combination. 
     * 
     * @param  string $username Username
     * 
     * @param  string $password Password
     * 
     * @return \thamtech\jwsauth\dto\Token
     */
    public function actionAuthenticate($username, $password) {
        $identityClass = Yii::$app->user->identityClass;
        $user = $identityClass::findByUsername($username);
        
        if (!$user) {
            throw new AuthException('Invalid username or password', AuthException::INVALID_AUTH);
        }
        
        if (!$user->validatePassword($password)) {
            throw new AuthException('Invalid username or password', AuthException::INVALID_AUTH);
        }
        
        return ['token' => $user->getAuthKey()];
    }
    
    /**
     * Authenticates a user from an existing auth access token that may have
     * expired but is still refreshable.
     * 
     * @return \thamtech\jwsauth\dto\Token
     */
    public function actionRefreshToken() {
        $identityClass = Yii::$app->user->identityClass;
        $user = $identityClass::findIdentityByAccessToken($this->getAuthCredentials(), JsonRpcAuth::className(), false);
        
        if (!$user) {
            throw new AuthException('Invalid token', AuthException::INVALID_AUTH);
        }
        
        if ($this->isUserTokenRefreshable($user)) {
            return ['token' => $user->getAuthKey()];
        }
        
        throw new AuthException('expired; user must reauthenticate', AuthException::INVALID_AUTH);
    }
    
    /**
     * Gets the refreshExp timeout duration, like "24 hours". This will look
     * for the refreshExp property on this controller's jwsManager object,
     * and fall back to looking for it in a jwsManager application component.
     * 
     * @return string Period of time for which a token should be valid for
     * authentication, or null if the token is not refreshable.
     * 
     * @see http://php.net/manual/en/datetime.formats.php
     */
    protected function getRefreshExp() {
        if (isset($this->jwsManager)) {
            if (!empty($this->jwsManager->refreshExp)) {
                return $this->jwsManager->refreshExp;
            }
            
            return null;
        }
        
        if (isset(Yii::$app->jwsManager) && Yii::$app->jwsManager instanceof \thamtech\jwsauth\components\JwsManager) {
            if (!empty(Yii::$app->jwsManager->refreshExp)) {
                return Yii::$app->jwsManager->refreshExp;
            }
            
            return null;
        }
        
        return null;
    }
    
    /**
     * Determines if the user token is refreshable. This implementation requires
     * the token issue time to be within the refreshExp timeout specified
     * in the [[\thamtech\jwsauth\components\JwsManager]] application component.
     * 
     * @param  \thamtech\models\IdentityInterface $userIdentity User Identity
     * 
     * @return boolean true if the token is refreshable
     */
    protected function isUserTokenRefreshable($userIdentity) {
        $refreshExp = $this->getRefreshExp();
        
        // if there is no refresh expiration, then token is not refreshable
        // at all.
        if ($refreshExp === null) {
            return false;
        }
        
        $issueTime = $userIdentity->getAuthKeyIssueTime();
        
        // if there is no issue time, then the token is not refreshable
        // at all.
        if (!$issueTime) {
            return false;
        }
        
        // Only allow token refresh for up to the refreshExp timeout.
        // 
        // For example, if $refreshExp = '24 hours', then a token with an
        // $issueTime within the last 24 hours should be refreshable
        $earliestRefreshableIssueTime = new \DateTime('-' . $refreshExp);
        if ($issueTime >= $earliestRefreshableIssueTime->format('U')) {
            return true;
        }
        
        return false;
    }
}