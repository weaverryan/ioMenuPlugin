ioMenuPlugin
============

A plugin to make menus easier to write in symfony.

 * Current menu item automatically given `active` class
 * Menu items automatically given `first` and `last` classes
 * Show/hide menus based on authentication, credentials
 * Hide portions of the tree, or render down to a certain depth
 * Menu rendered with "pretty" spacing for easier debugging & styling

Inspired by [sympal](http://www.sympalphp.org) and the
[SemanticMenu](http://github.com/danielharan/semantic-menu) from Ruby on Rails.

Usage
-----

Assume any of the routes have been defined in `routing.yml`:

    $menu = new ioMenu();
    $menu->addChild('overview', '@homepage');
    $menu->addChild('comments', '@comments');
    echo $menu->render();

Assuming you are on /comments, the output would be:

    <ul class="menu">
      <li class="first">
        <a href="/">overview</a>
      </li>
      <li class="current last">
        <a href="/comments">comments</a>
      </li>
    </ul>

The `ioMenu` class optionally takes an array of attributes as its first
argument. You can also nest menus as deeply as you want:

    $menu = new ioMenu(array('class' => 'top_level_nav'));
    $menu->addChild('overview', '@homepage');
    $menu->addChild('comments', '@comments', array('class' => 'button'));
      $menu['comments']->addChild('My Comments', '@my_comments');
      $menu['comments']->addChild('Recent', '@recent_comments');

    echo $menu->render();

Assuming you're in the /my-comments page, the output would be:

    <ul class="top_level_nav">
      <li class="first">
        <a href="/">overview</a>
      </li>
      <li class="button current_ancestor last">
        <a href="/comments">comments</a>
        <ul class="menu_level_1">
          <li class="current first">
            <a href="/my-comments">My Comments</a>
          </li>
          <li class="last">
            <a href="/recent">Recent</a>
          </li>
        </ul>
      </li>
    </ul>

If you will, you can define your menus in a yaml file!

Simply create a navigation.yml in your app config folder.
It has a fluent interface!

    #the main menu
    adminMenu:
      name: adminMenu
      attributes:
        id: main-nav
      children:
        -
          name: Homepage
          route: homepage
        -
          name: foo
          route: default/foo
        -
          name: bar
          route: http://foo.com
        -
          name: baz
          route: @default

    #the admin menu
    adminMenu:
      name: adminMenu
      attributes:
        id: main-nav
      children:
        -
          name: Dashboard
          route: homepage
        -
          name: User-Managment
          route:
          children:
            -
              attributes:
                id: foo
                onclick: 'alert("bar");'
              name: User
              route: sfGuardUser/index
            -
              name: Groups
              route: sfGuardGroup/index
            -
              name: Permission
              route: sfGuardPermission/index
        -
          name: Signout
          route: sfGuardAuth/signout

The default symfony security settings will be merged into this structure (currently it only respects credentials) from the corresponding security.yml file for each route.

To retrieve those cached menu easily, use the *ioMenu* helper, activate it in your settings.yml

To finally fetch a menu, use:

    $menu = get_menu('mainMenu');
    echo $menu->render();

    $menu = get_menu('adminMenu');
    echo $menu->render();

TODO
-----

  - test the config handler and the generated menu cache
  - correct fetching of cascading credentials