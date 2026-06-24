<?php
namespace App\Controllers;

use Hiya\Base\Controller;

class SiteController extends Controller
{
    public $layout = 'main';
    
    public function actionIndex()
    {
        $this->render('index', [
            'title' => 'Welcome to Hiya',
            'message' => 'This is page running on Hiya!',
        ]);
    }
    
    public function actionAbout()
    {
        $this->render('about');
    }
    
    public function actionContact()
    {
        $this->render('contact');
    }
}