Internationalizing your menus
=============================

Both the ioMenuPlugin and ioDoctrineMenuPlugin libraries fully support
internationalization (i18n).

Basic i18n via __()
-------------------

When using a menu item, the only content that needs to be translated is
the label, which is output in the body of the menu's `li` tag and is wrapped
in an anchor tag if a route is supplied:

    <li>
      <a href="/">Homepage</a>
    </li>

If i18n is enabled via `settings.yml`, each menu item will automatically
be processed through the i18n `__()` method. This means you can translate
each menu item via the standard xliff dictionary (or whichever dictionary
you use).

Setting explicit culture translations
-------------------------------------

You can also choose to explicitly set the translation for each culture on
the menu item. For example, you could explicitly set the spanish translation
of a menu item:

    $menu = new ioMenu('home');

    // set the default label, used if no translation is available
    $menu->setLabel('Homepage');

    // set the spanish translation
    $menu->setLabel('Página principal', 'es');

Next, we'll want to render our menu tree for a specific culture. To explicitly
set the culture to use when rendering a menu, use `setCulture()`:

    $menu->setCulture('es');
    $menu->getLabel(); // returns 'Página principal'

The culture used to render a menu is determined in the following way:

 1. If the culture is explicitly set via `setCulture`, that culture is used.
 1. If any of the menu's ancestors have their culture explicitly set via
    `setCulture`, that culture will be used
 1. If a context is available, the current user's culture is used
 1. As a last resort, the `sf_default_culture` value is used.

If no translation is available for a particular culture, the menu item
attempts to use the translation for the `sf_default_culture` culture. If
that's not available, the default label is used (`Homepage` in the above
example).

Importing and exporting i18n menus
----------------------------------

As seen in the previous chapter, menus can be imported and exported by
leveraging the `toArray()` and `fromArray()` methods. If any i18n labels
are set, they will be exported in `toArray()` via the `i18n_labels` array.
This allows you to create menu items via `app.yml` that looks like this:

  all:
    menus:
      admin_menu:
        root:
          name:     Admin menu
          children:
            signin:
              label:   Sign in
              i18n_labels:
                es:    Iniciar sesión
              route:   @sf_guard_signin
              requires_no_auth: true
              attributes: {class: signin }

Using i18n with the `ioDoctrineMenuItem` Doctrine model
-------------------------------------------------------

Perhaps the most common source for your `ioMenuItem` trees will be the
database via the `ioDoctrineMenuItem` model. This model supports Doctrine's
`I18n` behavior for the `label` field. To enable i18n, enable it in `app.yml`:

    all:
      doctrine_menu:
        use_i18n:   true

Rebuild your model via `doctrine:build --all-classes`. You should now have
a corresponding i18n model.

Like any other `I18n` Doctrine model, you'll be able to set different
translations for the `label` field:

    $menu = new ioDoctrineMenuItem();
    $menu['Translation']['es']['label'] = 'Página principal';

If an `ioMenuItem` tree is created from this object, the i18n labels will
be correctly merged into the i18n labels of the `ioMenuItem` object.

The system works in the other direction as well:

    $menu = new ioMenuItem('primary');
    $menu->addChild('admin');
    $menu->setLabel('administración', 'es');

If the above menu item were persisted to the database, a spanish translation
would be saved on the i18n table.