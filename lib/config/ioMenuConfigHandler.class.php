<?php
/**
 * define ioMenus in a nifty yaml file that could be cached
 *
 * @see navigation.sample.yml
 * @author digitalkaoz
 */
class ioMenuConfigHandler extends sfYamlConfigHandler
{
  private $buffer = "<?php\n";
  private $menus;

  /**
   * executes the config files
   *
   * @param array $configFiles
   */
  public function execute($configFiles)
  {
    $config = $this->parseYamls($configFiles);

    if (!$config) {
      return;
    }

    $this->iterateMenus($config);

    return $this->buffer;
  }

  /**
   * iterate over the various menus
   *
   * @param array $config
   */
  protected function iterateMenus($config)
  {
    array_walk($config, array($this, 'parseMenu'));
  }

  /**
   * iterate over various items within a menu
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
   * @todo
   */
  protected function parseItem($item, $name, $menu, $data=null, $root=null)
  {
    $this->buffer .= $this->addItem($item, $menu, $root);

    if (array_key_exists('children', $item)) {
      foreach ($item['children'] as $childName => $child) {
        $this->parseItem($child, $childName, $menu, $data, $item);
      }
    }
  }

  /**
   * parses the attributes to a string for menus and items
   *
   * @param array $item
   * @param string $menu
   * @param boolean $keys
   * @return string
   */
  protected function parseAttributes($item, $menu=false, $keys=true) {
    $attrs = array();

    if ($menu) {
      $attrs = isset($item['_attributes']) ? $item['_attributes'] : $this->menus[$menu]['item_attributes'];
    } else {
      $attrs = isset($item['menu_attributes']) ? $item['menu_attributes'] : array();
    }

    if ($keys) {
      return str_replace('"', "'", var_export($attrs, true));
    } else {
      return str_replace('"', "'", $this->var_export_nokeys($attrs));
    }
  }

  /**
   * adds a item to the buffer
   *
   * @param array $item
   * @param string $menu
   * @param array $anchor
   */
  protected function addItem($item, $menu, $anchor = null) {
    $attrs = $this->parseAttributes($item, $menu);

    $this->buffer .= sprintf("\$item_%s = new ioMenuItem('%s','@%s',%s);\n", $item['route'], $item['name'], $item['route'], $attrs);

    if ($credentials = $this->getCredentials($item)) {
      $this->buffer .= sprintf("\$item_%s->setCredentials(%s);\n", $item['route'], $credentials);
    }

    if ($anchor) {
      $this->buffer .= sprintf("\$item_%s->addChild(\$item_%s);\n", $anchor['route'], $item['route']);
    } else {
      $this->buffer .= sprintf("\$%s->addChild(\$item_%s);\n", $menu, $item['route']);
    }
  }

  /**
   * get the security settings for a route
   *
   * @param sfRoute $route
   * @return array
   */
  protected function getSecurityForRoute(sfRoute $route) {
    //bad dependency
    $config = sfContext::getInstance()->getConfiguration();
    $route_defaults = $route->getDefaults();

    $moduleCfg = $config->getRootDir() . '/apps/' . $config->getApplication() . '/modules/' . $route_defaults['module'] . '/config/security.yml';
    //TODO
    $pluginCfg = $config->getRootDir() . '/plugins/' . $config->getApplication() . '/modules/' . $route_defaults['module'] . '/config/security.yml';
    $appCfg = $config->getRootDir() . '/apps/' . $config->getApplication() . '/config/security.yml';

    if (file_exists($moduleCfg)) {
      $file = $moduleCfg;
    } elseif (file_exists($pluginCfg)) {
      //TODO
    } else {
      $file = $appCfg;
    }

    return sfSecurityConfigHandler::getConfiguration(array($file));
  }

  /**
   * get the credentials for an item
   *
   * @param array $item
   * @return mixed
   */
  protected function getCredentials($item) {
    //bad sfContext dependencies
    $routes = sfContext::getInstance()->getRouting()->getRoutes();

    if (!array_key_exists(substr($item['route'], 0, strpos($item['route'], '?')), $routes)) {
      return false;
    }

    $route = $routes[$item['route']];

    if ($security = $this->getSecurityForRoute($route)) {
      $defaults = $route->getDefaults();

      foreach (array('action', 'all', 'default') as $key) {
        if (!isset($defaults[$key])) {
          continue;
        }
        $cfg = $defaults[$key];

        if (array_key_exists($cfg, $security)) {
          if ($security[$cfg]['is_secure'] == 'on') {
            $config = isset($security[$cfg]['credentials']) ? $security[$cfg]['credentials'] : false;
          }
        }
      }
    }

    return isset($config) ? $config : false;
  }

  protected function var_export_nokeys($obj) {
    return preg_replace("/'?\w+'?\s+=>\s+/", '', var_export($obj, true));
  }

}