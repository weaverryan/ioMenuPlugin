<?php

/**
 * Base menu item
 * 
 * Originally taken from sympal (http://www.sympalphp.org)
 * 
 * @package     ioMenu
 * @subpackage  menu
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Ryan Weaver <ryan@thatsquality.com>
 * @version     svn:$Id$ $Author$
 */
class ioMenuItem implements ArrayAccess, Countable, IteratorAggregate
{

  /**
   * Properties on this menu item
   */
  protected
    $_name             = null,    // the name of this menu item (used for id by parent menu)
    $_label            = null,    // the label to output, name is used by default
    $_route            = null,    // the route or url to use in the anchor tag
    $_attributes       = array(), // an array of attributes for the li
    $_children         = array(), // an array of ioMenuItem children
    $_requiresAuth     = null,    // boolean to require auth to show this menu
    $_requiresNoAuth   = null,    // boolean to require NO auth to show this menu
    $_showChildren     = true,    // boolean to render the children of this menu
    $_credentials      = array(); // array of credentials needed to display this menu

  /**
   * Metadata on this menu item
   */
  protected
    $_level            = null,    // the level (depth) of this menu item
    $_num              = null,    // the order number this menu is in its parent
    $_parent           = null,    // parent ioMenuItem
    $_root             = null,    // root ioMenuItem
    $_isCurrent        = null;    // whether or not this menu item is current


