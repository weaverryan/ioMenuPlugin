<?php

function render_ioMenu($name = null){
  include(sfContext::getInstance()->getConfigCache()->checkConfig('config/navigation.yml'));
  
  $menu = ioMenu::createFromArray($$name);

  return $menu->render();
}

function get_ioMenu($name = null){
  include(sfContext::getInstance()->getConfigCache()->checkConfig('config/navigation.yml'));

  $menu = ioMenu::createFromArray($$name);

  return $menu;
}
