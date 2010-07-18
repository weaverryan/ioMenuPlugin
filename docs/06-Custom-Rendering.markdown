Custom rendering
====================================

Basically, to render menu, you can use render method or convert it to string:

    $menu = new ioMenuItem('My menu');
    $menu->addChild('overview', '@homepage');
    $menu->addChild('comments', '@comments');
    // render menu with render method
    echo $menu->render();
    // render menu by converting to string
    echo $menu;

This will render menu with default renderer, which renders as unordered list:

    <ul class="menu">
      <li class="first">
        <a href="/">overview</a>
      </li>
      <li class="current last">
        <a href="/comments">comments</a>
      </li>
    </ul>

If you would like to render menu differently, you can create class which
implements ioMenuItemRenderer interface. Then, you can use custom renderer with couple
ways:

    $renderer = new myCustomRenderer();
    // first way is to call render method of renderer
    echo $renderer->render($menu);
    // second way is to change default renderer
    ioMenuItem::setDefaultRenderer($renderer);
    // now every call will render with custom renderer
    echo $menu->render();
    echo $menu;
    
