<?php
/**
 * Utility class for helper unit test functions
 */

// creates the test tree. Run extract() on the return to make variables available
function create_test_tree(lime_test $t, $class = 'ioMenuItem')
{
  $t->info('### Creating the test menu.');

  $menu = new $class('Root li', null, array('class' => 'root'));
  $pt1 = $menu->getChild('Parent 1');
  $ch1 = $pt1->addChild('Child 1');
  $ch2 = $pt1->addChild('Child 2');

  // add the 3rd child via addChild with an object
  $ch3 = new $class('Child 3');
  $pt1->addChild($ch3);

  $pt2 = $menu->getChild('Parent 2');
  $ch4 = $pt2->addChild('Child 4');
  $gc1 = $ch4->addChild('Grandchild 1');

  return array(
    'menu'  => $menu,
    'pt1'   => $pt1,
    'pt2'   => $pt2,
    'ch1'   => $ch1,
    'ch2'   => $ch2,
    'ch3'   => $ch3,
    'ch4'   => $ch4,
    'gc1'   => $gc1,
  );
}

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