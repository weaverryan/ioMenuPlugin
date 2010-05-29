<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';
require_once $_SERVER['SYMFONY'].'/vendor/lime/lime.php';

$t = new lime_test(142);

// stub class used for testing
class ioMenuItemTest extends ioMenuItem
{
  // resets the current property so we can test for current repeatedly.
  public function resetIsCurrent()
  {
    $this->_isCurrent = null;
  }
}

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


$t->info('### Creating the test tree.');
/*
 * Note: the root menu item should usually be an instance of ioMenu,
 *       which suppresses the excess li at the top level (so you only
 *       get the ul that wraps the children. We're avoiding that here
 *       because we're strictly testing ioMenuItem.
 */
$menu = new ioMenuItemTest('Root li', null, array('class' => 'root'));
$pt1 = $menu->getChild('Parent 1');
$ch1 = $pt1->addChild('Child 1');
$ch2 = $pt1->addChild('Child 2');
$ch3 = $pt1->addChild('Child 3');

$pt2 = $menu->getChild('Parent 2');
$ch4 = $pt2->addChild('Child 4');
$gc1 = $ch4->addChild('Grandchild 1');


$t->info('2 - Test the construction of trees');
  check_test_tree($t, $menu);
  print_test_tree($t); // print the test tree

  $t->is(get_class($pt1), 'ioMenuItemTest', 'Test that children menu items are created as same class as parent.');

  // basic hierarchy functions
  $t->info('  2.1 - Test the basics of the hierarchy.');
  $t->is($menu->getLevel(), 0, '->getLevel() on the root menu item returns 0');
  $t->is($pt1->getLevel(), 1, '->getLevel() on pt1 is 1');
  $t->is($pt2->getLevel(), 1, '->getLevel() on pt2 is 1');
  $t->is($ch4->getLevel(), 2, '->getLevel() on ch4 is 2');
  $t->is($gc1->getLevel(), 3, '->getLevel() on gc1 is 3');

  $t->is($menu->getRoot(), $menu, '->getRoot() on rt returns itself.');
  $t->is($pt1->getRoot(), $menu, '->getRoot() on pt1 returns rt.');
  $t->is($gc1->getRoot(), $menu, '->getRoot() on gc1 returns rt.');

  $t->is($menu->getParent(), null, '->getParent() on rt returns null - it has no parent.');
  $t->is($pt1->getParent(), $menu, '->getParent() on pt1 returns rt.');
  $t->is($gc1->getParent(), $ch4, '->getParent() on gc1 returns ch4.');

  //$t->is($gc1->getPathAsString(), 'Root li > pt2 > ch4 > gc1', 'Test getPathAsString() on gc1');

  // positional functions
  $t->info('  2.2 - Test some positional functions.');
  $t->is($pt1->isFirst(), true, '->isFirst() returns true for pt1.');
  $t->is($pt1->isLast(), false, '->isLast() returns false for pt1.');
  $t->is($pt2->isFirst(), false, '->isFirst() return false for pt2.');
  $t->is($pt2->isLast(), true, '->isLast() return true for pt2.');
  $t->is($ch4->isFirst(), true, '->isFirst() returns true for ch4.');
  $t->is($ch4->isLast(), true, '->isLast() returns true for ch4.');
  $t->is($ch1->getNum(), 1, '->getNum() on ch1 is 1');
  $t->is($ch2->getNum(), 2, '->getNum() on ch2 is 2');
  $t->is($ch3->getNum(), 3, '->getNum() on ch3 is 3');

  // array access
  $t->info('  2.3 - Test ArrayAccess interface');
  $t->is($menu['Parent 1']['Child 1'], $ch1, 'menu[Parent 1][Child 1] correctly returns the ch1 menu item.');

  // countable
  $t->info('  2.4 - Test Countable interface');
  $t->is(count($menu), $menu->count(), 'Test sfSympalMenu Countable interface');
  $t->is(count($pt1), 3, 'count($pt1) returns 3.');

  // iterator
  $t->info('  2.5 - Test IteratorAggregate interface');
  $count = 0;
  foreach ($pt1 as $key => $value)
  {
    $count++;
    $t->is($key, 'Child '.$count, 'Iterating exposes the key as the name of the menu item.');
    $t->is($value->getLabel(), 'Child '.$count, 'Iterating exposes the correct menu item as the value.');
  }

