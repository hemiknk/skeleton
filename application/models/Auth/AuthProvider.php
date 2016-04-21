<?php
/**
 * Hybrid Auth integration
 * @author yuklia <yuliakostrikova@gmail.com>
 */
namespace Application\Auth;

use Bluz\Application\Exception\ApplicationException;
use Bluz\Proxy\Config;
use Bluz\Proxy\Messages;
use Application\Auth;
use Application\Users;

/**
 * Class AuthProvider
 * @package Application\Auth
 */
class AuthProvider implements AuthInterface
{
    /** @var \Application\Bootstrap */
    protected $response;

    /** @var \Application\Users\Row $identity */
    protected $identity;

    /** @var \Hybrid_Auth $hybridauth */
    protected $hybridauth;

    /** @var \Hybrid_Provider_Adapter $authAdapter */
    protected $authAdapter;

    /**
     * the same name as was mentioned in hybridauth config section providers
     * @var string
     */
    protected $providerName;

    public function __construct($providerName)
    {
        if (!in_array(ucfirst($providerName), $this->getAvailableProviders())) {
            throw new ApplicationException(sprintf('Provider % is not defined
            in configuration file', ucfirst($providerName)));
        }
        $this->providerName = ucfirst($providerName);
    }


    /**
     * @return \Hybrid_Auth
     */
    public function getHybridauth()
    {
        if (!$this->hybridauth) {
            $this->hybridauth = new \Hybrid_Auth($this->getOptions());
        }

        return $this->hybridauth;
    }

    public function setHybridauth($hybridauth)
    {
        $this->hybridauth = $hybridauth;
    }


    /**
     * @param \Bluz\Application\Application $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return \Application\Bootstrap
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param \Application\Users\Row $identity
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;
    }

    /**
     * @return \Application\Users\Row $user
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return $this->providerName;
    }

    /**
     * @param string $providerName
     */
    public function setProviderName($providerName)
    {
        $this->providerName = $providerName;
    }

    /**
     * @return \Hybrid_Provider_Adapter
     * @throws \Exception
     */
    public function getAuthAdapter()
    {
        if (!$this->authAdapter) {
            /** @var \Hybrid_Provider_Adapter $authProvider */
            $this->authAdapter = $this->getHybridauth()->authenticate($this->providerName);

            if (!$this->authAdapter->isUserConnected()) {
                throw new \Exception('Cannot connect to current provider !');
            }
        }

        return $this->authAdapter;
    }

    /**
     * @param \Hybrid_Provider_Adapter $authAdapter
     */
    public function setAuthAdapter($authAdapter)
    {
        $this->authAdapter = $authAdapter;
    }

    /**
     * @param \Hybrid_User_Profile $data
     * @param  \Application\Users\Row $user
     * @return void
     */
    public function registration($data, $user)
    {
        $row = new Auth\Row();
        $row->userId = $user->id;
        $row->provider = strtolower($this->providerName);

        $row->foreignKey = $data->identifier;
        $row->token = $this->authAdapter->getAccessToken()['access_token'];
        $row->tokenSecret = ($this->authAdapter->getAccessToken()['access_token_secret']) ? : '';
        $row->tokenType = Auth\Table::TYPE_ACCESS;
        $row->save();

        Messages::addNotice(sprintf('Your account was linked to %s successfully !', $this->providerName));
        $this->response->redirectTo('users', 'profile', ['id' => $user->id]);
    }

    /**
     * @return void
     */
    public function authProcess()
    {


        $opts = $this->getOptions();

        $url = "http://jethub.nixsolutions.com:8080/hub/api/rest/oauth2/auth";

        $client_id = $opts['providers']['Jethub']['keys']['id'];
        $client_secret = $opts['providers']['Jethub']['keys']['secret'];
        $redirect_uri = $opts['base_url'];


        $params = array(
            'redirect_uri'  => $redirect_uri,
            'response_type' => $opts['providers']['Jethub']['response_type'],
            'client_id'     => $client_id,
            'scope'         => $opts['providers']['Jethub']['scope'],
            /*'request_credentials' => 'skip',*/
        );

        $link =  $url . '?' . urldecode(http_build_query($params));

        return $link;




/*        $this->authAdapter = $this->getAuthAdapter();
        $profile = $this->getProfile();*/

        /**
         * @var Auth\Table $authTable
         */
/*        $authTable = Auth\Table::getInstance();
        $auth = $authTable->getAuthRow(strtolower($this->providerName), $profile->identifier);


        if ($this->identity) {
            if ($auth) {
                Messages::addNotice(sprintf('You have already linked to %s', $this->providerName));
                $this->response->redirectTo('users', 'profile', ['id' => $this->identity->id]);
            } else {
                $user = Users\Table::findRow($this->identity->id);
                $this->registration($profile, $user);
            }
        }

        if ($auth) {
            $this->alreadyRegisteredLogic($auth);
        } else {
            Messages::addError(sprintf('First you need to be linked to %s', $this->providerName));
            $this->response->redirectTo('users', 'signin');
        }*/
    }

    /**
     * @return array
     * @throws \Application\Exception
     */
    public function getOptions()
    {
        return Config::getData('hybridauth');
    }

    /**
     * @return array
     */
    public function getAvailableProviders()
    {
        return array_keys(Config::getData('hybridauth')['providers']);
    }

    /**
     * @param $auth
     * @return mixed
     */
    public function alreadyRegisteredLogic($auth)
    {
        $user = Users\Table::findRow($auth->userId);

        if ($user->status != Users\Table::STATUS_ACTIVE) {
            Messages::addError('User is not active');
        }

        $user->tryLogin();
        $this->response->redirectTo('index', 'index');
    }

    /**
     * @return \Hybrid_User_Profile
     */
    public function getProfile()
    {
        return $this->authAdapter->getUserProfile();
    }
}
