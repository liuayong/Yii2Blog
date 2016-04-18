<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\User2;
use app\models\Comment;

class TestController extends Controller {

    public function actionIndex() {
	$model = Comment::find();
	$dataProvider = new \yii\data\ActiveDataProvider([
	    'query' => $model,
	    'pagination' => [
		'pagesize' => 2
	    ]
	]);

	return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionNav() {

	echo "<div>当前控模块: " . Yii::$app->requestedAction->controller->module->id . "</div>";
	echo "<div>当前控制器: " . Yii::$app->requestedAction->controller->id . "</div>";
	echo "<div>当前方法: " . Yii::$app->requestedAction->id . "</div>";
	echo "<div>当前方法: " . Yii::$app->requestedAction->actionMethod . "</div>";	// actionNav


	var_dump($controllerID = Yii::$app->controller->id);
	var_dump($actionID = Yii::$app->controller->action->id);

	var_dump($controllerID = Yii::$app->controller->id);
	var_dump($actionID = Yii::$app->controller->action->id);

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

    public function actionInsert() {
	$model = new User2();

	if ($model->load(Yii::$app->request->post()) && $model->validate()) {
	    $upload = $this->uploadedFile($model, 'profile');
	    $uploadpath = $this->fileExists('./images/' . date('Ymd') . '/');

	    if ($model->save()) {
		$upload->saveAs($uploadpath . $upload->name);
	    }
	} else {
	    return $this->render('/user/create_1', [
			'model' => $model,
	    ]);
	}
    }

    protected function uploadedFile($model, $item) {

	$upload = \yii\web\UploadedFile::getInstance($model, $item);
	$model->$item = $upload->name;
	return $upload;
    }

    protected function fileExists($uploadpath) {

	if (!file_exists($uploadpath)) {
	    mkdir($uploadpath, 0777, true);
	}
	return $uploadpath;
    }

    public function actionQuery() {
	$ret = User2::findBySql('SELECT * FROM tbl_post')->all();
	var_dump($ret);
    }

    public function actionUpdate() {
	
    }

    public function actionDelete() {
	$ret = User2::deleteAll("username = 'lyy'");
	var_dump($ret);
    }

}