$t->info('3 - Test child-related functionality.');
  check_test_tree($t, $menu);
  print_test_tree($t); // print the test tree

  $t->info('  3.1 - Test basics of ->getChildren()');
  // getChildren(), removeChildren()
  $children = $ch4->getChildren();
  $t->is(count($children), 1, '->getChildren() on ch4 returns only one child menu item');
  $t->is($children[0]->name, $gc1->name, '->getChildren() on ch4 returns gc1 as the only menu item');

  $t->info('  3.2 - Test ->getFirstChild(), getLastChild().');
  $t->is($menu->getFirstChild(), $pt1, '->getFirstChild() on rt returns pt1.');
  $t->is($menu->getLastChild(), $pt2, '->getLastChild() on rt returns pt2.');

  $t->info('  3.3 - Test ->addChild().');
  $t->info('    a) Add a child (gc2) to ch4 via ->addChild().');
  $ch4->addChild('gc2');
  $t->is(count($ch4->getChildren()), 2, '->getChildren() on ch4 returns 2, reflecting the new child.');
  $t->info('    a) Add a child (gc3) to ch4 via the ArrayAccess method.');
  $ch4['gc3'];
  $t->is(count($ch4->getChildren()), 3, '->getChildren() on ch4 returns 3, reflecting both new children.');

  $t->info('  3.4 - Test ->getChild()');
  $t->is($ch4->getChild('Grandchild 1'), $gc1, '->getChild(Grandchild 1) returns gc1.');
  $t->is($ch4->getChild('gc4')->getName(), 'gc4', '->getChild() on a non-existent menu (gc4) creates a new child');
  $t->is(count($ch4), 4, 'count(ch4) now returns 4, reflecting this new child.');
  $t->is($ch4->getChild('nonexistent', false), null, '->getChild() on a non-existent menu passing false as the 2nd argument returns null without creating a new child.');

  $t->info('  3.5 - Test ->removeChildren()');
  $t->info('    a) ch4 now has 4 children (gc1, gc2, gc3, gc4). Remove gc4.');
  $ch4->removeChild('gc4');
  $t->is(count($ch4), 3, 'count(ch4) now returns only 3 children.');
  $t->is($ch4->getChild('Grandchild 1')->isFirst(), true, '->isFirst() on gc1 correctly returns true.');
  $t->is($ch4->getChild('gc3')->isLast(), true, '->isLast() on gc3 now returns true.');

  $t->info('    b) ch4 now has 3 children (gc1, gc2, gc3). Remove gc2.');
  $ch4->removeChild('gc2');
  $t->is(count($ch4), 2, 'count(ch4) now returns only 2 children.');
  $t->is($ch4->getChild('Grandchild 1')->isFirst(), true, '->isFirst() on gc1 correctly returns true.');
  $t->is($ch4->getChild('gc3')->isLast(), true, '->isLast() on gc3 now returns true');
  $t->is($gc1->getNum(), 1, '->getNum() on gc1 returns 1');
  $t->is($ch4->getChild('gc3')->getNum(), 2, '->getNum() on gc3 returns 2');

  $t->info('    c) ch4 now has 2 children (gc1, gc3). Remove gc3.');
  $ch4->removeChild('gc3');
  $t->is(count($ch4), 1, 'count(ch4) now returns only 1 child.');
  $t->is($gc1->isFirst(), true, '->isFirst() on gc1 returns true.');
  $t->is($gc1->isLast(), true, '->isLast() on gc1 returns true.');

  $t->info('    d) try to remove a non-existent child.');
  $ch4->removeChild('fake');
  $t->is(count($ch4), 1, '->removeChildren() with a non-existent child does nothing');

