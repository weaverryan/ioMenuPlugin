<?php

/**
 * Menu items are shown if they, or one of their children, should be shown.
 *
 * This is good for an admin menu where a parent node should be shown only
 * if at least on of its children is visible and hidden otherwise.
 *
 * * root
 *    * child1
 *    * child2
 *
 * If child1 and child2 aren't visible, root will be hidden entirely. This
 * is different from a normal menu where child1 and child2 would be hidden,
 * but root would still print out its <li> and link. 
 *
 * @package     ioMenuPlugin
 * @subpackage  menu
 * @author      Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioMenuAdminItem extends ioMenuItem
{
  /**
   * Overridden to be displayed if at least one of the children menus
   * should be displayed
   *
   * @see ioMenuItem
   */
  public function checkUserAccess(sfBasicSecurityUser $user = null)
  {
    $normalAccess = parent::checkUserAccess($user);

    // if we have no children, then just behave normally. This behavior
    // is intended to hide parents who have no children.
    if (count($this->getChildren()) == 0)
    {
      return $normalAccess;
    }

    // if this item is normally accessible, then it still should be
    if (!$normalAccess)
    {
      return false;
    }

    // if any of the children are accessible, then this should be also
    foreach ($this->getChildren() as $child)
    {
      if ($child->checkUserAccess($user))
      {
        return true;
      }
    }

    return false;
  }
}