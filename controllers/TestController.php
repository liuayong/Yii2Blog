<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class TestController extends Controller {

    public function actionIndex() {
	var_dump(\app\models\Lookup::item("PostStatus", 1) );
	var_dump(\app\models\Lookup::item("PostStatus", 2) );
    }

    public function actionListView() {
	$query = \app\models\Post::find();  //	object(yii\db\ActiveQuery)
	$dataProvider = new \yii\data\ActiveDataProvider([
	    'query' => $query,
	    'pagination' => [
		'pageSize' => 5
	    ]
	]);
	return $this->render('list', ['dataProvider' => $dataProvider]);
    }

}
