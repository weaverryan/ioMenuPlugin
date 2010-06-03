Creating Advanced Menus
=======================

As we learned in the previous chapter, menus can be easily created, traversed,
and configured. However, for more complex situation, we'll need even more.

In this chapter, we'll show you how to hide menu items based on authentication
and credentials, customize the url and anchor tag, change the underlying
php class for a menu item, call functions recursively and other advanced
strategies.

Security and credentials
------------------------

A common requirement is to hide or show menu items based on whether or
not the user is logged in and his/her credentials. For example, you'll
want to show a "My account" link only when the user is authenticated, but
you'll want to show a "register" menu only when the user is _not_ authenticated.
Consider the following example:

    $menu = new ioMenu();

    // only show if the user is NOT authenticated
    $menu->addChild('login')->requiresNoAuth(true);

    // only show if the user IS authenticated
    $menu->addChild('manage account')->requiresAuth(true);

    // only show if the user has the "ManagerUsers" credential
    $menu->addChild('manage users')->setCredentials(array('ManageUsers'));

>**NOTE**
>Another great feature of the menu framework is that many functions are
>chainable so that you can customize menu items via a fluent interface.

Using the built-in security features, you can easily built large menus
and cleanly allow a few items to respond to different security environments.

Customizing link and url options
--------------------------------

While using the `setAttributes()` method affects the `<li>` tag, it is
also possible to make changes to the anchor tag itself. This is done
via the `setLinkOptions()` method. The options set here correspond to
the options that available for symfony's `link_to()` function:

    $menu->addChild('logout', '@signout')->setLinkOptions(array(
      'class' => 'logout',
      'confirm' => 'Are you sure you want to logout?',
    ));

In some situations, you may want to further customize the url itself. This
can be done via the `setUrlOptions()` method:

    $menu->addChild('logout', '@signout')->setUrlOptions(array(
      'absolute' => 'true',
    ));

Controlling the class of your children
--------------------------------------

By default, when creating a menu item via `addChild()`, the new child menu
is created using the same class as the parent. You can do this by specifying
a 4th argument to `addChild()`

    $menu->addChild('overview', @homepage', array(), 'myMenuItem');

or you can pass `addChild()` a menu object:

    $child = new myMenuItem('overview', '@overview');
    $menu->addChild($child);

Calling methods recursively
---------------------------

In some situations, you may find it necessary to call a method on every
object in part of (or the entire) tree. This is easily done with
`callRecursively()`. For example, suppose you need every url in your tree
to be rendered with an absolute url. Assuming you haven't already customized
any url options, simply do the following:

    $menu->callRecursively('setUrlOptions', array(
      'absolute' => true,
    ));

The url of every menu item beneath `$menu` will now render with an absolute url.

Exporting & importing via an array
----------------------------------

As you'll see in the next chapter on data sources (we'll be saving and
retrieiving menus to and from the database). The key behind that process
is the `toArray()` and `fromArray()` methods. If you're familiar with
Doctrine's methods by the same name, these work much the same way.

    $menu = new ioMenuItem('My root');
    $menu->addChild('overview', '@homepage')
      ->setAttributes('class' => 'home')
      ->addChild('about', @about');
    $menu->addChild('login')->requiresNoAuth(true);
    print_r ($menu->toArray());

The above method would render an array like this (shortned a bit here):

    array(
      'name'      => 'My root',
      'children'  => array(
        'overview' => array(
          'name' => 'overview',
          'route' => '@homepage',
          'attributes' => array(
            'class' => 'home',
          ),
        ),
        'login' => array(
          'name' => 'login',
          'requires_no_auth' => true,
        ),
      ),
    )

The beauty of this is that a menu item can be created using `fromArray()`
just as easily:

    $arr = $menu->toArray();
    $newMenu = new ioMenu();
    $newMenu->fromArray($arr);

The `$newMenu` menu tree will be identical to the original menu tree. This
means that, for example, menus can be specified entirely in `app.yml` and
then easily used to create menu trees. More on that in chapter 3.

