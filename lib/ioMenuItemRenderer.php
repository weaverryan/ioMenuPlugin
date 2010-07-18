<?php
/**
 * Interface for menu renderers.
 */
interface ioMenuItemRenderer
{
  public function render(ioMenuItem $item, $depth = null);
}

?>
