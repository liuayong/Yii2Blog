<?php
	
namespace app\controllers;

use Yii;
use yii\web\Controller;

class TestController extends Controller {
    
    public function actionIndex() {
	$homePage = Yii::$app->homeUrl ;
	var_dump($homePage);
    }
}