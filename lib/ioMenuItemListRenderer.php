<?php
/**
 * Renders ioMenu tree as unordered list
 */
class ioMenuItemListRenderer implements ioMenuItemRenderer
{
  /**
   * Renders menu tree.
   *
   * Depth values corresppond to:
   *   * 0 - no children displayed at all (would return a blank string)
   *   * 1 - directly children only
   *   * 2 - children and grandchildren
   *
   * @param ioMenuItem  $item         Menu item
   * @param integer     $depth        The depth of children to render
   *
   * @return string
   */
  public function render(ioMenuItem $item, $depth = null)
  {
    return $this->_render($item, $depth);
  }

  /**
   * Renders menu tree. Internal method.
   *
   * @param ioMenuItem  $item      Menu item
   * @param integer $depth         The depth of children to render
   * @param boolean $renderAsChild Render with attributes on the write element
   *
   * @return string
   */
  protected function _render(ioMenuItem $item, $depth = null, $renderAsChild = false)
  {
     /**
     * Return an empty string if any of the following are true:
     *   a) The menu has no children eligible to be displayed
     *   b) The depth is 0
     *   c) This menu item has been explicitly set to hide its children
     */
    if (!$item->hasChildren() || $depth === 0 || !$item->showChildren())
    {
      return;
    }

    if ($renderAsChild)
    {
      $attributes = array('class' => 'menu_level_'.$item->getLevel());
    }
    else
    {
      $attributes = $item->getAttributes();

      // give the top ul a class of "menu" of none specified
      if (!isset($attributes['class']))
      {
        $attributes['class'] = 'menu';
      }
    }

    // render children with a depth - 1
    $childDepth = ($depth === null) ? null : ($depth - 1);

    $html = $this->_format($item->getLevel(), '<ul'._tag_options($attributes).'>', 'ul');
    $html .= $this->_renderChildren($item, $childDepth);
    $html .= $this->_format($item->getLevel(), '</ul>', 'ul');

    return $html;
  }

  /**
   * Renders all of the children of item.
   *
   * This calls ->renderChild() on each menu item, which instructs each
   * menu item to render themselves as an <li> tag (with nested ul if it
   * has children).
   *
   * @param integer $depth The depth each child should render
   * @return string
   */
  protected function _renderChildren(ioMenuItem $item, $depth = null)
  {
    $html = '';
    foreach ($item->getChildren() as $child)
    {
      $html .= $this->_renderItem($child, $depth);
    }
    return $html;
  }

  /**
   * Render item with all of its children.
   *
   * This renders the li tag to fit into the parent ul as well as its
   * own nested ul tag if this menu item has children
   *
   * @param integer $depth The depth each child should render
   * @return string
   */
  protected function _renderItem(ioMenuItem $item, $depth = null)
  {
    // if we don't have access or this item is marked to not be shown
    if (!$item->shouldBeRendered())
    {
      return;
    }

    // explode the class string into an array of classes
    $class = ($item->getAttribute('class')) ? explode(' ', $item->getAttribute('class')) : array();

    if ($item->isCurrent())
    {
      $class[] = 'current';
    }
    elseif ($item->isCurrentAncestor($depth))
    {
      $class[] = 'current_ancestor';
    }

    if ($item->actsLikeFirst())
    {
      $class[] = 'first';
    }
    if ($item->actsLikeLast())
    {
      $class[] = 'last';
    }

    // retrieve the attributes and put the final class string back on it
    $attributes = $item->getAttributes();
    if (count($class) > 0)
    {
      $attributes['class'] = implode(' ', $class);
    }

    // opening li tag
    $html = $this->_format($item->getLevel(), '<li'._tag_options($attributes).'>', 'li');

    // render the text/link inside the li tag
    $html .= $this->_format($item->getLevel(), $item->getRoute() ? $item->renderLink() : $item->renderLabel(), 'link');

    // renders the embedded ul if there are visible children
    $html .= $this->_render($item, $depth, true);

    // closing li tag
    $html .= $this->_format($item->getLevel(), '</li>', 'li');

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
  protected function _format($level, $html, $type)
  {
    if (ioMenuItem::$renderCompressed)
    {
      return $html;
    }

    switch ($type)
    {
      case 'ul':
      case 'link':
        $spacing = $level * 4;
        break;

      case 'li':
        $spacing = $level * 4 - 2;
        break;
    }

    return str_repeat(' ', $spacing).$html."\n";
  }

}

?>
