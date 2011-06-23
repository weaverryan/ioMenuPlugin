<?php

/**
 * This is your base menu item. It roughly represents a single <li> tag
 * and is what you should interact with most of the time by default.
 * 
 * Originally taken from sympal (http://www.sympalphp.org)
 * 
 * @package     ioMenuPlugin
 * @subpackage  menu
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Ryan Weaver <ryan@thatsquality.com>
 * @version     svn:$Id$ $Author$
 */
class ioMenuItem implements ArrayAccess, Countable, IteratorAggregate
{
  /**
   * Whether or not to render menus with pretty spacing, or fully compressed.
   */
  public static $renderCompressed = false;

  /**
   * Properties on this menu item
   */
  protected
    $_name             = null,    // the name of this menu item (used for id by parent menu)
    $_label            = null,    // the label to output, name is used by default
    $_route            = null,    // the route or url to use in the anchor tag
    $_attributes       = array(), // an array of attributes for the li
    $_requiresAuth     = null,    // boolean to require auth to show this menu
    $_requiresNoAuth   = null,    // boolean to require NO auth to show this menu
    $_credentials      = array(); // array of credentials needed to display this menu

  /**
   * Special i18n properties
   */
  protected
    $_i18nLabels       = array(), // an array of labels for different cultures
    $_culture          = null;    // the culture to use when rendering this menu

  /**
   * Options related to rendering
   */
  protected
    $_show             = true,    // boolean to render this menu
    $_showChildren     = true,    // boolean to render the children of this menu
    $_urlOptions       = array(), // the options array passed to url_for()
    $_linkOptions      = array(); // the options array passed to link_to()

  /**
   * Metadata on this menu item
   */
  protected
    $_children         = array(), // an array of ioMenuItem children
    $_num              = null,    // the order number this menu is in its parent
    $_parent           = null,    // parent ioMenuItem
    $_isCurrent        = null,    // whether or not this menu item is current
    $_userAccess       = null,    // whether or not the current user can access this item
    $_currentUri       = null;    // the current uri to use for selecting current menu

