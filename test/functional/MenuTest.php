<?php

require_once dirname(__FILE__).'/../bootstrap/functional.php';

$browser = new sfTestFunctional(new sfBrowser());

$browser->info('1 - Surf to a url with a complex menu and check several things.')
  ->get('/big-menu')
 ;

$pretty = '<ul class="root">
  <li class="first">
    <a href="'.url_for('@homepage', true).'">Parent 1</a>
    <ul class="menu_level_1">
      <li class="first">
        <a href="'.url_for('/parent1/ch1', true).'?test=1">Child 1</a>
      </li>
      <li class="last">
        <a href="'.url_for('/parent1/ch2').'">Child 2</a>
      </li>
    </ul>
  </li>
  <li class="current_ancestor last">
    Parent 2
    <ul class="menu_level_1">
      <li class="current first last">
        <a href="'.url_for('@test_menu').'">Child 4</a>
        <ul class="menu_level_2">
          <li class="first last">
            Grandchild 1
          </li>
        </ul>
      </li>
    </ul>
  </li>
</ul>
';

$browser->info("Rendered Menu: \n".$pretty);
$browser->test()->is($browser->getResponse()->getContent(), $pretty, 'The full rendered menu is correct with the uncompressed spacing.');