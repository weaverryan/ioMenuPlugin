<?php

/**
 * caches the ioMenus defined in the navigation.yml
 *   - you can infinite nest the menus
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
  private $menus = array();

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
    
    $this->setContext();

    if (!$config)
    {
      return;
    }

    $this->iterateMenus($config);

    return $this->buffer;
  }

  /**
   * iterate over the defined menus
   *
   * @param array $config
   */
  protected function iterateMenus($config)
  {
    array_walk($config, array($this, 'parseMenu'));
  }

  /**
   * iterate over items within a menu
   *
   * @param array $menu
   * @param string $name
   */
  protected function iterateItems($menu, $name)
  {
    array_walk($menu, array($this, 'parseItem'), $name);
  }

  /**
   * parses a menu configuration
   *
   * @param array $menu
   * @param string $name
   */
  protected function parseMenu($menu, $name)
  {
    $this->menus[$name] = $menu;

    $this->buffer .= sprintf("\n$%s = new ioMenu(%s);\n", $name, $this->parseAttributes($menu));

    $this->iterateItems($menu['items'], $name);
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
  protected function parseItem($item, $name, $menu, $anchor=null)
  {
    $this->createItem($item, $menu);
    $this->addItem($item, $menu, $anchor);

    //recursivly scan and add child items
    if (array_key_exists('children', $item))
    {
      foreach ($item['children'] as $childName => $child)
      {
        $this->parseItem($child, $childName, $menu, $item);
      }
    }
  }

  /**
   * creates a menu item
   *
   * @param array $item
   * @param string $menu
   */
  protected function createItem($item, $menu)
  {
    $attrs = $this->parseAttributes($item, $menu);

    $this->buffer .= sprintf("\$item_%s = new ioMenuItem('%s','@%s',%s);\n", $item['route'], $item['name'], $item['route'], $attrs);

    if ($credentials = $this->getCredentials($item))
    {
      $this->buffer .= sprintf("\$item_%s->setCredentials(%s);\n", $item['route'], $this->var_export_nokeys($credentials));
    }
  }

  /**
   * adds a menu item to another item or to a menu
   *
   * @param array $item
   * @param string $menu
   * @param array $anchor
   */
  protected function addItem($item, $menu, $anchor=false)
  {
    if ($anchor)
    {
      $this->buffer .= sprintf("\$item_%s->addChild(\$item_%s);\n", $anchor['route'], $item['route']);
    }
    else
    {
      $this->buffer .= sprintf("\$%s->addChild(\$item_%s);\n", $menu, $item['route']);
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
      $routeName = substr($routeName, 0, strpos($$routeName, '?'));
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
      return false;
    }

    $security = $this->getSecurityConfigForRoute($route);

    $route_defaults = $route->getDefaultParameters();
    $action = $route_defaults['action'];

    foreach(array($action,'all','default') as $dataset)
    {
      if(isset($security[$dataset]) && $security[$dataset]['is_secure'] == 'on')
      {
        $set = isset($security[$dataset]['credentials']) ? $security[$dataset]['credentials'] : false;
      }
    }

    return isset($set) ? $set : false;
  }

  /**
   * parses the attributes to a string for menus and items
   *
   * @param array $item
   * @param string $menu
   * @param boolean $keys
   * @return string
   */
  protected function parseAttributes($item, $menu=false, $keys=true)
  {
    $attrs = $this->getAttributesFromItem($item, $menu);

    return $this->exportAttributes($attrs, $keys);
  }

  /**
   * parses an array to a string representation, with or without keys
   *
   * @param array $attrs
   * @param string $keys
   * @return string
   */
  protected function exportAttributes($attrs, $keys)
  {
    if ($keys)
    {
      return str_replace('"', "'", var_export($attrs, true));
    }
    else
    {
      return str_replace('"', "'", $this->var_export_nokeys($attrs));
    }
  }

  /**
   * read the attributes from an item (works for items and menus) and respects the configuration cascade
   *
   * @param array $item
   * @param string $menu
   * @return array
   */
  protected function getAttributesFromItem($item, $menu=false)
  {
    $attrs = array();

    if ($menu)
    {
      $attrs = isset($item['_attributes']) ? $item['_attributes'] : $this->menus[$menu]['item_attributes'];
    }
    else
    {
      $attrs = isset($item['menu_attributes']) ? $item['menu_attributes'] : array();
    }

    return $attrs;
  }

  /**
   * exports an object as array without keys
   *
   * @param mixed $obj
   * @return string
   */
  protected function var_export_nokeys($obj)
  {
    return preg_replace("/'?\w+'?\s+=>\s+/", '', var_export($obj, true));
  }

}