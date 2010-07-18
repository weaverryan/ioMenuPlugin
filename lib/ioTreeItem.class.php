<?php

/**
 * Implements tree item.
 *
 * Tree item is an object which has name and contains childrens.
 *
 * Originally taken from sympal (http://www.sympalphp.org)
 *
 * @package     ioMenuPlugin
 * @subpackage  menu
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Ryan Weaver <ryan@thatsquality.com>
 * @version     svn:$Id$ $Author$
 */
class ioTreeItem implements ArrayAccess, Countable, IteratorAggregate
{
  /**
   * Properties on this tree item
   */
  protected
    $_name             = null;    // the name of this tree item (used for id by parent item)

  /**
   * Metadata on this tree item
   */
  protected
    $_children         = array(), // an array of ioTreeItem children
    $_num              = null,    // the order number this item is in its parent
    $_parent           = null;    // parent ioTreeItem

  /**
   * Creates new tree item.
   *
   * @param string $name Name of this tree item
   */
  public function __construct($name)
  {
      $this->_name = $name;
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
   * @return ioTreeItem
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
   * @param ioTreeItem $child Item whose name has been changed.
   * @param string $oldName Old (previous) name of item.
   *
   */
  protected function updateChildId(ioTreeItem $child, $oldName)
  {
    $names = array_keys($this->getChildren());
    $items = array_values($this->getChildren());

    $offset = array_search($oldName, $names);
    $names[$offset] = $child->getName();

    $children = array_combine($names, $items);
    $this->setChildren($children);
  }

  /**
   * Add a child item to this tree
   *
   * @param mixed   $child    An ioTreeItem object
   */
  public function addChild(ioTreeItem $child)
  {
    if ($child->getParent())
    {
      throw new sfException('Cannot add item as child, it already belongs to another tree (e.g. has a parent).');
    }

    $child->setParent($this);
    $child->showChildren($this->showChildren());
    $child->setNum($this->count());

    $this->_children[$child->getName()] = $child;
  }

  /**
   * Returns the child item identified by the given name
   *
   * @param  string $name  Then name of the child item to return
   * @return ioTreeItem|null
   */
  public function getChild($name, $create = true)
  {
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
   * @param ioTreeItem $child Child to move.
   * @param numeric $position Position to move child to.
   */
  public function moveChildToPosition(ioTreeItem $child, $position)
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
   * Makes a deep copy of tree. Every item is copied as another object.
   *
   * @return ioTreeItem
   */
  public function copy()
  {
    $newTree = clone $this;
    $newTree->_children = array();
    $newTree->setParent(null);
    foreach($this->getChildren() as $child)
    {
      $newTree->addChild($child->copy());
    }

    return $newTree;
  }

  /**
   * Get slice of tree as another tree.
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
   * @return ioTreeItem Slice of tree.
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
      $child = ($offset instanceof ioTreeItem) ? $offset : $this->getChild($offset, false);
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
      $to = ($length instanceof ioTreeItem) ? $length->getName() : $length;
    }

    return $this->_sliceFromTo($from, $to);
  }

  /**
   * Get slice of tree as another tree.
   *
   * Internal method.
   *
   * @param string $offset Name of child.
   * @param string $length Name of child.
   * @return ioTreeItem
   *
   */
  private function _sliceFromTo($from, $to)
  {
    $newTree = $this->copy();
    $newChildren = array();

    $copy = false;
    foreach($newTree->getChildren() as $child)
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

    $newTree->setChildren($newChildren);
    $newTree->_resetChildrenNum();

    return $newTree;
  }

  /**
   * Split tree into two distinct trees.
   *
   * @param mixed $length Name of child, child object, or numeric length.
   * @return array Array with two trees, with "primary" and "secondary" key
   */
  public function split($length)
  {
    $count = $this->count();

    if (!is_numeric ($length))
    {
      if (!($length instanceof ioTreeItem))
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
   * Returns the level of this tree item
   *
   * The root tree item is 0, followed by 1, 2, etc
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
   * Returns the root ioTreeItem of this tree
   *
   * @return ioTreeItem
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
   * Returns whether or not this item is the root tree item
   *
   * @return bool
   */
  public function isRoot()
  {
    return (bool) !$this->getParent();
  }

  /**
   * @return ioTreeItem|null
   */
  public function getParent()
  {
    return $this->_parent;
  }

  /**
   * Used internally when adding and removing children
   *
   * @param ioTreeItem $parent
   * @return ioTreeItem
   */
  public function setParent(ioTreeItem $parent = null)
  {
    return $this->_parent = $parent;
  }

  /**
   * @return array of ioTreeItem objects
   */
  public function getChildren()
  {
    return $this->_children;
  }

  /**
   * @param  array $children An array of ioTreeItem objects
   * @return ioTreeItem
   */
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
   * Removes a child from this item
   *
   * @param mixed $name The name of ioTreeItem instance to remove
   */
  public function removeChild($name)
  {
    $name = ($name instanceof ioTreeItem) ? $name->getName() : $name;

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
   * @return ioTreeItem
   */
  public function getFirstChild()
  {
    return reset($this->_children);
  }

  /**
   * @return ioTreeItem
   */
  public function getLastChild()
  {
    return end($this->_children);
  }

  /**
   * @return bool Whether or not this tree item is last in its parent
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
   * @return bool Whether or not this tree item is first in its parent
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
}

?>
