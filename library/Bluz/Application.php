<?php

/**
 * Copyright (c) 2012 by Bluz PHP Team
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @namespace
 */
namespace Bluz;

use Bluz\Acl\Acl;
use Bluz\Auth\Auth;
use Bluz\Cache\Cache;
use Bluz\Config\Config;
use Bluz\Db\Db;
use Bluz\EventManager\EventManager;
use Bluz\Registry\Registry;
use Bluz\Request;
use Bluz\Router\Router;
use Bluz\Session\Session;
use Bluz\View\Layout;
use Bluz\View\View;

/**
 * Application
 *
 * @category Bluz
 * @package  Loader
 *
 * <code>
 *
 *
 * </code>
 *
 * @author   Anton Shevchuk
 * @created  06.07.11 16:25
 */
class Application
{
    /**
     * @var Acl
     */
    protected $_acl;

    /**
     * @var Auth
     */
    protected $_auth;

    /**
     * @var Cache
     */
    protected $_cache;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var Db
     */
    protected $_db;

    /**
     * @var EventManager
     */
    protected $_eventManager;

    /**
     * @var Loader
     */
    protected $_loader;

    /**
     * @var Layout
     */
    protected $_layout;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Request\AbstractRequest
     */
    protected $_request;

    /**
     * @var Router
     */
    protected $_router;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var string
     */
    protected $_environment;

    /**
     * Use layout flag
     * @var boolean
     */
    protected $_layoutFlag = true;

    /**
     * JSON response flag
     * @var boolean
     */
    protected $_jsonFlag = false;

    /**
     * Messages
     * @var array
     */
    protected $_messages = array(
        'notice' => array(),
        'warning' => array(),
        'error' => array(),
    );

    /**
     * Widgets closures
     * @var array
     */
    protected $_widgets = array();

    /**
     * init
     *
     * @param string $environment Array format only!
     * @return Application
     */
    public function init($environment = ENVIRONMENT_PRODUCTION)
    {

        $this->_environment = $environment;

        try {
            $this->getConfig($environment);
            $this->getLoader();

            $this->log(__METHOD__);

            $this->getCache();
            $this->getRegistry();
            $this->getSession();
            $this->getAuth();
            $this->getAcl();
            $this->getDb();
        } catch (Exception $e) {
            throw new Exception("Application can't be loaded: ". $e->getMessage());
        }
        return $this;
    }

    /**
     * log message, working with profiler
     *
     * @param  string $message
     * @return void
     */
    public function log($message)
    {
        $this->getEventManager()->trigger('log', $message);
    }

    /**
     * load config file
     *
     * @param string|null $environment
     * @return Config
     */
    public function getConfig($environment = null)
    {
        if (!$this->_config) {
            $this->_config = new Config();
            $this->_config->load($environment);
        }
        return $this->_config;
    }

    /**
     * config
     *
     * @param string|null $section of config
     * @param string|null $subsection of config
     * @return array
     */
    public function getConfigData($section = null, $subsection = null)
    {
        return $this->getConfig()->get($section, $subsection);
    }

    /**
     * getLoader
     *
     * @return Loader
     */
    public function getLoader()
    {
        if (!$this->_loader) {
            $this->_loader = new Loader();

            $conf = $this->getConfigData('loader');
            if (isset($conf['namespaces'])) {
                foreach ($conf['namespaces'] as $ns => $path) {
                    $this->_loader -> registerNamespace($ns, $path);
                }
            }
            if (isset($conf['prefixes'])) {
                foreach ($conf['prefixes'] as $prefix => $path) {
                    $this->_loader -> registerPrefix($prefix, $path);
                }
            }

            $this->_loader -> register();
        }
        return $this->_loader;
    }

    /**
     * getAcl
     *
     * @return Acl
     */
    public function getAcl()
    {
        if (!$this->_acl) {
            $this->_acl = new Acl();
            $this->_acl->setApplication($this);
        }
        return $this->_acl;
    }

    /**
     * getAuth
     *
     * @return Auth
     */
    public function getAuth()
    {
        if (!$this->_auth && $conf = $this->getConfigData('auth')) {
            $this->_auth = new Auth($conf);
            $this->_auth->setApplication($this);
        }
        return $this->_auth;
    }