$t->info('4 - Check the credentials and security functions.');
  $userMenu = new ioMenuItem('user menu');
  $user = new sfBasicSecurityUser($configuration->getEventDispatcher(), new sfNoStorage());
  $t->is($userMenu->checkUserAccess($user), true, '->checkUserAccess() returns true for a menu with no restrictions.');

  $userMenu->requiresAuth(true);
  $t->is($userMenu->checkUserAccess($user), false, '->checkUserAccess() returns false if the menu requires auth but the user is not authenticated.');
  $user->setAuthenticated(true);
  $t->is($userMenu->checkUserAccess($user), true, '->checkUserAccess() returns true if the menu requires auth and the user is authenticated.');

  $userMenu->requiresNoAuth(true);
  $userMenu->requiresAuth(false);
  $t->is($userMenu->checkUserAccess($user), false, '->checkUserAccess() returns false if the menu requires NO auth and the user is authenticated.');
  $user->setAuthenticated(false);
  $t->is($userMenu->checkUserAccess($user), true, '->checkUserAccess() returns true if the menu requires NO auth and the user is NOT authenticated.');

  $userMenu = new ioMenuItem('user menu');
  $userMenu->setCredentials(array('c1', 'c2'));
  $user->addCredential('c1');
  $t->is($userMenu->checkUserAccess($user), false, '->checkUserAccess() returns false when the menu requires a credential the user does not have.');
  $user->addCredential('c2');
  $t->is($userMenu->checkUserAccess($user), true, '->checkUserAccess() returns true when the menu requires credentials but the user has those credentials.');

  $user->removeCredential('c2');
  $userMenu->setCredentials(array(array('c1', 'c2')));
  $t->is($userMenu->checkUserAccess($user), true, '->checkUserAccess() supports the nesting of credentials to handle OR logic.');


$t->info('5 - Check the "current" behavior.');
  $currentMenu = new ioMenuItemTest('root');
  $currentMenu->addChild('child', 'http://www.symfony-project.org');

  $t->info('  5.1 - Test the setting of the current uri.');
  $currentMenu->setCurrentUri('http://www.symfony-project.org');
  $t->is($currentMenu->getCurrentUri(), 'http://www.symfony-project.org', '->setCurrentUri() sets the current uri correctly.');
  $t->is($currentMenu['child']->getCurrentUri(), 'http://www.symfony-project.org', '->getCurrentUri() on the child was also set.');

  $currentMenu->setCurrentUri('http://www.sympalphp.org');
  $t->is($currentMenu->getCurrentUri(), 'http://www.sympalphp.org', '->setCurrentUri() sets the current uri correctly a second time.');
  $t->is($currentMenu['child']->getCurrentUri(), 'http://www.sympalphp.org', '->getCurrentUri() on the child was set for a second time.');

  $currentMenu['child']->addChild('grandchild', 'http://www.doctrine-project.org');
  $t->is($currentMenu['child']['grandchild']->getCurrentUri(), 'http://www.sympalphp.org', 'The current uri is passed to any new child objects.');

  $t->info('  5.2 - Test the isCurrent() and isCurrentAncestor() methods.');
  $t->is($currentMenu->isCurrent(), false, '->isCurrent() returns false, the route is not even set on that menu item.');

  $t->info('    a) Test isCurrent() on the top level.');
  $currentMenu->setRoute('http://www.sympalphp.org');
  $currentMenu->resetIsCurrent(); // force _current to be recalculated
  $t->is($currentMenu->isCurrent(), true, '->isCurrent() returns true, the current uri matches the uri of the menu item.');

  $t->info('    b) Test isCurrent() on the second level.');
  $currentMenu->setCurrentUri('http://www.symfony-project.org');
  $currentMenu->resetIsCurrent(); // force _current to be recalculated
  $currentMenu['child']->resetIsCurrent();
  $t->is($currentMenu->isCurrent(), false, '->isCurrent() on the root returns false, no longer matches the current uri.');
  $t->is($currentMenu['child']->isCurrent(), true, '->isCurrent() properly returns true on the child menu item.');
  $t->is($currentMenu->isCurrentAncestor(), true, '->isCurrentAncestor() returns true since its child is current.');
  $t->is($currentMenu['child']->isCurrentAncestor(), false, '->isCurrentAncestor() returns false when called in the current menu item itself.');

  $t->info('    c) Test isCurrent() on the third level.');
  $currentMenu->setCurrentUri('http://www.doctrine-project.org');
  $currentMenu->resetIsCurrent(); // force _current to be recalculated
  $currentMenu['child']->resetIsCurrent();
  $currentMenu['child']['grandchild']->resetIsCurrent();
  $t->is($currentMenu['child']->isCurrent(), false, '->isCurrent() on the child returns false, no longer matches the current uri.');
  $t->is($currentMenu['child']['grandchild']->isCurrent(), true, '->isCurrent() properly returns true on the grandchild menu item.');
  $t->is($currentMenu->isCurrentAncestor(), true, '->isCurrentAncestor() returns true on the root since its grandchild is current.');
  $t->is($currentMenu['child']->isCurrentAncestor(), true, '->isCurrentAncestor() returns true on the child since its child is current.');
  