  /**
   * Class constructor
   * 
   * 
   * @param string $name    The name of this menu, which is how its parent will
   *                        reference it. Also used as label if label not specified
   * @param string $route   The route/url for this menu to use. If not specified,
   *                        text will be shown without a link
   * @param array $attributes Attributes to place on the li tag of this menu item
   */
  public function __construct($name, $route = null, $attributes = array())
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('Tag', 'Url'));

    $this->_name = $name;
    $this->_route = $route;
    $this->_attributes = $attributes;
  }

  /**
   * Generates the url to this menu item based on the route
   * 
   * In case the route is totally invalid, this catches the exception
   * and sends to the raw string
   * 
   * @TODO Find a more explicit way to log if the route is invalid
   * 
   * @param array $options Options to pass to the url_for method
   */
  public function getUrl(array $options = array())
  {
    try
    {
      return url_for($this->getRoute(), $options);
    }
    catch (sfConfigurationException $e)
    {
      sfApplicationConfiguration::getActive()->getEventDispatcher()->notify(
        new sfEvent($this, 'application.log', array(
          sprintf('Cannot generate a menu url for "%s"', $this->getRoute())
        ))
      );
      
      return $this->getRoute();
    }
  }

  /**
   * @return string
   */
  public function getName()
  {
    return $this->_name;
  }

  /**
   * @param  string $name
   * @return ioMenuItem
   */
  public function setName($name)
  {
    $this->_name = $name;

    return $this;
  }

  /**
   * @return string
   */
  public function getRoute()
  {
    return $this->_route;
  }

  /**
   * Sets the route/url for a menu item
   *
   * @param  string $route The route/url to set on this menu item
   * @return ioMenuItem
   */
  public function setRoute($route)
  {
    $this->_route = $route;

    return $this;
  }

  /**
   * Returns the label that will be used to render this menu item
   *
   * Defaults to the name of no label was specified
   *
   * @return string
   */
  public function getLabel()
  {
    return ($this->_label !== null) ? $this->_label : $this->_name;
  }

  /**
   * @param  string $label The text to use when rendering this menu item
   * @return ioMenuItem
   */
  public function setLabel($label)
  {
    $this->_label = $label;

    return $this;
  }

  /**
   * @return array
   */
  public function getAttributes()
  {
    return $this->_attributes;
  }

  /**
   * @param  array $attributes 
   * @return ioMenuItem
   */
  public function setAttributes($attributes)
  {
    $this->_attributes= $attributes;

    return $this;
  }

  /**
   * @param  string $name     The name of the attribute to return
   * @param  mixed  $default  The value to return if the attribute doesn't exist
   * 
   * @return mixed
   */
  public function getAttribute($name, $default = null)
  {
    if (isset($this->_attributes[$name]))
    {
      return $this->_attributes[$name];
    }

    return $default;
  }

  public function setAttribute($name, $value)
  {
    $this->_attributes[$name] = $value;

    return $this;
  }

  /**
   * Gets or sets whether or not this menu item requires the user to
   * be authenticated in order to be shown.
   *
   * @param  boolean $bool  Optionally set whether or not this item should require auth
   * @return boolean
   */
  public function requiresAuth($bool = null)
  {
    if ($bool !== null)
    {
      $this->_requiresAuth = $bool;
    }

    return $this->_requiresAuth;
  }

  /**
   * Gets or sets whether or not this menu item requires the user to NOT
   * be authenticated in order to be shown.
   *
   * @param  boolean $bool  Optionally set whether or not this item should require NO auth
   * @return boolean
   */
  public function requiresNoAuth($bool = null)
  {
    if ($bool !== null)
    {
      $this->_requiresNoAuth = $bool;
    }

    return $this->_requiresNoAuth;
  }

  /**
   * Set the credential(s) that a user must have to display this menu item.
   *
   * The and/or logic follows what would be rendered from a security.yml
   * file. For example:
   *
   * $credentials = array('c1', 'c2');      // user must have both c1 and c2
   * $credentials = array(array('c1', c2')) // user can have either c1 or c2
   *
   * @link http://www.symfony-project.org/jobeet/1_4/Doctrine/en/13#chapter_13_sub_authorization
   * @param  mixed $credentials A string credential or array of credentials
   * @return ioMenuItem
   */
  public function setCredentials($credentials)
  {
    $this->_credentials = is_string($credentials) ? explode(',', $credentials):(array) $credentials;

    return $this;
  }

  /**
   * @return array
   */
  public function getCredentials()
  {
    return $this->_credentials;
  }
  
  /**
   * Returns and optionally sets whether or not this menu item should
   * show its children. If the $bool argument is passed, the _showChildren
   * property will be set
   * 
   * @param boolean $bool Whether to show children or not
   */
  public function showChildren($bool = null)
  {
    if ($bool !== null)
    {
      $this->_showChildren = (bool) $bool;
    }

    return $this->_showChildren;
  }

  /**
   * Add a child menu item to this menu
   *
   * @param mixed   $child    An ioMenuItem object or the name of a new menu to create
   * @param string  $route    If creating a new menu, the route for that menu
   * @param string  attributes  If creating a new menu, the attributes for that menu
   *
   * @return ioMenuItem The child menu item
   */
  public function addChild($child, $route = null, $attributes = array())
  {
    if (!$child instanceof ioMenuItem)
    {
      $child = $this->_createChild($child, $route, $attributes);
    }

    $child->setParent($this);
    $child->showChildren($this->showChildren());
    $child->setNum($this->count() + 1);

    $this->_children[$child->getName()] = $child;

    return $child;
  }

  /**
   * Returns whether or not the given/current user has permission to
   * view this current menu item
   *
   * @param sfUser $user
   * @return bool
   */
  public function checkUserAccess(sfBasicSecurityUser $user = null)
  {
    // if we're not passed a user and we have no context, bail
    if ($user === null && !sfContext::hasInstance())
    {
      return true;
    }

    if ($user === null)
    {
      $user = sfContext::getInstance()->getUser();
    }

    if ($user->isAuthenticated() && $this->requiresNoAuth())
    {
      return false;
    }

    if (!$user->isAuthenticated() && $this->requiresAuth())
    {
      return false;
    }

    return $user->hasCredential($this->getCredentials());
  }

  /**
   * Returns the level of this menu item
   *
   * @return integer
   */
  public function getLevel()
  {
    if ($this->_level === null)
    {
      $count = -2;
      $obj = $this;

      do {
      	$count++;
      } while ($obj = $obj->getParent());

      $this->_level = $count;
    }

    return $this->_level;
  }

  /**
   * Returns the root ioMenuItem of this menu tree
   *
   * @return ioMenuItem
   */
  public function getRoot()
  {
    if ($this->_root === null)
    {
      $obj = $this;
      do {
        $found = $obj;
      } while ($obj = $obj->getParent());

      $this->_root = $found;
    }

    return $this->_root;
  }

  public function getParent()
  {
    return $this->_parent;
  }

  public function setParent(ioMenuItem $parent)
  {
    return $this->_parent = $parent;
  }

  public function getChildren()
  {
    return $this->_children;
  }

  public function setChildren(array $children)
  {
    $this->_children = $children;

    return $this;
  }

  /**
   * Returns the index that this child is within its parent.
   *
   * Primarily used internally to calculate first and last
   *
   * @return integer
   */
  public function getNum()
  {
    return $this->_num;
  }

  /**
   * Sets the index that this child is within its parent.
   *
   * Primarily used internally to calculate first and last
   *
   * @return void
   */
  public function setNum($num)
  {
    $this->_num = $num;
  }

  /**
   * Creates a new ioMenuItem to be the child of this menu
   * 
   * @param string  $name
   * @param string  $route
   * @param array   $attributes
   * 
   * @return ioMenuItem
   */
  protected function _createChild($name, $route = null, $attributes)
  {
    $class = get_class($this);

    return new $class($name, $route, $attributes);
  }

  /**
   * Removes a child from this menu item
   * 
   * @param mixed $name The name of ioMenuItem instance to remove
   */
  public function removeChild($name)
  {
    $name = ($name instanceof ioMenuItem) ? $name->getName() : $name;
    
    if (isset($this->_children[$name]))
    {
      unset($this->_children[$name]);

      // reset the "num" of all children since we just shook up the child list
      $i = 0;
      foreach ($this->_children as $children)
      {
        $child->setNum(++$i);
      }
    }
  }

  /**
   * @return ioMenuItem
   */
  public function getFirstChild()
  {
    return current($this->_children);
  }

  /**
   * @return ioMenuItem
   */
  public function getLastChild()
  {
    return end($this->_children);
  }

  /**
   * @param  $name  Then name of the child menu to return
   * @return ioMenutItem
   */
  public function getChild($name)
  {
    if (!isset($this->_children[$name]))
    {
      $this->addChild($name);
    }

    return $this->_children[$name];
  }

  /**
   * Returns whether or not this menu items has viewable children
   * 
   * This menu MAY have children, but this will return false if the current
   * user does not have access to vew any of those items
   * 
   * @return boolean;
   */
  public function hasChildren()
  {
    foreach ($this->_children as $child)
    {
      if ($child->checkUserAccess())
      {
        return true;
      }
    }

    return false;
  }

  /**
   * @return string
   */
  public function __toString()
  {
    try
    {
      return (string) $this->render();
    }
    catch (Exception $e)
    {
      return $e->getMessage();
    }
  }

  /**
   * Called by the parent menu item to render this menu.
   * 
   * This renders the li tag to fit into the parent ul as well as its
   * own nested ul tag if this menu item has children
   * 
   * @return string
   */
  public function render()
  {
    if ($this->checkUserAccess())
    {
      // explode the class string into an array of classes
      $class = explode(' ', $this->getAttribute('class'));

      if ($this->isCurrent())
      {
        $class[] = 'current';
      }
      elseif ($this->isCurrentAncestor())
      {
        $class[] = 'current_ancestor';
      }

      if ($this->isFirst())
      {
        $class[] = 'first';
      }
      if ($this->isLast())
      {
        $class[] = 'last';
      }

      // retrieve the attributes and put the final class string back on it
      $attributes = $this->getAttributes();
      $attributes['class'] = implode(' ', $class);

      // render the text/link inside the li tag
      $innerHtml = $this->_route ? $this->renderLink() : $this->renderLabel();

      // if we have visible children, render them in a ul tag
      if ($this->hasChildren() && $this->showChildren())
      {
        $innerHtml .= content_tag(
          'ul',
          $this->renderChildren(),
          array('class' => 'menu_level_'.$this->getLevel())
        );
      }

      return content_tag('li', $innerHtml, $attributes);
    }
  }

  /**
   * Renders all of the children of this menu, which equates to a group
   * of li tags
   *
   * @return string
   */
  public function renderChildren()
  {
    $html = '';
    foreach ($this->_children as $child)
    {
      $html .= $child->render();
    }
    return $html;
  }

  /**
   * Renders the anchor tag for this menu item.
   *
   * If no route is specified, or if the route fails to generate, the
   * label will be output.
   *
   * @return string
   */
  public function renderLink()
  {
    if (!$route = $this->getRoute())
    {
      return $this->renderLabel();
    }

    // protected against an invalid url (e.g. myModule/myAction, which doesn't exist)
    try
    {
      return link_to($this->renderLabel(), $route, array());
    }
    catch (sfConfigurationException $e)
    {
      sfApplicationConfiguration::getActive()->getEventDispatcher()->notify(
        new sfEvent($this, 'application.log', array(
          sprintf('Cannot generate a menu url for "%s"', $this->getRoute())
        ))
      );

      return $this->renderLabel();
    }
  }

  /**
   * Renders the label of this menu, through an i18n function
   *
   * @return string
   */
  public function renderLabel()
  {
    if (sfConfig::get('sf_i18n'))
    {
      sfApplicationConfiguration::getActive()->loadHelpers('I18N');

      return __($this->getLabel());
    }

    return $this->getLabel();
  }


  /**
   * Returns the current menu item if it is a child of this menu item
   *
   * @return bool|ioMenuItem
   */
  public function getCurrent()
  {
    if ($this->isCurrent())
    {
      return $this;
    }

    foreach ($this->_children as $child)
    {
      if ($current = $child->getCurrent())
      {
        return $current;
      }
    }

    return false;
  }

  /**
   * Returns whether or not this menu item is "current"
   *
   * By passing an argument, you can set this menu item as current or not.
   *
   * @param boolean $bool Optionally specify that this menu item is current
   * @return boolean
   */
  public function isCurrent($bool = null)
  {
    if ($bool !== null)
    {
      $this->_isCurrent = $bool;
    }

    if ($this->_isCurrent === null)
    {
      $url = sfContext::getInstance()->getRequest()->getUri();
      $this->_isCurrent = ($this->getUrl(array('absolute' => true)) == $url);
    }

    return $this->_isCurrent;
  }

  /**
   * Returns whether or not this menu is an ancestor of the current menu item
   *
   * @return boolean
   */
  public function isCurrentAncestor()
  {
    foreach ($this->getChildren() as $child)
    {
      if ($child->isCurrent())
      {
        return true;
      }
    }

    return false;
  }

  /**
   * @return bool Whether or not this menu item is last in its parent
   */
  public function isLast()
  {
    return $this->getNum() == $this->getParent()->count() ? true:false;
  }

  /**
   * @return bool Whether or not this menu item is first in its parent 
   */
  public function isFirst()
  {
    return ($this->getNum() == 1);
  }

  /**
   * Implements Countable
   */
  public function count()
  {
    return count($this->_children);
  }

  /**
   * Implements IteratorAggregate
   */
  public function getIterator()
  {
    return new ArrayObject($this->_children);
  }

  /**
   * Implements ArrayAccess
   */
  public function offsetExists($name)
  {
    return isset($this->_children[$name]);
  }

  /**
   * Implements ArrayAccess
   */
  public function offsetGet($name)
  {
    return $this->getChild($name);
  }

  /**
   * Implements ArrayAccess
   */
  public function offsetSet($name, $value)
  {
    return $this->addChild($name)->setLabel($value);
  }

  /**
   * Implements ArrayAccess
   */
  public function offsetUnset($name)
  {
    unset($this->_children[$name]);
  }

  /**
   * Throws an io.menu.method_not_found event.
   * 
   * This allows anyone to hook into the event and effectively add methods
   * to this class
   */
  public function __call($method, $arguments)
  {
    $name .= 'io.menu.method_not_found';

    $event = sfProjectConfiguration::getActive()->getEventDispatcher()->notifyUntil(new sfEvent($this, $name, array('method' => $method, 'arguments' => $arguments)));
    if (!$event->isProcessed())
    {
      throw new sfException(sprintf('Call to undefined method %s::%s.', get_class($this), $method));
    }

    return $event->getReturnValue();
  }










  public function getBreadcrumbsArray($subItem = null)
  {
    $breadcrumbs = array();
    $obj = $this;

    if ($subItem)
    {
      if (!is_array($subItem))
      {
        $subItem = array((string) $subItem => null);
      }
      $subItem = array_reverse($subItem);
      foreach ($subItem as $key => $value)
      {
        if (is_numeric($key))
        {
          $key = $value;
          $value = null;
        }
        $breadcrumbs[(string) $key] = $value;
      }
    }

    do {
      $label = __($obj->getLabel());
    	$breadcrumbs[$label] = $obj->getRoute();
    } while ($obj = $obj->getParent());

    return count($breadcrumbs) > 1 ? array_reverse($breadcrumbs):array();
  }

  public function getBreadcrumbs($subItem = null)
  {
    return sfSympalMenuBreadcrumbs::generate($this->getBreadcrumbsArray($subItem));
  }

  public function getPathAsString()
  {
    $children = array();
    $obj = $this;

    do {
    	$children[] = __($obj->getLabel());
    } while ($obj = $obj->getParent());

    return implode(' > ', array_reverse($children));
  }

  /**
   * Calls a given method recursively on this menu item and all of its children
   *
   * @return ioMenuItem
   */
  public function callRecursively()
  {
    $args = func_get_args();
    $arguments = $args;
    unset($arguments[0]);

    call_user_func_array(array($this, $args[0]), $arguments);

    foreach ($this->_children as $child)
    {
      call_user_func_array(array($child, 'callRecursively'), $args);
    }

    return $this;
  }

  /**
   * Exports this menu item to an array
   *
   * @return array
   */
  public function toArray()
  {
    $array = array();
    $array['name'] = $this->getName();
    if ($this->getRoute())
    {
      $array['route'] = $this->getRoute();
    }
    if ($this->_label)
    {
      $array['label'] = $this->_label;
    }
    $array['level'] = $this->getLevel();
    $array['is_current'] = $this->isCurrent();
    $array['attributes'] = $this->getAttributes();
    foreach ($this->_children as $key => $child)
    {
      $array['children'][$key] = $child->toArray();
    }

    return $array;
  }

  /**
   * Imports a menu item array into this menu item
   *
   * @param  array $array The menu item array
   * @return ioMenuItem
   */
  public function fromArray($array)
  {
    $this->setName($array['name']);
    if (isset($array['label']))
    {
      $this->_label = $array['label'];
    }
    if (isset($array['level']))
    {
      $this->_level = $array['level'];
    }
    if (isset($array['is_current']))
    {
      $this->isCurrent($array['is_current']);
    }
    if (isset($array['attributes']))
    {
      $this->setAttributes($array['attributes']);
    }

    if (isset($array['children']))
    {
      foreach ($array['children'] as $name => $child)
      {
        $this->addChild($name)->fromArray($child);
      }
    }

    return $this;
  }
}