<?php
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
    <head>
	<meta charset="<?= Yii::$app->charset ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $this->head() ?>
    </head>
<body>
<?php $this->beginBody() ?>
<div class="wrap">
<?php
NavBar::begin(['brandLabel' => 'NavBar Test']);
echo Nav::widget([
    'items' => [
        [
            'label' => 'Home',
            'url' => ['site/index'],
            'linkOptions' => [ 'title' => 'title属性'],
        ],
        [
            'label' => 'Dropdown',
            'items' => [
                 ['label' => 'Level 1 - Dropdown A', 'url' => ['site/about']],
                 '<li class="divider"></li>',
                 '<li class="dropdown-header">Dropdown Header</li>',
                 ['label' => 'Level 1 - Dropdown B', 'url' => 'www.baidu'],
            ],
        ],
        [
            'label' => 'Login',
            'url' => ['site/login'],
            'visible' => !Yii::$app->user->isGuest
        ],
    ],
    'options' => ['class' =>'nav-pills'], // set this to nav-tab to get tab-styled navigation
]);
NavBar::end();

/*

*/
?>
</div>
 
    
<?php echo  $content  ;?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
