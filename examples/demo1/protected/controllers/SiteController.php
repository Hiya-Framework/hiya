<?php
namespace App\Controllers;

use Hiya\Base\Controller;

class SiteController extends Controller
{
	public $layout='column1';


    public function actionIndex()
    {
        $this->render('index');
    }

    public function actionAbout()
    {
        $this->render('about');
    }
	
	public function actionError()
	{
	    if($error=Hiya::app()->errorHandler->error)
	    {
	    	if(Hiya::app()->request->isAjaxRequest)
	    		echo $error['message'];
	    	else
	        	$this->render('error', $error);
	    }
	}

}