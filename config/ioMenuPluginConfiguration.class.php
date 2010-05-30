<?php

/**
 * Plugin configuration for ioMenuPlugin
 * 
 * @package     ioMenuPlugin
 * @subpackage  config
 * @author      Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioMenuPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    // make the ioMenuItem not be escaped in the template
    sfOutputEscaper::markClassAsSafe('ioMenuItem');
  }
}