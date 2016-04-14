<?php
use yii\helpers\Html;
?>
<div class="post">
    <strong><?= Html::encode($model->id) ?></strong>
    <a href="test/index/<?= $model->id ?>"><?= Html::encode($model->title) ?> </a>   
</div>

