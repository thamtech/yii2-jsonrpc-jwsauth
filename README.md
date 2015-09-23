yii2-jsonrpc-jwsauth
====================

An extension to handle signed access token authentication via JSON RPC 2.0.

This library interfaces
with [yii2-json-rpc-2.0](http://github.com/cranetm/yii2-json-rpc-2.0) to provide
the JSON RPC 2.0 communication in your controller and
[namshi/jose](http://github.com/namshi/jose) to generate signed
[JWS](https://tools.ietf.org/html/rfc7515) tokens.

For license information check the [LICENSE](LICENSE.md)-file.

Installation
------------

The preferred way to install this extensions is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require --prefer-dist thamtech/yii2-jsonrpc-jwsauth
```
or add
```
"thamtech/yii2-jsonrpc-jwsauth": "*"
```
to the `require` section of your `composer.json` file.

Integration
-----------

1. [Generate a kepair using OpenSSL](https://en.wikibooks.org/wiki/Cryptography/Generate_a_keypair_using_OpenSSL)
   and store the keys in public.pem and private.pem.

2. Add the JwsManager application component in your site configuration:

    ```php
    return [
      'components' => [
        'jwsManager' => [
          'class' => 'thamtech\jwsauth\components\JwsManager',
          'pubkey' => '@app/config/keys/jwsauth/public.pem',
          'pvtkey' => '@app/config/keys/jwsauth/private.pem',
          
          // The settings below are optional. Defaults will be used if not set here.
          //'encoder' => 'Namshi\JOSE\Base64\Base64UrlSafeEncoder',
          //'refreshExp' => '24 hours',
          //'exp' => '1 hour',
          //'alg' => 'RS256',
          //'jwsClass' => 'Namshi\JOSE\SimpleJWS',
        ],
      ]
    ]
    ```

3. Create a `UserController` in your application:

    ```php
    class UserController extends \thamtech\jwsauth\controllers\UserController
    {
      // parent class provides actionAuthenticate($username, $passwrd)
      // and actionRefreshToken()
      
      // You may add your own additional methods to provide additional user
      // management services such as registration, password changes, etc.
    }
    ```

4. Update your `User` model to implement `\thamtech\jwsauth\models\IdentityInterface`
   instead of `\yii\web\IdentityInterface`, and use the `SimpleUserTrait`:

    ```php
    class User extends \yii\base\Object implements \thamtech\jwsauth\models\IdentityInterface
    {
      use SimpleUserTrait;
      
      public $id;
      public $username;
      
      // You must still implement all methods required by \yii\web\IdentityInterface
      // since \thamtech\jwsauth\models\IdentityInterface extends
      // \yii\web\IdentityInterface
    }
    ```

5. Add the JsonRpcAuth filter on any \JsonRpc2\Controller you would like
   jwsauth-authenticated users to access:
    
    ```php
    public function behaviors()
    {
      return [
        'authenticator' => [
          'class' => \thamtech\jwsauth\filters\auth\JsonRpcAuth::className(),
          'except' => ['public-method-1', 'public-method-2'],
        ],
      ];
    }
    ```

Client-Side Usage
-----------------

1. Make a JSON RPC request to the authenticate method passing in a username
   and password.
   
    ```
    http://yoursite/user
    ```
    with data
    ```javascript
    {
      "jsonrpc": "2.0",
      "id": 1,
      "method": "authenticate",
      "params": {
        "username": "YOUR-USERNAME",
        "password": "YOUR-PASSWORD"
      }
    }
    ```
    and a successful response will be something like this
    ```javascript
    {"jsonrpc":"2.0","id":1,"result":{"token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpZCI6MT-TRIMMED_FOR_BREVITY"}}
    ```

2. Make a JSON RPC request to any controller/method requiring authentication
   using the token provided in the previous step:
    
    ```
    http://yoursite/protected-controller
    ```
    with data
    ```javascript
    {
      "jsonrpc": "2.0",
      "id": 2,
      "auth": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpZCI6MT-TRIMMED_FOR_BREVITY",
      "method": "access-sensitive-data",
      "params": {"id": 27}
    }
    ```

### Expiration and Refreshing Tokens

When the token expires (after 1 hour by default), you may refresh the token
without requiring the user to re-authenticate with username and password. This
is allowed up to the refresh expiration of a token (24 hours by default).

If you have a valid token and make an authenticated request but receive a
result like the following:
```javascript
{
  "jsonrpc": "2.0",
  "id": 3,
  "error": {
    "code": -32652,
    "data": null,
    "message": "Invalid or expired token"
  }
}
```
then your next step is to try to refresh the token:
```
http://yoursite/user
```
with data
```javascript
{
  "jsonrpc": "2.0",
  "id": 4,
  "auth": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpZCI6MT-TRIMMED_FOR_BREVITY",
  "method": "refresh-token"
}
```

The response will either contain a new token which you may continue using
normally:
```javascript
{"jsonrpc":"2.0","id":4,"result":{"token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpZCI6MT-TRIMMED_FOR_BREVITY"}}
```

Or an indication that the token could not be refreshed:
```javascript
{
  "jsonrpc": "2.0",
  "id": 3,
  "error": {
    "code": -32652,
    "data": null,
    "message": "expired; user must reauthenticate"
  }
}
```

If the token could not be refreshed, then you will need to:

1. Ask the user to re-login with their username and password

2. Use the "authenticate" method in Step 1 of the Client-Side Usage section above
   to get a new auth token.

3. Continue making authenticated requests with the new token.

Advanced Usage
--------------

* You do not have to use `SimpleUserTrait` in your User identity. It is merely
  a convenience for most use cases. You are free to implement your own
  `getAuthKey()` and `findIdentityByAccessToken()` methods directly in your
  `User` identity class in a way that better suits your application's needs.

* Rather than instantiating a `UserController` as a sublcass, you could refer
  to `\thamtech\jwsauth\controllers\UserController` directly in a controller map:
  
  ```php
  [
    'controllerMap' => [
      // declares "login" controller using a class name
      'login' => 'thamtech\jwsauth\controllers\UserController',
    ],
  ]
  ```

See Also
--------

* [cranetm/yii2-json-rpc-2.0](http://github.com/cranetm/yii2-json-rpc-2.0) - Yii 2
  extension that helps turn your Controllers into JSON RPC 2.0 APIs.
  
* [namshi/jose](http://github.com/namshi/jose) - PHP implementation of the
  JWS (JSON Web Signature) specification.
  
* [JSON Web Signature (JWS)](https://tools.ietf.org/html/rfc7515) - JWS specifications
