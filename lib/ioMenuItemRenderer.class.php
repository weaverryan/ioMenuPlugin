<?php
/**
 * 
 */

/**
 * Interface for menu renderers.
 * 
 * @package     ioMenuPluginer
 * @subpackage  renderer
 * @author      g21michal
 * @author      Ryan Weaver <ryan.weaver@iostudio.com>
 * @copyright   Iostudio, LLC 2010
 * @since       2010-07-21
 * @version     svn:$Id$ $Author$
 */
interface ioMenuItemRenderer
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
  public function render(ioMenuItem $item, $depth = null);
}