  /**
   * Class constructor
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
   * @param array $options Options to pass to the url_for method
   */
  public function getUri(array $options = array())
  {
    if (!$this->getRoute() || $this->getRoute() == '#')
    {
      return null;
    }

    // setup the options array and single out the absolute boolean
    $options = array_merge($this->getUrlOptions(), $options);
    if (isset($options['absolute']))
    {
      $absolute = $options['absolute'];
      unset($options['absolute']);
    }
    else
    {
      $absolute = false;
    }

    try
    {
      // Handling of the url options varies depending on the url format
      if ($this->_isOldRouteMethod())
      {
        // old-school url_for('@route_name', $absolute);
        return url_for($this->getRoute(), $absolute);
      }
      else
      {
        // new-school url_for('route_name', $options, $absolute)
        return url_for($this->getRoute(), $options, $absolute);
      }
    }
    catch (sfConfigurationException $e)
    {
      throw new sfConfigurationException(
        sprintf('Problem with menu item "%s": %s', $this->getLabel(), $e->getMessage())
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
    if ($this->_name == $name)
    {
      return $this;
    }

    if ($this->getParent() && $this->getParent()->getChild($name, false))
    {
      throw new sfException('Cannot rename item, name is already used by sibling.');
    }

    $oldName = $this->_name;
    $this->_name = $name;

    if ($this->getParent())
    {
      $this->getParent()->updateChildId($this, $oldName);
    }

    return $this;
  }

  /**
   * Updates id for child based on new name.
   *
   * Used internally after renaming item which has parent.
   *
   * @param ioMenuItem $child Item whose name has been changed.
   * @param string $oldName Old (previous) name of item.
   *
   */
  protected function updateChildId(ioMenuItem $child, $oldName)
  {
    $names = array_keys($this->getChildren());
    $items = array_values($this->getChildren());

    $offset = array_search($oldName, $names);
    $names[$offset] = $child->getName();

    $children = array_combine($names, $items);
    $this->setChildren($children);
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
  public function getLabel($culture = null)
  {
    /*
     * only try to retrieve via i18n if both:
     *  a) we're using i18n on this menu and
     *  b) we're either passed a culture or can retrieve it from the context
     */
    if ($this->useI18n() && ($culture !== null || $this->getCulture()))
    {
      $culture = ($culture === null) ? $this->getCulture() : $culture;

      // try to return that exact culture
      if (isset($this->_i18nLabels[$culture]))
      {
        return $this->_i18nLabels[$culture];
      }

      // try to return the default culture
      $defaultCulture = sfConfig::get('sf_default_culture');
      if (isset($this->_i18nLabels[$defaultCulture]))
      {
        return $this->_i18nLabels[$defaultCulture];
      }
    }

    // if i18n isn't used or if no i18n label was found, use the default method
    return ($this->_label !== null) ? $this->_label : $this->_name;
  }

  /**
   * @param  string $label    The text to use when rendering this menu item
   * @param  string $culture  The i18n culture to set this label for 
   * @return ioMenuItem
   */
  public function setLabel($label, $culture = null)
  {
    if ($culture === null)
    {
      $this->_label = $label;
    }
    else
    {
      $this->_i18nLabels[$culture] = $label;
    }

    return $this;
  }

  /**
   * Whether or not this menu item is using i18n
   *
   * @return bool
   */
  public function useI18n()
  {
    return (count($this->_i18nLabels) > 0);
  }

  /**
   * Returns the culture with which this menu item should render.
   *
   * If the culture has not been set, it asks its parent menu item for
   * a culture. If this is the root, it will attempt to ask sfContext
   * for a culture. If all else fails, the default culture is returned.
   *
   * @return string
   */
  public function getCulture()
  {
    // if the culture is set, simply return it
    if ($this->_culture !== null)
    {
      return $this->_culture;
    }

    // if we have a parent, just as the parent
    if ($this->getParent())
    {
      return $this->getParent()->getCulture();
    }
    
    // if we're the root, get from the context or return the default
    if (sfContext::hasInstance())
    {
      return sfContext::getInstance()->getUser()->getCulture();
    }
    else
    {
      return sfConfig::get('sf_default_culture');
    }
  }

  /**
   * Set the culture that should be used when rendering the menu
   *
   * @param  string $culture The culture to use when rendering the menu
   * @return void
   */
  public function setCulture($culture)
  {
    $this->_culture = $culture;
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
  public function setCredentials(array $credentials)
  {
    $this->_credentials = $credentials;

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
   * Whether or not to show this menu item. Leave parameter blank to
   * simply return the value.
   *
   * @param  boolean $bool If specified, set show to this value
   * @return bool
   */
  public function show($bool = null)
  {
    if ($bool !== null)
    {
      $this->_show = (bool) $bool;
    }

    return $this->_show;
  }

  /**
   * Whether or not this menu item should be rendered or not based on
   * all the available factors
   *
   * @param sfBasicSecurityUser $user The optional user to check against
   * @return boolean
   */
  public function shouldBeRendered(sfBasicSecurityUser $user = null)
  {
    return $this->show() && $this->checkUserAccess($user);
  }

  /**
   * Add a child menu item to this menu
   *
   * @param mixed   $child    An ioMenuItem object or the name of a new menu to create
   * @param string  $route    If creating a new menu, the route for that menu
   * @param string  $attributes  If creating a new menu, the attributes for that menu
   * @param string  $class    The class for menu item, if it needs to be created
   *
   * @return ioMenuItem The child menu item
   */
  public function addChild($child, $route = null, $attributes = array(), $class = null)
  {
    if (!$child instanceof ioMenuItem)
    {
      $child = $this->_createChild($child, $route, $attributes, $class);
    }
    elseif ($child->getParent())
    {
      throw new sfException('Cannot add menu item as child, it already belongs to another menu (e.g. has a parent).');
    }

    $child->setParent($this);
    $child->showChildren($this->showChildren());
    $child->setCurrentUri($this->getCurrentUri());
    $child->setNum($this->count());

    $this->_children[$child->getName()] = $child;

    return $child;
  }

  /**
   * Returns the child menu identified by the given name
   *
   * If the child menu doesn't exist and $create is true, it will
   * automatically be created
   *
   * @param  string $name  Then name of the child menu to return
   * @param  boolean $create Whether or not to create the child if it does not exist
   * @return ioMenuItem|null
   */
  public function getChild($name, $create = true)
  {
    if (!isset($this->_children[$name]) && $create)
    {
      $this->addChild($name);
    }

    return isset($this->_children[$name]) ? $this->_children[$name] : null;
  }

  /**
   * Moves child to specified position. Rearange other children accordingly.
   *
   * @param numeric $position Position to move child to.
   *
   */
  public function moveToPosition($position)
  {
    $this->getParent()->moveChildToPosition($this, $position);
  }

  /**
   * Moves child to specified position. Rearange other children accordingly.
   *
   * @param ioMenuItem $child Child to move.
   * @param numeric $position Position to move child to.
   */
  public function moveChildToPosition(ioMenuItem $child, $position)
  {
    $name = $child->getName();
    $order = array_keys($this->_children);

    $oldPosition = array_search($name, $order);
    unset($order[$oldPosition]);

    $order = array_values($order);

    array_splice($order, $position, 0, $name);
    $this->reorderChildren($order);
  }

  /**
   * Moves child to first position. Rearange other children accordingly.
   */
  public function moveToFirstPosition()
  {
    $this->moveToPosition(0);
  }

  /**
   * Moves child to last position. Rearange other children accordingly.
   */
  public function moveToLastPosition()
  {
    $this->moveToPosition($this->getParent()->count());
  }

  /**
   * Reorder children.
   *
   * @param array $order New order of children.
   */
  public function reorderChildren($order)
  {
    if (count($order) != $this->count())
    {
      throw new sfException('Cannot reorder children, order does not contain all children.');
    }

    $newChildren = array();

    foreach($order as $name)
    {
      if (!isset($this->_children[$name]))
      {
        throw new sfException('Cannot find children named '.$name);
      }

      $child = $this->_children[$name];
      $newChildren[$name] = $child;
    }

    $this->_children = $newChildren;
    $this->_resetChildrenNum();
  }

  /**
   * Makes a deep copy of menu tree. Every item is copied as another object.
   *
   * @return ioMenuItem
   *
   */
  public function copy()
  {
    $newMenu = clone $this;
    $newMenu->_children = array();
    $newMenu->setParent(null);
    foreach($this->getChildren() as $child)
    {
      $newMenu->addChild($child->copy());
    }

    return $newMenu;
  }

  /**
   * Get slice of menu as another menu.
   *
   * If offset and/or length are numeric, it works like in array_slice function:
   *
   *   If offset is non-negative, slice will start at the offset.
   *   If offset is negative, slice will start that far from the end.
   *
   *   If length is zero, slice will have all elements.
   *   If length is positive, slice will have that many elements.
   *   If length is negative, slice will stop that far from the end.
   *
   * It's possible to mix names/object/numeric, for example:
   *   slice("child1", 2);
   *   slice(3, $child5);
   *
   * @param mixed $offset Name of child, child object, or numeric offset.
   * @param mixed $length Name of child, child object, or numeric length.
   * @return ioMenuItem Slice of menu.
   *
   */
  public function slice($offset, $length = 0)
  {
    $count = $this->count();
    
    $names = array_keys($this->getChildren());
    if (is_numeric($offset))
    {
      $offset = ($offset >= 0) ? $offset : $count + $offset;
      $from = (isset($names[$offset])) ? $names[$offset] : "";
    }
    else
    {
      $child = ($offset instanceof ioMenuItem) ? $offset : $this->getChild($offset, false);
      $offset = ($child) ? $child->getNum() : 0;
      $from = ($child) ? $child->getName() : "";
    }

    if (is_numeric($length))
    {
      if ($length == 0)
      {
        $offset2 = $count - 1;
      }
      else
      {
        $offset2 = ($length > 0) ? $offset + $length - 1 : $count - 1 + $length;
      }
      $to = (isset($names[$offset2])) ? $names[$offset2] : "";
    }
    else
    {
      $to = ($length instanceof ioMenuItem) ? $length->getName() : $length;
    }

    return $this->_sliceFromTo($from, $to);
  }

  /**
   * Get slice of menu as another menu.
   *
   * Internal method.
   *
   * @param string $offset Name of child.
   * @param string $length Name of child.
   * @return ioMenuItem
   *
   */
  private function _sliceFromTo($from, $to)
  {
    $newMenu = $this->copy();
    $newChildren = array();

    $copy = false;
    foreach($newMenu->getChildren() as $child)
    {
      if ($child->getName() == $from)
      {
        $copy = true;
      }

      if ($copy == true)
      {
        $newChildren[$child->getName()] = $child;
      }

      if ($child->getName() == $to)
      {
        break;
      }
    }

    $newMenu->setChildren($newChildren);
    $newMenu->_resetChildrenNum();

    return $newMenu;
  }

  /**
   * Split menu into two distinct menus.
   * 
   * @param mixed $length Name of child, child object, or numeric length.
   * @return array Array with two menus, with "primary" and "secondary" key
   */
  public function split($length)
  {
    $count = $this->count();

    if (!is_numeric ($length))
    {
      if (!($length instanceof ioMenuItem))
      {
        $length = $this->getChild($length, false);
      }

      $length = ($length != null) ? $length->getNum() + 1 : $count;
    }

    $ret = array();
    $ret['primary'] = $this->slice(0, $length);
    $ret['secondary'] = $this->slice($length);

    return $ret;
  }

  /**
   * Returns whether or not the given/current user has permission to
   * view this current menu item.
   *
   * This saves the result as a property on this class for the current
   * user so that this method isn't checked redundantly. If an argument
   * is passed in, the property is ignored.
   *
   * @param sfUser $user
   * @return bool
   */
  public function checkUserAccess(sfBasicSecurityUser $user = null)
  {
    // if we're not checking a special user and _userAccess is already known, just return it
    if ($user === null && $this->_userAccess !== null)
    {
      return $this->_userAccess;
    }

    // cache the end value unless a custom user object has been passed
    $userPropertyCache = ($user === null);
    if ($user === null)
    {
      // if we're not passed a user and we have no context, bail
      if (!sfContext::hasInstance())
      {
        return true;
      }

      $user = sfContext::getInstance()->getUser();
    }

    // determine the user access
    if ($user->isAuthenticated() && $this->requiresNoAuth())
    {
      $userAccess = false;
    }
    elseif (!$user->isAuthenticated() && $this->requiresAuth())
    {
      $userAccess = false;
    }
    else
    {
      $userAccess = $user->hasCredential($this->getCredentials());
    }

    // if we should cache this value on the property, do it now
    if ($userPropertyCache)
    {
      $this->_userAccess = $userAccess;
    }

    return $userAccess;
  }

  /**
   * Returns the level of this menu item
   *
   * The root menu item is 0, followed by 1, 2, etc
   *
   * @return integer
   */
  public function getLevel()
  {
    $count = -1;
    $obj = $this;

    do {
      $count++;
    } while ($obj = $obj->getParent());

    return $count;
  }

  /**
   * Returns the root ioMenuItem of this menu tree
   *
   * @return ioMenuItem
   */
  public function getRoot()
  {
    $obj = $this;
    do {
        $found = $obj;
    } while ($obj = $obj->getParent());

    return $found;
  }

  /**
   * Returns whether or not this menu item is the root menu item
   *
   * @return bool
   */
  public function isRoot()
  {
    return (bool) !$this->getParent();
  }

  /**
   * @return ioMenuItem|null
   */
  public function getParent()
  {
    return $this->_parent;
  }

  /**
   * Used internally when adding and removing children
   *
   * @param ioMenuItem $parent
   * @return ioMenuItem
   */
  public function setParent(ioMenuItem $parent = null)
  {
    return $this->_parent = $parent;
  }

  /**
   * @return array of ioMenuItem objects
   */
  public function getChildren()
  {
    return $this->_children;
  }

  /**
   * @param  array $children An array of ioMenuItem objects
   * @return ioMenuItem
   */
  public function setChildren(array $children)
  {
    $this->_children = $children;

    return $this;
  }

  /**
   * Sets the array of options to use when running url_for()
   *
   * @param  array $options The array of options to set
   * @return void
   */
  public function setUrlOptions(array $options)
  {
    $this->_urlOptions = $options;
  }

  /**
   * @return array
   */
  public function getUrlOptions()
  {
    return $this->_urlOptions;
  }

  /**
   * @return array
   */
  public function getLinkOptions()
  {
    return $this->_linkOptions;
  }

  /**
   * The options that will be used in the link_to() function for this menu item.
   *
   * @param  $linkOptions The options to set
   * @return void
   */
  public function setLinkOptions($linkOptions)
  {
    $this->_linkOptions = $linkOptions;
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
   * Reset children nums.
   *
   * Primarily called after changes to children (removing, reordering, etc)
   * 
   * @return void
   */
  protected function _resetChildrenNum()
  {
    $i = 0;
    foreach ($this->_children as $child)
    {
      $child->setNum($i++);
    }
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
  protected function _createChild($name, $route = null, $attributes = array(), $class = null)
  {
    if ($class === null)
    {
      $class = get_class($this);
    }

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
      // unset the child and reset it so it looks independent
      $this->_children[$name]->setParent(null);
      $this->_children[$name]->setNum(null);
      unset($this->_children[$name]);

      $this->_resetChildrenNum();
    }
  }

  /**
   * @return ioMenuItem
   */
  public function getFirstChild()
  {
    return reset($this->_children);
  }

  /**
   * @return ioMenuItem
   */
  public function getLastChild()
  {
    return end($this->_children);
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
      if ($child->shouldBeRendered())
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Renders a ul tag and any children inside li tags.
   *
   * Depth values corresppond to:
   *   * 0 - no children displayed at all (would return a blank string)
   *   * 1 - directly children only
   *   * 2 - children and grandchildren
   *
   * @param integer $depth         The depth of children to render 
   * @param boolean $renderAsChild Used internally to render with attributes on the write element
   * 
   * @return string
   */
  public function render($depth = null, $renderAsChild = false)
  {
    /**
     * Return an empty string if any of the following are true:
     *   a) The menu has no children eligible to be displayed
     *   b) The depth is 0
     *   c) This menu item has been explicitly set to hide its children
     */
    if (!$this->hasChildren() || $depth === 0 || !$this->showChildren())
    {
      return;
    }

    if ($renderAsChild)
    {
      $attributes = array('class' => 'menu_level_'.$this->getLevel());
    }
    else
    {
      $attributes = $this->getAttributes();

      // give the top ul a class of "menu" of none specified
      if (!isset($attributes['class']))
      {
        $attributes['class'] = 'menu';
      }
    }

    // render children with a depth - 1
    $childDepth = ($depth === null) ? null : ($depth - 1);

    $html = $this->_format('<ul'._tag_options($attributes).'>', 'ul');
    $html .= $this->renderChildren($childDepth);
    $html .= $this->_format('</ul>', 'ul');

    return $html;
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string) $this->render();
  }

  /**
   * Renders all of the children of this menu.
   *
   * This calls ->renderChild() on each menu item, which instructs each
   * menu item to render themselves as an <li> tag (with nested ul if it
   * has children).
   *
   * @param integer $depth The depth each child should render
   * @return string
   */
  public function renderChildren($depth = null)
  {  
    $html = '';
    foreach ($this->_children as $child)
    {
      $html .= $child->renderChild($depth);
    }
    return $html;
  }

  /**
   * Called by the parent menu item to render this menu.
   *
   * This renders the li tag to fit into the parent ul as well as its
   * own nested ul tag if this menu item has children
   *
   * @param integer $depth The depth each child should render
   * @return string
   */
  public function renderChild($depth = null)
  {
    // if we don't have access or this item is marked to not be shown
    if (!$this->shouldBeRendered())
    {
      return; 
    }

    // explode the class string into an array of classes
    $class = ($this->getAttribute('class')) ? explode(' ', $this->getAttribute('class')) : array();

    if ($this->isCurrent())
    {
      $class[] = 'current';
    }
    elseif ($this->isCurrentAncestor($depth))
    {
      $class[] = 'current_ancestor';
    }

    if ($this->actsLikeFirst())
    {
      $class[] = 'first';
    }
    if ($this->actsLikeLast())
    {
      $class[] = 'last';
    }

    // retrieve the attributes and put the final class string back on it
    $attributes = $this->getAttributes();
    if (count($class) > 0)
    {
      $attributes['class'] = implode(' ', $class);
    }

    // opening li tag
    $html = $this->_format('<li'._tag_options($attributes).'>', 'li');

    // render the text/link inside the li tag
    $html .= $this->_format((null !== $this->_route) ? $this->renderLink() : $this->renderLabel(), 'link');

    // renders the embedded ul if there are visible children
    $html .= $this->render($depth, true);

    // closing li tag
    $html .= $this->_format('</li>', 'li');

    return $html;
  }

  /**
   * If self::$renderCompressed is on, this will apply the necessary
   * spacing and line-breaking so that the particular thing being rendered
   * makes up its part in a fully-rendered and spaced menu.
   *
   * @param  string $html The html to render in an (un)formatted way
   * @param  string $type The type [ul,link,li] of thing being rendered 
   * @return string
   */
  protected function _format($html, $type)
  {
    if (self::$renderCompressed)
    {
      return $html;
    }

    switch ($type)
    {
      case 'ul':
      case 'link':
        $spacing = $this->getLevel() * 4;
        break;

      case 'li':
        $spacing = $this->getLevel() * 4 - 2;
        break;
    }

    return str_repeat(' ', $spacing).$html."\n";
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
    if (null === ($route = $this->getRoute()))
    {
      return $this->renderLabel();
    }

    // allow for "fake" hrefs
    if ($route == '' || $route == '#')
    {
      $params = array_merge($this->getUrlOptions(), $this->getLinkOptions());
      if ($route)
      {
        $params['href'] = $route;
      }

      return content_tag('a', $this->renderLabel(), $params);
    }

    // Handling of the url options and link options varies depending on the url format
    if ($this->_isOldRouteMethod())
    {
      // old-school link_to('link text', '@route_name', $options);
      return link_to($this->renderLabel(), $this->getRoute(), array_merge($this->getUrlOptions(), $this->getLinkOptions()));
    }
    else
    {
      // new-school link_to('link text', 'route_name', $params, $options)
      $params = $this->getUrlOptions();
      $options = $this->getLinkOptions();
      if (isset($params['absolute']))
      {
        $options['absolute'] = $params['absolute'];
        unset($params['absolute']);
      }

      return link_to($this->renderLabel(), $this->getRoute(), $params, $options);
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
   * A string representation of this menu item
   *
   * e.g. Top Level > Second Level > This menu
   *
   * @param string $separator
   * @return string
   */
  public function getPathAsString($separator = ' > ')
  {
    $children = array();
    $obj = $this;

    do {
    	$children[] = $obj->renderLabel();
    } while ($obj = $obj->getParent());

    return implode($separator, array_reverse($children));
  }

  /**
   * Renders an array of label => uri pairs ready to be used for breadcrumbs.
   *
   * The subItem can be one of the following forms
   *   * 'subItem'
   *   * array('subItem' => '@homepage')
   *   * array('subItem1', 'subItem2')
   *
   * @example
   * // drill down to the Documentation menu item, then add "Chapter 1" to the breadcrumb
   * $arr = $menu['Documentation']->getBreadcrumbsArray('Chapter 1');
   * foreach ($arr as $name => $url)
   * {
   *
   * }
   *
   * @param  mixed $subItem A string or array to append onto the end of the array
   * @return array
   */
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
      $label = $obj->renderLabel();
    	$breadcrumbs[$label] = $obj->getUri();
    } while ($obj = $obj->getParent());

    return array_reverse($breadcrumbs);
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
      $url = $this->getCurrentUri();
      $menuUrl = $this->getUri(array('absolute' => true));

      // a very dirty hack so homepages will match with or without the trailing slash
      if ($this->getRoute() == '@homepage' || $this->getRoute() == 'homepage')
      {
        if (substr($url, -1) != '/')
        {
          $menuUrl = substr($menuUrl, 0, strlen($menuUrl) - 1);
        }
      }

      $this->_isCurrent = ($menuUrl == $url);
    }

    return $this->_isCurrent;
  }

  /**
   * Returns whether or not this menu is an ancestor of the current menu item
   *
   * @return boolean
   */
  public function isCurrentAncestor($depth = null)
  {
    // if children not shown, then we're definitely not a visible ancestor
    if (!$this->showChildren() || $depth === 0)
    {
      return false;
    }

    foreach ($this->getChildren() as $child)
    {
      if ($child->isCurrent() || $child->isCurrentAncestor($depth - 1))
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
    // if this is root, then return false
    if ($this->isRoot())
    {
      return false;
    }

    return $this->getNum() == $this->getParent()->count() - 1 ? true : false;
  }

  /**
   * @return bool Whether or not this menu item is first in its parent 
   */
  public function isFirst()
  {
    // if this is root, then return false
    if ($this->isRoot())
    {
      return false;
    }

    return ($this->getNum() == 0);
  }

  /**
   * Whereas isFirst() returns if this is the first child of the parent
   * menu item, this function takes into consideration user credentials.
   *
   * This returns true if this is the first child that would be rendered
   * for the current user 
   *
   * @return boolean
   */
  public function actsLikeFirst()
  {
    // root items are never "marked" as first 
    if ($this->isRoot())
    {
      return false;
    }

    // if we're first and visible, we're first, period.
    if ($this->shouldBeRendered() && $this->isFirst())
    {
      return true;
    }

    $children = $this->getParent()->getChildren();
    foreach ($children as $child)
    {
      // loop until we find a visible menu. If its this menu, we're first
      if ($child->shouldBeRendered())
      {
        return $child->getName() == $this->getName();
      }
    }

    return false;
  }

  /**
   * Whereas isLast() returns if this is the last child of the parent
   * menu item, this function takes into consideration user credentials.
   *
   * This returns true if this is the last child that would be rendered
   * for the current user
   *
   * @return boolean
   */
  public function actsLikeLast()
  {
    // root items are never "marked" as last
    if ($this->isRoot())
    {
      return false;
    }

    // if we're last and visible, we're last, period.
    if ($this->shouldBeRendered() && $this->isLast())
    {
      return true;
    }

    $children = array_reverse($this->getParent()->getChildren());
    foreach ($children as $child)
    {
      // loop until we find a visible menu. If its this menu, we're first
      if ($child->shouldBeRendered())
      {
        return $child->getName() == $this->getName();
      }
    }

    return false;
  }

  /**
   * Returns the current uri, which is used for determining the current
   * menu item.
   *
   * If the uri isn't set, this asks the parent menu for its current uri.
   * This would recurse up the tree until the root is hit. Once the root
   * is hit, if it still doesn't know the currentUri, it gets it from the
   * request object.
   *
   * @return string
   */
  public function getCurrentUri()
  {
    if ($this->_currentUri === null)
    {
      if ($this->getParent() && ($currentUri = $this->getParent()->getCurrentUri()))
      {
        /**
         * This should look strange. But, if we ask our parent for the
         * current uri, and it returns it successfully, then one of two
         * different things just happened:
         * 
         *   1) The parent already had the currentUri calculated, but it
         *      hadn't been passed down to the child yet. This technically
         *      should not happen, but we allow for the possibility. In
         *      that case, _currentUri is still blank and we set it here.
         *   2) The parent did not have the currentUri calculated, and upon
         *      calculating it, it set it on itself and all of its children.
         *      In that case, this menu item and all of its children will
         *      now have the currentUri just by asking the parent.
         */
        if ($this->_currentUri === null)
        {
          $this->setCurrentUri($currentUri);
        }
      }
      else
      {
        $uri = explode('?', sfContext::getInstance()->getRequest()->getUri());
        $this->setCurrentUri($uri[0]);
      }
    }

    return $this->_currentUri;
  }

  /**
   * Sets the current uri, used when determining the current menu item
   *
   * This will set the current uri on the root menu item, which all other
   * menu items will use
   *
   * @return void
   */
  public function setCurrentUri($uri)
  {
    $this->_currentUri = $uri;

    foreach ($this->getChildren() as $child)
    {
      $child->setCurrentUri($uri);
    }
  }

  /**
   * Calls a method recursively on all of the children of this item
   *
   * @example
   * $menu->callRecursively('showChildren', false);
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
   * @param boolean $withChildren Whether to
   * @return array
   */
  public function toArray($withChildren = true)
  {
    $fields = array(
      '_name'           => 'name',
      '_label'          => 'label',
      '_route'          => 'route',
      '_attributes'     => 'attributes',
      '_requiresAuth'   => 'requires_auth',
      '_requiresNoAuth' => 'requires_no_auth',
      '_credentials'    => 'credentials',
      '_linkOptions'    => 'link_options',
      '_urlOptions'     => 'url_options',
    );

    // output the i18n labels if any are set
    if ($this->useI18n())
    {
      $fields['_i18nLabels'] = 'i18n_labels';
    }

    $array = array();

    foreach ($fields as $propName => $field)
    {
      $array[$field] = $this->$propName;
    }

    // record this class name so this item can be recreated with the same class
    $array['class'] = get_class($this);

    // export the children as well, unless explicitly disabled
    if ($withChildren)
    {
      $array['children'] = array();
      foreach ($this->_children as $key => $child)
      {
        $array['children'][$key] = $child->toArray();
      }
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
    if (isset($array['name']))
    {
      $this->setName($array['name']);
    }

    if (isset($array['label']))
    {
      $this->_label = $array['label'];
    }

    if (isset($array['i18n_labels']))
    {
      $this->_i18nLabels = $array['i18n_labels'];
    }

    if (isset($array['route']))
    {
      $this->setRoute($array['route']);
    }

    if (isset($array['attributes']))
    {
      $this->setAttributes($array['attributes']);
    }

    if (isset($array['requires_auth']))
    {
      $this->requiresAuth($array['requires_auth']);
    }
    
    if (isset($array['requires_no_auth']))
    {
      $this->requiresNoAuth($array['requires_no_auth']);
    }

    if (isset($array['credentials']))
    {
      $this->setCredentials($array['credentials']);
    }

		if (isset($array['link_options']))
		{
			$this->setLinkOptions($array['link_options']);
		}

		if (isset($array['url_options']))
		{
			$this->setUrlOptions($array['url_options']);
		}

    if (isset($array['children']))
    {
      foreach ($array['children'] as $name => $child)
      {
        $class = isset($child['class']) ? $child['class'] : get_class($this);
        // create the child with the correct class
        $this->addChild($name, null, array(), $class)->fromArray($child);
      }
    }

    return $this;
  }

  /**
   * Creates a new menu item (and tree if $data['children'] is set).
   *
   * The source is an array of data that should match the output from ->toArray().
   *
   * @param  array $data The array of data to use as a source for the menu tree 
   * @return ioMenuItem
   */
  public static function createFromArray(array $data)
  {
    $class = isset($data['class']) ? $data['class'] : 'ioMenuItem';

    $name = isset($data['name']) ? $data['name'] : null;
    $menu = new $class($name);
    $menu->fromArray($data);

    return $menu;
  }

  /**
   * Returns whether or not the route method used is in the old format
   * or the new format.
   *
   * This affects how we generate urls and links
   *
   * @return bool
   */
  protected function _isOldRouteMethod()
  {
    return ('@' == substr($this->getRoute(), 0, 1) || false !== strpos($this->getRoute(), '/'));
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
    return $this->getChild($name, false);
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
    $this->removeChild($name);
  }

  /**
   * Throws an io.menu.method_not_found event.
   * 
   * This allows anyone to hook into the event and effectively add methods
   * to this class
   */
  public function __call($method, $arguments)
  {
    $name = 'io.menu.method_not_found';

    $event = sfProjectConfiguration::getActive()->getEventDispatcher()->notifyUntil(new sfEvent($this, $name, array('method' => $method, 'arguments' => $arguments)));
    if (!$event->isProcessed())
    {
      throw new sfException(sprintf('Call to undefined method %s::%s.', get_class($this), $method));
    }

    return $event->getReturnValue();
  }
}
