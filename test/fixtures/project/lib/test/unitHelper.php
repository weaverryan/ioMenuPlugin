<?php
/**
 * Utility class for helper unit test functions
 */

// prints a visual representation of our basic testing tree
function print_test_tree(lime_test $t)
{
  $t->info('      Menu Structure   ');
  $t->info('               rt      ');
  $t->info('             /    \    ');
  $t->info('          pt1      pt2 ');
  $t->info('        /  | \      |  ');
  $t->info('      ch1 ch2 ch3  ch4 ');
  $t->info('                    |  ');
  $t->info('                   gc1 ');
}

// runs basic checks on the test tree to make sure it has its integrity
function check_test_tree(lime_test $t, ioMenuItem $menu)
{
  $t->info('### Running checks on the integrity of the test tree.');
  $t->is(count($menu), 2, 'count(rt) returns 2 children');
  $t->is(count($menu['Parent 1']), 3, 'count(pt1) returns 3 children');
  $t->is(count($menu['Parent 2']), 1, 'count(pt2) returns 1 child');
  $t->is(count($menu['Parent 2']['Child 4']), 1, 'count(ch4) returns 1 child');
  $t->is_deeply($menu['Parent 2']['Child 4']['Grandchild 1']->getName(), 'Grandchild 1', 'gc1 has the name "Grandchild 1"');
}