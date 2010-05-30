<?php

require_once dirname(__FILE__).'/../../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';

$t = new lime_test(5);

$t->info('1 - Test the special checkUserAccess() functionality.');
  $user = new sfBasicSecurityUser($configuration->getEventDispatcher(), new sfNoStorage());
  $menu = new ioMenuAdminItem('root');
  $ch1 = $menu->addChild('ch1');
  $gc1 = $ch1->addChild('gc1');

  $t->info('  1.1 - Check basic user access for both require and not require auth.');
  $t->is($menu->checkUserAccess($user), true, '->checkUserAccess() returns true under a very basic condition.');
  $menu->requiresAuth(true);
  $t->is($menu->checkUserAccess($user), false, '->checkUserAccess() returns false under a very basic condition.');
  $menu->requiresAuth(false);

  $t->info('  1.2 - Play with the credentials of the children, see how they affect the parent.');
  $ch1->requiresAuth(true);
  $t->is($menu->checkUserAccess($user), false, '->checkUserAccess() on the root returns false because its child requires auth.');

  $ch1->requiresAuth(false);
  $gc1->requiresAuth(true);
  $t->is($menu->checkUserAccess($user), false, '->checkUserAccess() on the root returns false because its grandchild requires auth.');
  $t->is($ch1->checkUserAccess($user), false, '->checkUserAccess() on the child returns false because its child requires auth.');