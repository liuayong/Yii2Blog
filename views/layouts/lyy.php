<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html ;
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;

use app\assets\AppAsset;
AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
	<meta charset="<?= Yii::$app->charset ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?= Html::csrfMetaTags() ?>
	<title><?= Html::encode($this->title) ?></title>
	<?php $this->head() ?>
    </head>
    <body>
	<?php $this->beginBody() ?>

	<div class="wrap">
	    <?php
	    NavBar::begin(['brandLabel' => 'NavBar Test']);
	    echo Nav::widget([
		'items' => [
		    ['label' => 'Home', 'url' => ['/site/index']],
		    ['label' => 'About', 'url' => ['/site/about']],
		],
		'options' => ['class' => 'navbar-nav'],
	    ]);
	    NavBar::end();
	    ?>
	    <div class="container">
		<?= $content ?>
	    </div>
	</div>

	<?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
