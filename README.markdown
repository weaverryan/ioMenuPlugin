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

A small book has been written to support this plugin and
[ioDoctrineMenuItemPlugin](http://github.com/weaverryan/ioDoctrineMenuPlugin):
[Menu Reference Manual](http://github.com/weaverryan/ioMenuPlugin/tree/master/docs/). 

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

Installation
------------

### With git

    git submodule add git://github.com/weaverryan/ioMenuPlugin.git plugins/ioMenuPlugin
    git submodule init
    git submodule update

### With subversion

    svn propedit svn:externals plugins

In the editor that's displayed, add the following entry and then save

    ioMenuPlugin https://svn.github.com/weaverryan/ioMenuPlugin.git

Finally, update:

    svn up

# Setup

In your `config/ProjectConfiguration.class.php` file, make sure you have
the plugin enabled.

    $this->enablePlugins('ioMenuPlugin');

In-depth documentation
----------------------

An in-depth reference manual is available:
[Menu Reference Manual](http://github.com/weaverryan/ioMenuPlugin/tree/master/docs/).

Care to Contribute?
-------------------

Please clone and improve this plugin! This plugin is by the community and
for the community and I hope it can be final solution for handling menus.

If you have any ideas, notice any bugs, or have any ideas, you can reach
me at ryan [at] thatsquality.com.

A bug tracker is available at
[http://redmine.sympalphp.org/projects/io-menu](http://redmine.sympalphp.org/projects/io-menu)

This plugin was taken from [sympal CMF](http://www.sympalphp.org) and was
developed by both Ryan Weaver and Jon Wage.