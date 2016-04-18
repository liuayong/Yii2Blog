<?php

use yii\grid\GridView;
$this->title = '页面';


?>
<?=

GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
	'id',
	[
	    'attribute' => 'content',
	    'options' => ['width' => '500']
	],
	[
	    'attribute' => 'create_time',
	    'format' => ['date', 'php:Y-m-d H:i:s']
	],
	[
	    'attribute' => 'post_id',
	    'label' => 'aa',
	    'value' => function( $model ) {
		return 'hello world ' .  $model ->post_id;
	    }
	],
	'create_time:datetime',
	[
	    'class' => 'yii\grid\ActionColumn',
	    'header' => '操作',
	    'headerOptions' => ['width' => '100']
	],
    // ...
    ],
])
?>