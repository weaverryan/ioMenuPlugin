Reordering and splicing menus
=============================

Once you have a menu tree, there are many options available to reorder
children, slice out only a portion of a menu, or split the menu into
pieces.

Reordering menus
----------------

A number of methods are available to reorder the children menu items for
a particular menu item. The most powerful is `reorderChildren()`:. Let's
start with the following simple menu tree:

    $menu = new IoMenu();
    $menu->addChild('ch1');
    $menu->addChild('ch2');
    $menu->addChild('ch3');

Reordering the children is easy:

    // change order to ch2, ch3, ch1
    $menu->reorderChildren(array('ch2', 'ch3', 'ch1'));

    // change order to ch3, ch2, ch1
    $menu['ch3']->moveToFirstPosition();

    // change order to ch2, ch1, ch3
    $menu['ch3']->moveToLastPosition();

    // change order to ch1, ch2, ch3
    $menu['ch2']->moveToPosition(2);

    // chnage order to ch2, ch3, ch1
    $menu->moveChildToPosition($menu['ch1'], 3);

>When reordering the menus, the position begins with the integer `1`.

Slicing menus
--------------

Sometimes it may be useful to 




--->To continue reading, see Chapter 4: Importing from YAML and the database