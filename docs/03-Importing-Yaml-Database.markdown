Importing from YAML and the database
====================================

One of the most exciting things about the menu framework is the easy with
which you can store and import menu structures. This chapter will show you
how to store menu trees in YAML as well as how to persist and retrieve
them to and from the database via the `ioDoctrineMenuItemPlugin`. As a
bonus, the `ioDoctrineMenuItemPlugin comes with a built-in admin for reordering
and modifying the menu items in the database.

Importing from YAML
-------------------

As explained in the previous chapter, the `ioMenuItem` object can be used
to create entire menu trees from an array input. This makes specifying
menus in YAML extremely easy. Suppose we want to specify an admin menu
in `app.yml`:

  all:
    menus:
      admin_menu:
        root:
          name:     Admin menu
          children:
            signin:
              name:    Sign in
              route:   @sf_guard_signin
              requires_no_auth: true
              attributes: {class: signin }
            signout:
              name:    Sign out
              route:   @sf_guard_signout
              requires_auth: true
            user_admin:
              name:          User Admin
              requires_auth: true
              children:
                manage_users:
                  name:    Manage Users
                  route:   @sf_guard_user
                  credentials: [ManageUsers]
                manage_permissoins:
                  name:    Manage Permissions
                  route:   @sf_guard_permission
                  credentials: [ManagePermissions]

Creating and rendering a menu item from this object is easy:

    $arr = sfConfig::get('app_menus_admin_menu');
    $menu = ioMenuItem::createFromArray($arr);
    echo $menu->render();

Saving menus to the database
----------------------------

Up to this point, we haven't talked about one of the most powerful features
of the menu framework. Specifically, by using the `ioDoctrineMenuPlugin`,
menu trees can be easily persisted to and retrieved from the database.
Let's start with an example:

    $menu = new ioMenuItem('primary');
    $menu->addChild('overview', '@homepage')
      ->setAttributes('class' => 'home');
    $menu->addChild('signin', @signin')
      ->requiresNoAuth(true);

    Doctrine_Core::getTable('ioDoctrineMenuItem')->persist($menu);

That's it! You're entire menu tree was stored as a Nested Set in the
ioDoctrineMenuItem model. The root of the nested set will be the entry
named 'primary'.

Retrieving menus from the database
----------------------------------

Fetching menus from the database is just as easy:

    $menu = Doctrine_Core::getTable('ioDoctrineMenuItem')
      ->fetchMenu('primary');

With one simple line of code, the menu tree with root node named "primary"
is retrieved from the database and transformed into an ioMenuItem tree.

Caching Doctrine menu trees
---------------------------

Even better, you can easily cache the menu trees so that they're only
pulled from the database the first time. While the cache is setup by
default, you may configure it further in `app.yml`:

    all:
      doctrine_menu:
        cache:
          enabled:  true
          class:    sfFileCache
          options:
            cache_dir:  <?php echo sfConfig::get('sf_app_cache_dir') ?>/io_doctrine_menu

To use the cache, simply fetch your trees using the `ioDoctrineMenuManager`
instead of using the `ioDoctrineMenuItemTable::fetchMenu()` method directly.
To do this, you'll first need to get an instance of `ioDoctrineMenuPluginConfiguration`.
While this will be made easier in the future
[http://redmine.sympalphp.org/issues/110](#110)
[http://redmine.sympalphp.org/issues/111](#111), this can be done fairly
easily from the actions class:

    public function executeIndex(sfWebRequest $request)
    {
      $this->menu = $this->getContext()
        ->getConfiguration()
        ->getPluginConfiguration('ioDoctrineMenuPlugin')
        ->getMenuManager()
        ->getMenu('admin');
    }

By enabling cache in `app.yml` and retrieving your doctrine menus using
the menu manager, all the menus will be retrieved from the database only
once and then cached. If a menu item is updated in the database, the menu
cache is automatically flushed.

Draggable, sortable admin interface
-----------------------------------

Another perk of the `ioDoctrineMenuPlugin` is that it comes packaged with
an admin interface where the admin user can easilyreorder and reorganize the
menus.
