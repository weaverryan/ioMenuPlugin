<?php

/**
 * A convenience class for creating the root node of a menu.
 *
 * When creating the root menu object, you can use this class or the
 * normal ioMenuItem class. For example, the following are equivalent:
 *   $menu = new ioMenu(array('class' => 'root'));
 *   $menu = new ioMenuItem(null, null, array('class' => 'root'));
 * 
 * @package     ioMenuPlugin
 * @subpackage  menu
 * @author      Ryan Weaver <ryan@thatsquality.com>
 */
class ioMenu extends ioMenuItem
{

  /**
   * @var string
   */
  protected $_childClass;

  /**
   * Class constructor
   * 
   * @see ioMenuItem
   * @param array   $options
   * @param string  $childClass The class to use if instantiating children menu items
   */
  public function __construct($options = array(), $childClass = 'ioMenuItem')
  {
    $this->_childClass = $childClass;
     
    parent::__construct(null, null, $options);
  }

  /**
   * Overridden to specify what the child class should be
   */
  protected function _createChild($name, $route = null, $attributes = array(), $class = null)
  {
    if ($class === null)
    {
      $class = $this->_childClass;
    }

    return parent::_createChild($name, $route, $attributes, $class);
  }
}