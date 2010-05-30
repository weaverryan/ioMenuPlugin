<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';

$t = new lime_test(3);

class ioMenuItemTest extends ioMenuItem
{
}

$t->info('1 - Basic checks on the ioMenu object');
  $menu = new ioMenu(array('class' => 'root'), 'ioMenuItemTest');
  $ch1 = $menu->addChild('ch1');

  $t->is($menu->getName(), null, 'The menu item has a null name');
  $t->is($menu->getRoute(), null, 'The menu item has a null route');
  $t->is(get_class($ch1), 'ioMenuItemTest', 'The children are created with the class passed into the constructor.');
