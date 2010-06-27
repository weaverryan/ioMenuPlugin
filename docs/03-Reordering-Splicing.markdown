Reordering and slicing menus
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
    $menu['ch2']->moveToPosition(1);

    // change order to ch2, ch3, ch1
    $menu->moveChildToPosition($menu['ch1'], 2);

>When reordering the menus, the position begins with the integer `0`.

Slicing menus
--------------

Sometimes it may be useful to return just a subset of your menu for rendering.
To perform these types of operations, a powerful `slice()` method is available,
which works much like the native php array_slice function. Take the same
simple example from above:

    $menu = new IoMenu();
    $menu->addChild('ch1');
    $menu->addChild('ch2');
    $menu->addChild('ch3');

To slice a menu, specify the start position and (optionally) a `$length`
argument. Both arguments can be either integer offsets, names of the children
menu items, or the children menu items themselves:

    // return just ch1, ch2
    $newMenu = $menu->slice(0, 2);
    $newMenu = $menu->slice('ch1', 'ch2');
    $newMenu = $menu->slice($menu['ch1'], $menu['ch2']);

The `slice` method is very flexible, allowing for a negative offset and
an optional `$length` argument:

    // return just ch2, ch3
    $newMenu = $menu->slice(1, 3);
    $newMenu = $menu->slice('ch2', 'ch3');
    $newMenu = $menu->slice(1);
    $newMenu = $menu->slice(-2);

The `$newMenu` item is a new `ioMenuItem` object - your original menu
item remains unaffected.

Splitting menus
---------------

In other cases, you may want to split your menu item into pieces. For example,
you may have a large primary menu, which, for space purposes, is actually
split visually on your page. The first XX items of your menu are shown in
a primary location (e.g. top navigation) while all others are shown in a
secondary location (e.g. a sidebar).

To accomplish this, a `split()` method is available. Consider the following
example:

    $menu = new IoMenu();
    $menu->addChild('ch1');
    $menu->addChild('ch2');
    $menu->addChild('ch3');
    $menu->addChild('ch4');

The `split` menu returns two separate `ioMenuItem` objects in an array
with keys `primary` and `secondary`. The first and only argument is
the size of the `primary` menu.

    $arr = $menu->split(2);

    // $arr['primary'] contains ch1, ch2
    echo $arr['primary']

    // $arr['secondary'] contains ch3, ch4
    echo $arr['secondary']

Alternatively, you can specify the name of last menu item that should be
included in the `primary` menu:

    $arr = $menu->split('ch3');

    // $arr['primary'] contains ch1, ch2, ch3
    echo $arr['primary']

    // $arr['secondary'] contains ch4
    echo $arr['secondary']

--->To continue reading, see Chapter 4: Importing from YAML and the database