    /**
     * getCache
     *
     * @return Cache
     */
    public function getCache()
    {
        if (!$this->_cache) {
            $this->_cache = new Cache($this->getConfigData('cache'));
            $this->_cache->setApplication($this);
        }
        return $this->_cache;
    }

    /**
     * getDb
     *
     * @return Db
     */
    public function getDb()
    {
        if (!$this->_db && $conf = $this->getConfigData('db')) {
            $this->_db = new Db($conf);
            $this->_db->setApplication($this);
        }
        return $this->_db;
    }

    /**
     * getEventManager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        if (!$this->_eventManager) {
            $this->_eventManager = new EventManager();
        }
        return $this->_eventManager;
    }

    /**
     * getRegistry
     *
     * @return Registry
     */
    public function getRegistry()
    {
        if (!$this->_registry && $conf = $this->getConfigData('registry')) {
            $this->_registry = new Registry($conf);
            $this->_registry->setApplication($this);
        }
        return $this->_registry;
    }

    /**
     * getRequest
     *
     * @return Request\HttpRequest|Request\CliRequest
     */
    public function getRequest()
    {
        if (!$this->_request) {
            if ('cli' == PHP_SAPI) {
                $this->_request = new Request\CliRequest($this->getConfigData('request'));
            } else {
                $this->_request = new Request\HttpRequest($this->getConfigData('request'));
            }

            $this->_request->setApplication($this);

            if ($this->_request->isXmlHttpRequest()) {
                $this->useLayout(false);
            }
        }
        return $this->_request;
    }

    /**
     * getRouter
     *
     * @return Router
     */
    public function getRouter()
    {
        if (!$this->_router) {
            $this->_router = new Router($this->getConfigData('router'));
            $this->_router->setApplication($this);
        }
        return $this->_router;
    }

    /**
     * getSession
     *
     * @return Session
     */
    public function getSession()
    {
        if (!$this->_session) {
            $this->_session = new Session($this->getConfigData('session'));
            $this->_session->setApplication($this);
            $this->_session->start();

            if ($this->_session->_messages) {
                $this->_messages = $this->_session->_messages;
                $this->_session->_messages = null;
            }
        }
        return $this->_session;
    }

    /**
     * getLayout
     *
     * @return Layout
     */
    public function getLayout()
    {
        if (!$this->_layout && $conf = $this->getConfigData('layout')) {
            $this->_layout = new Layout($conf);
            $this->_layout->setApplication($this);
        }
        return $this->_layout;
    }

    /**
     * add notice
     *
     * @param string $text
     * @return Application
     */
    public function addNotice($text)
    {
        $this->_messages['info'][] = $text;
        return $this;
    }

    /**
     * add success
     *
     * @param string $text
     * @return Application
     */
    public function addSuccess($text)
    {
        $this->_messages['success'][] = $text;
        return $this;
    }

    /**
     * add error
     *
     * @param string $text
     * @return Application
     */
    public function addError($text)
    {
        $this->_messages['error'][] = $text;
        return $this;
    }

    /**
     * process
     *
     * @return Application
     */
    public function process()
    {
        $this->log(__METHOD__);

        $this->getRequest();

        $this->getRouter()
             ->process();

        if ($this->_request->getParam('json')) {
            $this->useJson(true);
        }
        $layout = $this->getLayout();


        /* @var View $ControllerView */
        try {
            $controllerView = $this->dispatch(
                $this->_request->module(),
                $this->_request->controller(),
                $this->_request->getParams()
            );

            // move vars from layout to view instance
            if ($controllerView instanceof View) {
                $controllerView -> setData($this->getLayout()->toArray());
            }

            if (!$this->_layoutFlag) {
                $this->_layout = $layout = $controllerView;
            } else {
                $layout->setContent($controllerView);
            }
        } catch (Exception $e) {
            $controllerView = $this->dispatch('error', 'error', array(
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ));
            $layout->setContent($controllerView);
        }

        if (!$layout instanceof \Closure) {
            $layout->_messages = $this->_messages;
        }
        return $this;
    }

    /**
     * useLayout
     *
     * @param boolean|string $flag
     * @return Application
     */
    public function useLayout($flag = true)
    {
        if (is_string($flag)) {
            $this->getLayout()->setTemplate($flag);
            $this->_layoutFlag = true;
        } else {
            $this->_layoutFlag = $flag;
        }
        return $this;
    }

