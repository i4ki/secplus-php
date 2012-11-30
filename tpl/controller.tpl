<?php

class {#controller_name#}Controller extends SecPlus\AbstractController {
  public function setup() {
    /**
     * This is the first method called by framework, before handle
     * the action.
     */

     /* Calling this method, will use the default action handler.
        With this, SecPlus-PHP will try to use any method of the controller as
        action IF and only IF the method appear in the safeActions property.
        For use the method 'view' as a action, you need to add it to the array.
      */

     $this->safe_actions[] = 'view';
     
     $this->handleAction();
  }

  public function view() {
    /* See the file HomeView.php */
    $this->render('{#controller_name#}');
  }
}
