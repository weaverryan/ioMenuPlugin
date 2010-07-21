<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

$t = new lime_test(6);

$timer = new sfTimer();

$t->info('1 - Test basic getters, setters and constructor');
  $menu = new ioTreeItem('test menu', '@homepage', array('title' => 'my menu'));

  $t->is($menu->getName(), 'test menu', '->getName() returns the given name.');
  $menu->setName('new menu name');
  $t->is($menu->getName(), 'new menu name', '->setName() sets the name correctly.');


  $childMenu = new ioTreeItem('child');
  $childMenu->setParent($menu);
  $t->is($childMenu->getParent(), $menu, '->setParent() sets the parent menu item.');

  $t->is(count($menu->getChildren()), 0, '->getChildren() returns no children to start.');
  $menu->setChildren(array($childMenu));
  $t->is($menu->getChildren(), array($childMenu), '->getChildren() returns the proper children array.');

  $menu->setNum(5);
  $t->is($menu->getNum(), 5, '->setNum() sets the num property.');

// used for benchmarking
$timer->addTime();
$t->info('Test completed in '.$timer->getElapsedTime());
