<?php
/**
 * @copyright Copyright (c) 2015 Thamtech, LLC
 * @link http://github.com/thamtech/yii2-jsonrpc-jwsauth
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace thamtech\jwsauth\models;

use Yii;

/**
 * An extended IdentityInterface that should be implemented by a class providing
 * identity information that can be authenticated with JWS tokens and
 * JsonRpcAuth.
 * 
 * This interface can typically be implemented by a user model class as
 * demonstrated in [[\yii\web\IdentityInterface]], but with the addition of a
 * getAuthKeyIssueTime() method:
 * 
 * ```php
 * class User extends ActiveRecord implements IdentityInterface
 * {
 *     public function getAuthKeyIssueTime()
 *     {
 *         return $this->authKeyIssueTime;
 *     }
 * }
 * ```
 * 
 * @author Tyler Ham <tyler@thamtech.com>
 */
interface IdentityInterface extends \yii\web\IdentityInterface
{
    
    /**
     * Returns a unix timestamp indicating when the user's auth key was issued.
     * 
     * @return integer unix timestamp or null if there is no auth key
     */
    public function getAuthKeyIssueTime();
}