    /**
     * useJson
     *
     * @param boolean $flag
     * @return Application
     */
    public function useJson($flag = true)
    {
        if ($flag) {
            $this->useLayout(false);
        }
        $this->_jsonFlag = $flag;
        return $this;
    }

    /**
     * render
     *
     * @return Application
     */
    public function render()
    {
        $this->log(__METHOD__);

        $layout = $this->getLayout();

        if ('cli' == PHP_SAPI) {
            $data = $layout->toArray();
            foreach ($data as $key => $value) {
                if (strpos($key, '_') === 0) {
                    echo "\033[1;31m$key\033[m:\n";
                } else {
                    echo "\033[1;33m$key\033[m:\n";
                }
                var_dump($value);
                echo "\n";
            }
        } else {
            if ($this->_jsonFlag) {
                header('Content-type: application/json');
                echo json_encode($layout->toArray());
            } else {
                echo ($layout instanceof \Closure) ? $layout(): $layout;
            }
        }
        return $this;
    }

    /**
     * reflection for anonymous function
     *
     * @param string  $uid
     * @param closure $closure
     * @return array
     */
    public function reflection($uid, $closure)
    {
        // cache for reflection data
        if (!$data = $this->getCache()->get('Reflection: '.$uid)) {

            $reflection = new \ReflectionFunction($closure);

            // check and normalize params by doc comment
            $docComment = $reflection->getDocComment();
            preg_match_all('/\s*\*\s*\@param\s+(bool|boolean|int|integer|float|string|array)\s+\$([a-z0-9_]+)/i', $docComment, $matches);

            // init data
            $data = array(
                'resourceType'  => null,
                'resourceParam' => null,
                'privilege'     => null
            );

            // rebuild array
            $data['types'] = array();
            foreach ($matches[1] as $i => $type) {
                $data['types'][$matches[2][$i]] = $type;
            }

            $data['params'] = $reflection->getParameters();

            if (preg_match('/\s*\*\s*\@cache\s+([0-9\.]+).*/i', $docComment, $matches)) {
                $data['cache'] = $matches[1];
            }
            if (preg_match('/\s*\*\s*\@privilege\s+(\w+).*/i', $docComment, $matches)) {
                $data['privilege'] = $matches[1];
            }
            if (preg_match('/\s*\*\s*\@resource\s+(\w+)(\s\w+|).*/i', $docComment, $matches)) {
                $data['resourceType'] = $matches[1];
                $data['resourceParam'] = trim($matches[2]);
            }

            $this->getCache()->set('Reflection: '.$uid, $data);
        }
        return $data;
    }

