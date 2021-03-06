<?php
/* @var $this CasesController */
/* @var $model Cases */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'cases-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'case_summary'); ?>
		<?php echo $form->textField($model,'case_summary',array('size'=>60,'maxlength'=>127)); ?>
		<?php echo $form->error($model,'case_summary'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'case_details'); ?>
		<?php echo $form->textArea($model,'case_details',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'case_details'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'caller_name'); ?>
		<?php echo $form->textField($model,'caller_name',array('size'=>60,'maxlength'=>127)); ?>
		<?php echo $form->error($model,'caller_name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'caller_mobile'); ?>
		<?php echo $form->textField($model,'caller_mobile',array('size'=>60,'maxlength'=>127)); ?>
		<?php echo $form->error($model,'caller_mobile'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'caller_address'); ?>
		<?php echo $form->textField($model,'caller_address',array('size'=>60,'maxlength'=>256)); ?>
		<?php echo $form->error($model,'caller_address'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'voice_path'); ?>
		<?php echo $form->textField($model,'voice_path',array('size'=>60,'maxlength'=>127)); ?>
		<?php echo $form->error($model,'voice_path'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'datetime'); ?>
		<?php echo $form->textField($model,'datetime'); ?>
		<?php echo $form->error($model,'datetime'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'priority'); ?>
		<?php echo $form->textField($model,'priority'); ?>
		<?php echo $form->error($model,'priority'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'created_by'); ?>
		<?php echo $form->textField($model,'created_by'); ?>
		<?php echo $form->error($model,'created_by'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'created_time'); ?>
		<?php echo $form->textField($model,'created_time'); ?>
		<?php echo $form->error($model,'created_time'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'updated_by'); ?>
		<?php echo $form->textField($model,'updated_by'); ?>
		<?php echo $form->error($model,'updated_by'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'updated_time'); ?>
		<?php echo $form->textField($model,'updated_time'); ?>
		<?php echo $form->error($model,'updated_time'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'status'); ?>
		<?php echo $form->textField($model,'status'); ?>
		<?php echo $form->error($model,'status'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->