$t->info('6 - Test the url, link, label rendering');
  check_test_tree($t, $menu);
  print_test_tree($t);

  $t->info('  6.1 - Test the getUri() method');
  $t->is($menu->getUri(), null, '->getUri() returns null when no route is set.');
  $menu->setRoute('http://www.sympalphp.org');
  $t->is($menu->getUri(), 'http://www.sympalphp.org', '->getUri() returns the raw url for an absolute route.');
  $menu->setRoute('@homepage');
  $t->is($menu->getUri(), url_for('@homepage'), '->getUri() returns the real url of a symfony route.');
  $t->info('    Using a bad route should throw the normal exception, with added text.');
  $menu->setRoute('@fake_route');
  try
  {
    $menu->getUri();
    $t->fail('Exception not thrown.');
  }
  catch (sfConfigurationException $e)
  {
    $t->pass('Exception thrown.');
  }

  $t->info('  6.2 - Test the basic rendering functions, renderLabel(), renderLink()');
  $t->is($menu->renderLabel(), 'Root li', '->renderLabel() on rt returns "Root li", its name');
  $menu->setLabel('root');
  $t->is($menu->renderLabel(), 'root', '->renderLabel() on rt returns "root" after setting the label');

  $menu->setRoute(null);
  $t->is($menu->renderLink(), $menu->renderLabel(), '->renderLink() == renderLabel() on rt because no route is set.');
  $menu->setRoute('http://www.google.com');
  $t->is($menu->renderLink(), '<a href="http://www.google.com">root</a>', '->renderLink() returns the correct link tag for an absolute url route.');
  $menu->setRoute('@homepage');
  $t->is($menu->renderLink(), '<a href="'.url_for('@homepage').'">root</a>', '->renderLink() returns the correct link tag for true symfony route.');
  $menu->setRoute(null); // set it back to null


$t->info('7 - Test some "intangible" functions (e.g. callRecursively()).');

  $t->info('  7.1 - Test callRecursively()');
  $otherMenu = new ioMenuItem('other');
  $otherMenu->addChild('child');
  $otherMenu['child']->addChild('grandchild');
  $t->info('    Call ->setLabel() recursively.');
  $otherMenu->callRecursively('setLabel', 'changed');
  $t->is($otherMenu->getLabel(), 'changed', 'The label was changed at the root.');
  $t->is($otherMenu['child']->getLabel(), 'changed', 'The label was changed on the child.');
  $t->is($otherMenu['child']['grandchild']->getLabel(), 'changed', 'The label was changed on the grandchild.');

  $t->info('  7.2 - Test setDepth()');
  $t->info('    a) Set a high depth, has no effect.');
  $otherMenu->setDepth(10);
  $t->is($otherMenu->showChildren(), true, 'The root still shows children.');
  $t->is($otherMenu['child']->showChildren(), true, 'The child still shows children.');
  $t->is($otherMenu['child']['grandchild']->showChildren(), true, 'The grandchild still shows children.');

  $t->info('    b) Set a depth of 0, children are hidden at the top level.');
  $otherMenu->setDepth(0);
  $t->is($otherMenu->showChildren(), false, 'The root hides its children.');

  $t->info('    c) Set a depth of 1, only children are shown');
  $otherMenu->setDepth(1);
  $t->is($otherMenu->showChildren(), true, 'The root shows its children.');
  $t->is($otherMenu['child']->showChildren(), false, 'The child hides its children.');

