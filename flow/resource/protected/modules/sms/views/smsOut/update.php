<?php
$this->layout = "//layouts/column2_sms";
$this->renderPartial('//sms/default/_menu', array('active' => 'sms'));
?>

<h3 class="ui header dividing" style="margin-top: 0">Update Sms</h3>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>