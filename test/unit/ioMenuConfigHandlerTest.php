<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';
require_once sfConfig::get('sf_lib_dir').'/test/unitHelper.php';

class testClass extends ioMenuConfigHandler {

}

// @BeforeAll
$file = dirname(__FILE__).'/../fixtures/project/apps/frontend/config/navigation.yml';
$ch = new testClass();
$testCount = 12;

$t = new lime_test($testCount, new lime_output_color());

// @Test general validation
$t->diag('testing cache file');
  $t->is($ch->execute(array()),false, 'fast quit for no config files found');
  $t->is($buffer = $ch->execute(array($file)),true, 'buffer written');
  $t->is(eval(substr($buffer,6)),null,'buffer is valid php code');
  $t->isnt($buffer,'','output generated');

// @Test single level menus
$t->diag('testing single level menu');
  $t->is(isset($singleLevel),true, 'single level menu correctly generated');
  $menu = ioMenu::createFromArray($singleLevel);
  $t->is(get_class($menu),'ioMenuItem', 'menu correctly instanciated');
  $t->is(count($menu),2, 'item count is correct');

// @Test single level menus
$t->diag('testing 1 cascade menu');
  $t->is(isset($multiLevel),true, 'multi level menu correctly generated');
  $menu = ioMenu::createFromArray($multiLevel);
  $t->is(get_class($menu),'ioMenuItem', 'menu correctly instanciated');
  $t->is(count($menu),1, 'item count is correct');
  $t->is(count($menu->getChild('level_1_1',false)),2,'childnodes added correctly');
  $t->is(count($menu->getChild('level_1_1',false)->getChild('level_2_1',false)),3,'childnodes deeper inside added correctly');
