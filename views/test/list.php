<?php  
echo \yii\widgets\ListView::widget([  
    'dataProvider' => $dataProvider,  
    'layout' => '{items}{pager}',
    'itemView' => '_item',//子视图  
]);  
?> 