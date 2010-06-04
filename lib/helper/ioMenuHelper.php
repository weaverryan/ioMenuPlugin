<?php

function get_menu($name = null){
  include(sfContext::getInstance()->getConfigCache()->checkConfig('config/navigation.yml'));
  
  return ioMenu::createFromArray(unserialize($$name));
}