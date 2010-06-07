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
      //inject security.yml here
      $this->setSecuritySettingsForItem($data);
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
      $config->getRootDir().'/apps/'.$config->getApplication().'/config/security.yml',
      $config->getRootDir().'/apps/'.$config->getApplication().'/modules/'.$route_defaults['module'].'/config/security.yml'
    );

    foreach($files as $k => $file)
    {
      if(!file_exists($file))
      {
        unset($files[$k]);
      }
    }

    $config = sfSecurityConfigHandler::flattenConfigurationWithEnvironment(sfSecurityConfigHandler::getConfiguration($files));

    return $config;
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
    $routing = $this->context->getRouting();
    $routeName = $item['route'];
    $routeName  = str_replace('@', '', $routeName);

    if(strpos($item['route'],'://') || strpos($item['route'],'ww.') || strpos($item['route'],'#') !== false){
      return false;
    }
    elseif(strpos($routeName,'/'))
    {
      $routeName = self::replaceConstants($routeName);
      $config = $routing->parse($routeName);
      return $config['_sf_route'];
    }
    else
    {
      if(strpos($routeName, '?'))
      {
        $routeName = substr($routeName, 0, strpos($routeName, '?') ? strpos($routeName, '?') : strlen($routeName));
      }

      $routes = $routing->getRoutes();
      return isset($routes[$routeName]) ? $routes[$routeName] : false;
    }

  }

  /**
   * set the security settings for an item
   *
   * @param array $item
   * @return mixed
   */
  protected function setSecuritySettingsForItem(&$item)
  {
    $route = $this->getRouteFromItem($item);

    if(!$route)
    {
      return array();
    }

    $security = $this->getSecurityConfigForRoute($route);

    if(isset($security['credentials']))
    {
      $item['credentials'] = isset($item['credentials']) ? $item['credentials'] : $security['credentials'];
    }

    if($security['is_secure'])
    {
      $item['requires_auth'] = isset($item['requires_auth']) ? $item['requires_auth'] : true;
    }
  }

}