<?php

/**
 * caches the ioMenus defined in the navigation.yml
 *   - you can infinite nest the menus
 *   - it has a fluent interface to ioMenu::createFromArray
 *   - seamless fetching of security settings from security.yml
 *
 * @package     ioMenuPlugin
 * @subpackage  config
 * @author      Robert Schönthal <seroscho@googlemail.com>
 * @see         navigation.sample.yml for configuration hints
 */
class ioMenuConfigHandler extends sfYamlConfigHandler
{
  /**
   * holds the config cache buffer
   *
   * @var string
   */
  private $buffer = "<?php\n";

  /**
   * holds the menu instances
   *
   * @var array
   */
  public $menus = array();

  /**
   * the sfContext
   *
   * @var sfContext
   */
  private $context;

  /**
   * executes the config files
   *
   * @param array $configFiles
   */
  public function execute($configFiles)
  {
    $config = $this->parseYamls($configFiles);
    //$config = sfYaml::load($configFiles);
    
    $this->setContext();

    if (!$config)
    {
      return false;
    }

    $this->iterateMenus($config);

    return $this->buffer;
  }

  /**
   * iterate over the defined menus
   *
   * @param array $config
   */
  protected function iterateMenus(&$config)
  {
    if(is_array($config))
    {
      array_walk($config, array($this, 'parseMenu'));
    }
    else
    {
      $this->parseMenu($config, 'menu');
    }
  }

  /**
   * parses a menu configuration
   *
   * @param array $menu
   * @param string $name
   */
  protected function parseMenu(&$menu, $name)
  {
    $this->menus[$name] = $menu;

    array_walk($menu['children'], array($this,'parseItem'));

    $this->buffer .= '$'.$name.' = '.var_export($menu,true).';';
  }

  /**
   * parses a menu item
   *
   * @param array $item
   * @param string $name
   * @param string $menu
   * @param array $root
   * 
   * @todo too many parameters
   */
  protected function parseItem(&$data)
  {
    if(isset($data['route']))
    {
      //inject credentials for route here
      $data['credentials'] = $this->getCredentials($data);
    }

    if(isset($data['children']) && is_array($data['children']) && !empty($data['children']))
    {
      array_walk($data['children'], array($this,'parseItem'));
    }
  }

  /**
   * sets the sfContext
   *
   * @param sfContext $context
   */
  public function setContext(sfContext $context=null)
  {
    $this->context = $context ? $context : sfContext::getInstance();
  }

  /**
   * get the security settings for a route
   *
   * @param sfRoute $route
   * @return array
   */
  protected function getSecurityConfigForRoute(sfRoute $route)
  {
    $route_defaults = $route->getDefaultParameters();
    $module = $route_defaults['module'];
    $config = $this->context->getConfiguration();
    $finder = new sfFinder();

    $files = array(
      $config->getRootDir().'/apps/'.$config->getApplication().'/modules/'.$route_defaults['module'].'/config/security.yml',
      $config->getRootDir().'/apps/'.$config->getApplication().'/config/security.yml',
      //$finder->ignore_version_control()->type('file')->name($route_defaults['module'].'/config/security.yml')->in($config->getRootDir().'/plugins')
    );

    foreach($files as $k => $file)
    {
      if(!file_exists($file))
      {
        unset($files[$k]);
      }
    }

    return sfSecurityConfigHandler::getConfiguration($files);
  }

  /**
   * extracts the sfRoute from the item if exists
   *
   * @param array $item
   * @return mixed
   */
  protected function getRouteFromItem($item)
  {
    $config = $this->context->getConfiguration();
    $routes = $this->context->getRouting()->getRoutes();

    $routeName = $item['route'];

    if(strpos($routeName, '?'))
    {
      $routeName = substr($routeName, 0, strpos($routeName, '?'));
    }

    if (!array_key_exists($routeName, $routes))
    {
      return false;
    }

    return $routes[$routeName];
  }

  /**
   * get the credentials for an item
   *
   * @param array $item
   * @return mixed
   */
  protected function getCredentials($item)
  {
    $route = $this->getRouteFromItem($item);

    if(!$route)
    {
      return array();
    }

    $security = $this->getSecurityConfigForRoute($route);

    $route_defaults = $route->getDefaultParameters();
    $action = $route_defaults['action'];

    foreach(array($action,'all','default') as $dataset)
    {
      if(isset($security[$dataset]) && $security[$dataset]['is_secure'] == 'on')
      {
        $set = isset($security[$dataset]['credentials']) ? $security[$dataset]['credentials'] : array();
      }
    }

    return isset($set) ? $set : array();
  }

}