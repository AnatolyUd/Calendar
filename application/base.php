<?php
class application
{
	public $uri; 

	function __construct( $uri = null )
	{
		$this->uri = $uri;
		$this->loadController( $uri['controller'] );
	}

	function loadController( $class )
	{
		$file = "application/controllers/".$this->uri['controller'].".php";

		if(!file_exists($file)) die( "controller not found at $file" );

		require_once($file);

		$controller = new $class();

		if( method_exists( $controller, $this->uri['method'] ) ){
			$controller->{$this->uri['method']}( $this->uri['var'] );
		} 
		else {
			$controller->action_index();
		}
	}
}

class model
{ 
	function __construct(){}
}

class controller
{
	function loadModel( $model )
	{
		require_once( 'application/models/'. $model .'.php' );
        $class_name = 'Model_'.$model;
		return new $class_name;
	} 

	function loadView( $view, $vars="" )
	{
        $content = $this->capture('application/views/'.$view.'.html', $vars);
        extract(array('content'=>$content), EXTR_SKIP);
		require_once( 'application/views/layout.html' );
	}

	function redirect( $uri )
	{
		header( "Location: index.php?route=$uri" );
		die();
	}

    function capture($view_filename, array $view_data)
    {
        // Import the view variables to local namespace
        extract($view_data, EXTR_SKIP);
        ob_start();
        try
        {
            include $view_filename;
        }
        catch (Exception $e)
        {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }


}
