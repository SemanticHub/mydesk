<?php
/* @var $this CasesController */
/* @var $model Cases */

$this->breadcrumbs=array(
	'Cases'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List Cases', 'url'=>array('index')),
	array('label'=>'Create Cases', 'url'=>array('create')),
	array('label'=>'Update Cases', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete Cases', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Cases', 'url'=>array('admin')),
);
?>

<h1>View Cases #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'case_summary',
		'case_details',
		'caller_name',
		'caller_mobile',
		'caller_address',
                'victim_name',
                'victim_mobile',
                'is_caller_victim',
                'victim_current_condition',
		'voice_path',
		'datetime',
		'priority',
		'created_by',
		'created_time',
		'updated_by',
		'updated_time',
		'status',
	),
)); ?>
