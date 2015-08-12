<?php
class home extends controller {
    function action_index()
    {
        $this->redirect( "calendar/index" );
    }
}