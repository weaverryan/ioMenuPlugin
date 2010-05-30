<?php

class testActions extends sfActions
{
  public function executeBigMenu(sfWebRequest $request)
  {
    // setup a menu with a variety of conditions difficult to test in unit tests
    $menu = new ioMenuItem('Root li', null, array('class' => 'root'));
    $pt1 = $menu->addChild('Parent 1', 'homepage');
    $ch1 = $pt1->addChild('Child 1', '/parent1/ch1');
    $ch2 = $pt1->addChild('Child 2', '/parent1/ch2');
    $ch3 = $pt1->addChild('Child 3', '/parent1/ch3');

    $pt2 = $menu->addChild('Parent 2');
    $ch4 = $pt2->addChild('Child 4');
    $gc1 = $ch4->addChild('Grandchild 1');

    // setup ch4 to be the current menu
    $ch4->setRoute('@test_menu');

    // setup ch3 to be hidden since we won't be authenticated
    $ch3->requiresAuth(true);

    // setup pt1 and ch1 to render absolutely, in two different ways
    $pt1->setUrlOptions(array('absolute' => true));
    $ch1->setLinkOptions(array('absolute' => true, 'query_string' => 'test=1'));

    $this->menu = $menu;
    $this->setLayout(false);
  }
}