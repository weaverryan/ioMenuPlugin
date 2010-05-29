<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';

$t = new lime_test(10);

$t->info('1 - Test basic getters, setters and constructor');
  $menu = new ioMenuItem('test menu', '@homepage', array('title' => 'my menu'));

  $t->is($menu->getName(), 'test menu', '->getName() returns the given name.');
  $menu->setName('new menu name');
  $t->is($menu->getName(), 'new menu name', '->setName() sets the name correctly.');

  $t->is($menu->getLabel(), 'new menu name', '->getLabel() returns the name if the label does not exist.');
  $menu->setLabel('menu label');
  $t->is($menu->getLabel(), 'menu label', 'Once set, ->getLabel() returns the actual label.');

  $t->is($menu->getRoute(), '@homepage', '->getRoute() returns the given route.');
  $menu->setRoute('http://www.sympalphp.org');
  $t->is($menu->getRoute(), 'http://www.sympalphp.org', '->setRoute() sets the route correctly.');

  $t->is($menu->getAttributes(), array('title' => 'my menu'), '->getAttributes() returns the attributes array.');
  $menu->setAttributes(array('id' => 'unit_test'));
  $t->is($menu->getAttributes(), array('id' => 'unit_test'), '->setAttributes() sets the attributes array.');

  $t->is($menu->getAttribute('id', 'default'), 'unit_test', '->getAttribute() returns an existing attribute correctly.');
  $t->is($menu->getAttribute('fake', 'default'), 'default', '->getAttribute() returns the default for a non-existent attribute.');
  $menu->setAttribute('class', 'testing classes');
  $t->is($menu->getAttribute('class'), 'testing classes', '->setAttribute() correctly sets an attribute.');

  $t->is($menu->requiresAuth(), false, 'By default ->requiresAuth() returns false.');
  $menu->requiresAuth(true);
  $t->is($menu->requiresAuth(), true, 'Calling ->requiresAuth() with an argument sets the property.');

  $t->is($menu->requiresNoAuth(), false, 'By default ->requiresNoAuth() returns false.');
  $menu->requiresNoAuth(true);
  $t->is($menu->requiresNoAuth(), true, 'Calling ->requiresNoAuth() with an argument sets the property.');

  $menu->setCredentials('c1');
  $t->is($menu->getCredentials(), array('c1'), '->setCredentials() with a string sets the one credential.');
  $menu->setCredentials(array('c1', 'c2'));
  $t->is($menu->getCredentials(), array('c1', 'c2'), '->setCredentials() with an array sets all of the given credentials.');

  $t->is($menu->showChildren(), true, '->showChildren() return true by default.');
  $menu->showChildren(false);
  $t->is($menu->showChildren(), false, '->showChildren() with an argument properly sets the property.');

  $childMenu = new ioMenuItem('child');
  $childMenu->setParent($menu);
  $t->is($childMenu->getParent(), $menu, '->setParent() sets the parent menu item.');

  $t->is(count($menu->getChildren()), 0, '->getChildren() returns no children to start.');
  $menu->setChildren(array($childMenu));
  $t->is($menu->getChildren(), array($childMenu), '->getChildren() returns the proper children array.');

  $menu->setNum(5);
  $t->is($menu->getNum(), 5, '->setNum() sets the num property.');