    /**
     * process params
     *
     * @param $data
     * @return array
     */
    private function params($data)
    {
        $request = $this->getRequest();
        $params = array();
        foreach ($data['params'] as $param) {
            /* @var \ReflectionParameter $param */
            if (isset($data['types'][$param->name]) && $type = $data['types'][$param->name]) {
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $params[] = (bool) $request->{$param->name};
                        break;
                    case 'int':
                    case 'integer':
                        $params[] = (int) $request->{$param->name};
                        break;
                    case 'float':
                        $params[] = (float) $request->{$param->name};
                        break;
                    case 'string':
                        $params[] = (string) $request->{$param->name};
                        break;
                    case 'array':
                        $params[] = (array) $request->{$param->name};
                        break;
                }
            } else {
                $params[] = $request->{$param->name};
            }
        }
        return $params;
    }

    /**
     * dispatch
     *
     * @param string $module
     * @param string $controller
     * @param array $params
     * @return View
     */
    public function dispatch($module, $controller, $params = array())
    {
        $this->log(__METHOD__.": ".$module.'/'.$controller);

        $app = $this;

        $request = $this->getRequest();
        $request -> setParams($params);

        $view = new View($this->getConfigData('view'));
        $view -> setPath(PATH_APPLICATION .'/modules/'. $module .'/views');
        $view -> setTemplate($controller .'.phtml');
        $view -> setApplication($this);

        $bootstrapPath = PATH_APPLICATION .'/modules/' . $module .'/bootstrap.php';

        /**
         * @var closure $bootstrap
         */
        if (file_exists($bootstrapPath)) {
            $bootstrap = require $bootstrapPath;
        } else {
            $bootstrap = null;
        }

        $controllerFile = $this->getControllerFile($module, $controller);
        /**
         * @var closure $controllerClosure
         */
        $controllerClosure = include $controllerFile;

        if (!is_callable($controllerClosure)) {
            throw new Exception("Controller is not callable '$module/$controller'");
        }

        $data = $this->reflection($controllerFile, $controllerClosure);

        // check acl
        if (!$this->isAllowedController($module, $controller, $params)) {
            throw new Exception('You don\'t have permissions', 403);
        }


        $params = $this->params($data);

        // load html from cache file
        if (isset($data['cache'])) {
            if ($view->cache($data['cache'], $params)) {
                return $view;
            }
        };

        $result = call_user_func_array($controllerClosure, $params);

        // return false is equal to disable layout
        if ($result === false) {
            $result = function(){};
        }

        if ($result) {
            if (!is_callable($result)) {
                throw new Exception("Controller result is not callable '$module/$controller'");
            }
            return $result;
        } else {
            return $view;
        }
    }

    /**
     * widget
     *
     * @param string $module
     * @param string $widget
     * @param array $params
     * @return \Closure
     */
    public function widget($module, $widget, $params = array())
    {
        $this->log(__METHOD__.": ".$module.'/'.$widget);

        $app = $this;

        /**
         * Cachable widgets
         * @var \Closure $widgetClosure
         */
        if (isset($this->_widgets[$module])
            && isset($this->_widgets[$module][$widget])) {
            $widgetClosure = $this->_widgets[$module][$widget];
        } else {
            $widgetClosure = require $this->getWidgetFile($module, $widget);

            if (!isset($this->_widgets[$module])) {
                $this->_widgets[$module] = array();
            }
            $this->_widgets[$module][$widget] = $widgetClosure;
        }

        if (!is_callable($widgetClosure)) {
            throw new Exception("Widget is not callable '$module/$widget'");
        }

        // TODO: check acl and other docs information
//        $data = $this->reflection($module."/widgets/".$widget, $widgetClosure);
        return $widgetClosure;
    }

    /**
     * Is allowed controller
     *
     * @param string $module
     * @param string $controller
     * @param array  $params
     * @return boolean
     */
    public function isAllowedController($module, $controller, array $params = array())
    {
        return true;
        $controllerFile = $this->getControllerFile($module, $controller);

        $app = $bootstrap = $request = $view = null;
        $data = $this->reflection($controllerFile, include $controllerFile);

        if (!empty($data['resourceType']) || !empty($data['privilege'])) {
            $resourceId = null;
            if (!empty($data['resourceParam'])) {
                $resourceId = $params[$data['resourceParam']];
            }
            return $this->getAcl()->isAllowed($data['resourceType'], $resourceId, $data['privilege']);
        }
        return true;
    }

    /**
     * Get controller file
     *
     * @param  string $module
     * @param  string $controller
     * @return \Closure
     * @throws Exception
     */
    public function getControllerFile($module, $controller)
    {
        $controllerPath = PATH_APPLICATION . '/modules/' . $module
                        .'/controllers/' . $controller .'.php';

        if (!file_exists($controllerPath)) {
            throw new Exception("Controller not found '$module/$controller'");
        }

        return $controllerPath;
    }

    /**
     * Get widget file
     *
     * @param  string $module
     * @param  string $widget
     * @return \Closure
     * @throws Exception
     */
    public function getWidgetFile($module, $widget)
    {
        $widgetPath = PATH_APPLICATION . '/modules/' . $module
                        .'/widgets/' . $widget .'.php';

        if (!file_exists($widgetPath)) {
            throw new Exception("Widget not found '$module/$widget'");
        }

        return $widgetPath;
    }

    /**
     * redirect
     *
     * @param string $url
     * @return void
     */
    public function redirect($url)
    {
        if (!headers_sent($file, $line)) {
            // save notification to session
            $this->getSession()->_messages = $this->_messages;

            header('Location: '.$url);
            exit();
        } else {
            throw new Exception("Headers already sent by $file:$line");
        }
    }

    /**
     * redirect
     *
     * @param string $module
     * @param string $controller
     * @param array $params
     * @return void
     */
    public function redirectTo($module = 'index', $controller = 'index', $params = array())
    {
        $url = $this->getRouter()->url($module, $controller, $params);
        $this->redirect($url);
    }
}