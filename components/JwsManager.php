<?php
/**
 * @copyright Copyright (c) 2015 Thamtech, LLC
 * @link http://github.com/thamtech/yii2-jsonrpc-jwsauth
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace thamtech\jwsauth\components;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * Provides a pre-configured interface for generating and verifying JWS tokens
 * with specified expirations, algorithms, and encodings.
 * 
 * Configure this as an application component as follows:
 * 
 * ```php
 * return [
 *   'components' => [
 *     'jwsManager' => [
 *       'class' => 'thamtech\jwsauth\components\JwsManager',
 *       'pubkey' => '@app/config/keys/jwsauth/public.pem',
 *       'pvtkey' => '@app/config/keys/jwsauth/private.pem',
 *       'encoder' => 'Namshi\JOSE\Base64\Base64UrlSafeEncoder',
 *       'refreshExp' => '24 hours',
 *       'exp' => '1 hour',
 *       'alg' => 'RS256',
 *       'jwsClass' => 'Namshi\JOSE\SimpleJWS',
 *     ],
 *   ]
 * ]
 * ```
 * 
 * @author Tyler Ham <tyler@thamtech.com>
 */
class JwsManager extends \yii\base\Object
{
    
    /**
     * Period of time for which a token should be refreshable.
     * 
     * @var string
     * @see http://php.net/manual/en/datetime.formats.php
     */
    public $refreshExp = '24 hours';
    
    /**
     * Period of time for which a token should be valid for authentication.
     * 
     * @var string
     * @see http://php.net/manual/en/datetime.formats.php
     */
    public $exp = '1 hour';
    
    /**
     * Path alias to a public key file. The referenced file must contain
     * a PEM encoded certificate/public key (it may contain both).
     * 
     * @var string
     */
    public $pubkey;
    
    /**
     * Path alias to a private key file. The referenced file must contain a PEM
     * encoded certificate/private key (it may contain both).
     * 
     * @var string
     */
    public $pvtkey;
    
    /**
     * Algorithm used to sign the JSON Web Token. This should be an algorithm
     * recognized by the Namshi\jose library.
     * 
     * @var string
     */
    public $alg = 'RS256';
    
    /**
     * Class of the Base64 encoder to use when generating the JSON Web Token.
     * 
     * @var string
     */
    public $encoder = 'Namshi\JOSE\Base64\Base64UrlSafeEncoder';
    
    /**
     * Class of the JWS implementation to use.
     * 
     * @var string
     */
    public $jwsClass = 'Namshi\JOSE\SimpleJWS';
    
    /**
     * Resource identifier of the public key (returned by [[openssl_pkey_get_public()]]).
     * 
     * @var resource
     */
    private $publicKeyResource;
    
    /**
     * Resource identifier of the private key (returned by [[openssl_pkey_get_private()]]).
     * 
     * @var resource
     */
    private $privateKeyResource;
    
    /**
     * Creates an instance of a JWS from a JWT string.
     * 
     * @param  string $token JWT token string
     * 
     * @return \Namshi\JOSE\JWS
     */
    public function load($token) {
        $jwsClass = $this->jwsClass;
        $jws = $jwsClass::load($token);
        return $jws;
    }
    
    /**
     * Checks that the JWS has been signed with a valid private key by verifying
     * it with the public key and, if $jws is an instance of
     * [[\Namshi\JOSE\SimpleJWS]], verify that the token is not expired.
     * 
     * @param  \Namshi\JOSE\JWS  &$jws
     * 
     * @return boolean
     */
    public function isValid(&$jws) {
        if (method_exists($jws, 'isValid')) {
            $publicKey = $this->getPublicKey();
            return $jws->isValid($publicKey, $this->alg);
        }
        
        // otherwise
        return $this->verify($jws);
    }
    
    /**
     * Verifies that the internal signin input corresponds to the encoded
     * signature previously stored.
     * 
     * @param  \Namshi\JOSE\JWS  &$jws
     * 
     * @return boolean
     */
    public function verify(&$jws) {
        $publicKey = $this->getPublicKey();
        return $jws->verify($publicKey, $this->alg);
    }
    
    /**
     * Generates a new JWS token with the specified payload array.
     * 
     * @param  array $payload
     * 
     * @return string The JWS token string.
     */
    public function newToken($payload) {
        $jwsClass = $this->jwsClass;
        $jws = new $jwsClass(['alg' => $this->alg]);
        $jws->setEncoder(Yii::createObject($this->encoder));
        
        if ($this->exp) {
            $date = new \DateTime($this->exp);
            
            $payload = ArrayHelper::merge($payload, [
                'exp' => $date->format('U'),
            ]);
        }
        
        $jws->setPayload($payload);
        
        $privateKey = $this->getPrivateKey();
        $jws->sign($privateKey);
        
        return $jws->getTokenString();
    }
    
    /**
     * Gets the public key resource.
     * 
     * @return resource
     */
    protected function getPublicKey() {
        if (empty($this->publicKeyResource)) {
            $this->publicKeyResource = openssl_pkey_get_public(file_get_contents(Yii::getAlias($this->pubkey)));
        }
        
        return $this->publicKeyResource;
    }
    
    /**
     * Gets the private key resource.
     * 
     * @return resource
     */
    protected function getPrivateKey() {
        if (empty($this->privateKeyResource)) {
            $this->privateKeyResource = openssl_pkey_get_private(file_get_contents(Yii::getAlias($this->pvtkey)));
        }
        
        return $this->privateKeyResource;
    }
}