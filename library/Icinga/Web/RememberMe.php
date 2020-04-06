<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Config;
use Icinga\Authentication\Auth;
use Icinga\Crypt\RSA;
use Icinga\Rememberme\Common\Database;
use Icinga\User;
use ipl\Sql\Select;

class RememberMe
{
use Database;

    /**
     * Constant cookie
     */
    const COOKIE = 'remember-me';

    /**
     * @var string
     */
    protected $encryptedPassword;

    /**
     * @var string
     */
    protected $encryptedUsername;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var object
     */
    protected $rsa;

    /**
     * @var array
     */
    protected $value = [];

    /**
     * Get cookie values
     *
     * @return void|static
     */
    public static function fromCookie()
    {
        if (! isset($_COOKIE[static::COOKIE])) {

            // Do we need throw ?
            //throw new UnexpectedValueException('');
            return;
        }
        $data = explode('|', $_COOKIE[static::COOKIE]);
        $publicKey = base64_decode(array_pop($data));

        $select = (new Select())
        ->from('rememberme')
        ->columns('*')
        ->where(['public_key = ?' => $publicKey]);

        $rememberMe = new static();
        $dbData = $rememberMe->getDb()->select($select)->fetch();
        $newData = [];
        foreach ($dbData as $key => $value) {
            $newData[$key] = $value;
        }

        $rememberMe->encryptedUsername = $data[0];
        $rememberMe->encryptedPassword = $data[1];
        $rememberMe->rsa = (new RSA())->loadKey($newData['private_key'], $publicKey);

        return $rememberMe;
    }

    /**
     * Encrypt the given username, password and assign the variables
     *
     * @param $username
     * @param $password
     * @return static
     */
    public static function fromCredentials($username, $password)
    {
        $rememberMe = new static();

        $rsa = (new RSA())->loadKey(...RSA::keygen());

        $rememberMe->encryptedPassword = $rsa->encryptToBase64($password);
        $rememberMe->encryptedUsername =  $rsa->encryptToBase64($username);
        $rememberMe->username = $username;
        $rememberMe->rsa = $rsa;
        $rememberMe->value = [
                $rememberMe->encryptedUsername[0],
                $rememberMe->encryptedPassword[0],
                base64_encode($rsa->getPublicKey())
            ];

        return $rememberMe;
    }

    /**
     * Set the values for 'remember-me' cookie
     *
     * @return Cookie
     */
    public function getCookie()
    {
        return (new Cookie(static::COOKIE))
            ->setExpire(time() + 60 * 60 * 24 * 30)
            ->setHttpOnly(true)
            ->setValue(implode('|',$this->value));
    }

    /**
     * Authenticate the given username and password
     *
     * @return bool     True if authentication succeed, false if not
     *
     */
    public function authenticate()
    {
        list($username, $password) = $this->rsa->decryptFromBase64(
            $this->encryptedUsername,
            $this->encryptedPassword
        );

        $auth = Auth::getInstance();
        $authChain = $auth->getAuthChain();
        $authChain->setSkipExternalBackends(true);
        $user = new User($username);
        if (! $user->hasDomain()) {
            $user->setDomain(Config::app()->get('authentication', 'default_domain'));
        }
        $authenticated = $authChain->authenticate($user, $password);
        if ($authenticated) {
            $auth->setAuthenticated($user);
        }

        return $authenticated;
    }

    /**
     * Database insert for the given private and public key
     *
     * Save new keys in database
     */
    public function persist()
    {
        $this->getDb()->insert('rememberme', [
            'username' => $this->username,
            'private_key' => $this->rsa->getPrivateKey(),
            'public_key' => $this->rsa->getPublicKey(),
            'ctime' => date('Y-m-d H:i:s'),
            'mtime' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Delete database entry if user logout or new keys are created for the same user
     *
     * by logout, this class do not have the login data for user, so username must
     * be given as parameter to delete the user information from database.
     *
     * @param null $username
     */
    public function remove($username = null)
    {
        $this->getDb()->delete('rememberme', [
            'username = ?' => $this->username ?: $username
        ]);
    }
}