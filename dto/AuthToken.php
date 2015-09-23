<?php
/**
 * @copyright Copyright (c) 2015 Thamtech, LLC
 * @link http://github.com/thamtech/yii2-jsonrpc-jwsauth
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace thamtech\jwsauth\dto;

use JsonRpc2\Dto;

/**
 * AuthToken is a data transfer object representing a single token string.
 * 
 * @author Tyler Ham <tyler@thamtech.com>
 */
class AuthToken extends Dto
{
    /** @var string */
    public $token;
}