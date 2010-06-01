<?php

class ioMenuConfigHandler extends sfYamlConfigHandler
{
  private $buffer = "<?php\n";
  private $menus;

  public function execute($configFiles)
  {
    $config = $this->parseYamls($configFiles);

	if(!$config){
	  return;
	}
	
	$this->iterateMenus($config);

	return $this->buffer;
  }

  protected function iterateMenus($config){
	array_walk($config, array($this,'parseMenu'));
  }

  protected function iterateItems($menu,$name){
	array_walk($menu, array($this,'parseItem'),$name);
  }

  protected function parseMenu($menu, $name){
	$this->menus[$name] = $menu;

    $this->buffer .= sprintf("\n$%s = new ioMenu(%s);\n",$name,$this->parseAttributes($menu));
	
	$this->iterateItems($menu['items'],$name);
  }

  protected function parseItem($item,$name,$menu, $data=null, $root=null)
  {
    $this->buffer .= $this->addItem($item,$menu, $root);

	if(array_key_exists('children', $item))
	{
	  foreach($item['children'] as $childName=>$child)
	  {
		$this->parseItem($child,$childName,$menu, $data, $item);
	  }
	}
  }

  protected function parseAttributes($item, $menu=false, $keys=true)
  {
	$attrs = array();
	
	if($menu)
	{
	  $attrs = isset($item['_attributes']) ? $item['_attributes'] : $this->menus[$menu]['item_attributes'];
	}
	else
	{
	  $attrs = isset($item['menu_attributes']) ? $item['menu_attributes'] : array();
	}

	if($keys)
	{
	  return str_replace('"', "'", var_export($attrs,true));
	}
	else
	{
	  return str_replace('"', "'", $this->var_export_nokeys($attrs));
	}
  }

  protected function addItem($item,$menu, $anchor = null){
	$attrs = $this->parseAttributes($item, $menu);

    $this->buffer .= sprintf("\$item_%s = new ioMenuItem('%s','@%s',%s);\n",$item['route'],$item['name'],$item['route'],$attrs);

    if($credentials = $this->getCredentials($item))
    {
      $this->buffer .= sprintf("\$item_%s->setCredentials(%s);\n",$item['route'],$credentials);
    }

    if($anchor)
    {
      $this->buffer .= sprintf("\$item_%s->addChild(\$item_%s);\n",$anchor['route'],$item['route']);
    }
    else
    {
      $this->buffer .= sprintf("\$%s->addChild(\$item_%s);\n",$menu,$item['route']);
    }
  }

  protected function getSecurityForRoute(sfRoute $route)
  {
	//bad dependency
    $config = sfContext::getInstance()->getConfiguration();
    $route_defaults = $route->getDefaults();

    $moduleCfg = $config->getRootDir().'/apps/'.$config->getApplication().'/modules/'.$route_defaults['module'].'/config/security.yml';
	//TODO
	$pluginCfg = $config->getRootDir().'/plugins/'.$config->getApplication().'/modules/'.$route_defaults['module'].'/config/security.yml';
	$appCfg = $config->getRootDir().'/apps/'.$config->getApplication().'/config/security.yml';

	if(file_exists($moduleCfg)){
	  $file = $moduleCfg;
	}elseif(file_exists($pluginCfg)){

	}else{
	  $file = $appCfg;
	}

	return sfSecurityConfigHandler::getConfiguration(array($file));
  }

  protected function getCredentials($item)
  {
	//bad sfContext dependencies
	$routes = sfContext::getInstance()->getRouting()->getRoutes();

    if(!array_key_exists(substr($item['route'], 0, strpos($item['route'], '?')),$routes))
    {
      return false;
    }

	$route = $routes[$item['route']];

	if($security = $this->getSecurityForRoute($route))
	{
	  $defaults = $route->getDefaults();

	  foreach(array('action','all','default') as $key)
	  {
		if(!isset($defaults[$key])){
		  continue;
		}
		$cfg = $defaults[$key];

		if(array_key_exists($cfg,$security))
		{
		  if($security[$cfg]['is_secure'] == 'on')
		  {
			$config = isset($security[$cfg]['credentials']) ? $security[$cfg]['credentials'] : false;
		  }
		}
	  }
	}

    return isset($config) ? $config : false;
  }

  protected function var_export_nokeys ($obj)
  {
    return preg_replace("/'?\w+'?\s+=>\s+/", '', var_export($obj, true));
  }
}