$t->info('8 - Test the render() method.');
  check_test_tree($t, $menu);
  print_test_tree($t);

  $t->info('  8.1 - Render the menu in a few basic ways');
  $rendered = '<ul class="root"><li class="first">Parent 1<ul class="menu_level_1"><li class="first">Child 1</li><li>Child 2</li><li class="last">Child 3</li></ul></li><li class="last">Parent 2<ul class="menu_level_1"><li class="first last">Child 4<ul class="menu_level_2"><li class="first last">Grandchild 1</li></ul></li></ul></li></ul>';
  $t->is($menu->render(), $rendered, 'The full menu renders correctly.');

  $t->info('  8.2 - Set a title and class on pt2, and see that it renders.');
  $pt2->setAttribute('class', 'parent2_class');
  $pt2->setAttribute('title', 'parent2 title');
  $rendered = '<ul class="root"><li class="first">Parent 1<ul class="menu_level_1"><li class="first">Child 1</li><li>Child 2</li><li class="last">Child 3</li></ul></li><li class="parent2_class last" title="parent2 title">Parent 2<ul class="menu_level_1"><li class="first last">Child 4<ul class="menu_level_2"><li class="first last">Grandchild 1</li></ul></li></ul></li></ul>';
  $t->is($menu->render(), $rendered, 'The menu renders with the title and class attributes.');

  $t->info('  8.3 - Set ch2 menu as current, look for "current" and "current_ancestor" classes.');
  $ch2->isCurrent(true);
  $rendered = '<ul class="root"><li class="current_ancestor first">Parent 1<ul class="menu_level_1"><li class="first">Child 1</li><li class="current">Child 2</li><li class="last">Child 3</li></ul></li><li class="parent2_class last" title="parent2 title">Parent 2<ul class="menu_level_1"><li class="first last">Child 4<ul class="menu_level_2"><li class="first last">Grandchild 1</li></ul></li></ul></li></ul>';
  $t->is($menu->render(), $rendered, 'The menu renders with the current and current_ancestor classes.');

  $t->info('  8.4 - Make ch4 hidden due to not having proper credentials');
  $ch4->requiresAuth(true);
  $rendered = '<ul class="root"><li class="current_ancestor first">Parent 1<ul class="menu_level_1"><li class="first">Child 1</li><li class="current">Child 2</li><li class="last">Child 3</li></ul></li><li class="parent2_class last" title="parent2 title">Parent 2</li></ul>';
  $t->is($menu->render(), $rendered, 'The menu renders, but ch4 and children are not shown.');
  $ch4->requiresAuth(false); // fix ch4

  $t->info('  8.5 - Only render a submenu portion.');
  $rendered = '<ul class="parent2_class" title="parent2 title"><li class="first last">Child 4<ul class="menu_level_2"><li class="first last">Grandchild 1</li></ul></li></ul>';
  $t->is($menu['Parent 2']->render(), $rendered, 'The pt2 menu renders as a ul with the correct classes and its children beneath.');

  $t->info('  8.6 - Test showChildren() functionality.');
  $menu['Parent 1']->showChildren(false);
  $rendered = '<ul class="root"><li class="current_ancestor first">Parent 1</li><li class="parent2_class last" title="parent2 title">Parent 2<ul class="menu_level_1"><li class="first last">Child 4<ul class="menu_level_2"><li class="first last">Grandchild 1</li></ul></li></ul></li></ul>';
  $menu['Parent 1']->showChildren(true); // replace the setting

  $menu->showChildren(false);
  $t->is($menu->render(), '', '->showChildren(false) at the root renders a blank string.');

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