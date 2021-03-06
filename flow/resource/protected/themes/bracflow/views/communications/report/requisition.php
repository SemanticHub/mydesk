<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/custom/javascript/jq-datetime-picker/dist/smoothness/jquery-ui-1.10.4.custom.min.css">
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/custom/javascript/jq-datetime-picker/dist/jquery-ui-timepicker-addon.min.css">
<link rel="stylesheet" media="print" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/css/comm_print.css" />
<style type="text/css">
    .ui.selection, .ui.selection .menu {
        padding: .4em .5em; border: 1px solid #aaa; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05) !important; background: #fff;
    }
    .ui-datepicker {
        font-size: 13px;
    }
    .required {
        color: #ff007b; font-weight: bold; font-size: 2em; line-height: 13px; vertical-align: text-bottom;
    }
    .ui-timepicker-div {
        display: none
    }
</style>
<?php $this->layout = "//layouts/column2_com"; ?>
<h3 class="ui header dividing" style="margin-top: 0">Requisition Report</h3>
<?php $this->renderPartial('//communications/default/_menu', array('active' => 'form')); ?>

<?php
$form = $this->beginWidget('CActiveForm', array(
    'id' => 'requisition-report-form',
    'htmlOptions' => array('class' => 'ui form')
        ));
?>
<div class="ui fluid form segment" style="background: rgba(0, 0, 0 , 0.1); margin-top: 0">
    <div class="ui grid com-form  middle aligned">        
        <!--        <div class="five wide column" style="margin: 0">
                    <div class="field">
        <?php //echo CHtml::label('Billing Department'); ?>
                        <div class="ui mini input">   
        <?php //echo CHtml::dropDownList('billingDept', '', $billingDept, array('class' => 'ui mini selection ', 'empty' => 'Select a Department')) ?>                   
                        </div>
                    </div>            
                </div>        -->
        <div class="four wide column" style="margin: 0">
            <div class="field">
                <?php echo CHtml::label('from date'); ?>
                <div class="ui mini input">
                    <?php echo CHtml::textField('fromdate', $fromdate, array('class' => 'fromdate')); ?>
                </div>
            </div>  
        </div>
        <div class="four wide column" style="margin: 0">
            <div class="field">
                <?php echo CHtml::label('to date'); ?>
                <div class="ui mini input">
                    <?php echo CHtml::textField('todate', $todate, array('class' => 'todate')); ?>
                </div>
            </div>            
        </div> 
        <div class="three wide column"  style="margin: 0px;">
            <div class="row buttons" style="text-align: right;">
                <div class="ui right small submit teal labeled icon button" id="reportButton">
                    <i class="right arrow icon"></i>
                    Send
                </div>
            </div>
        </div>
    </div>    
</div>
<?php $this->endWidget(); ?>

<div id="report-view-print" style="margin-top: 25px">
    <?php if (TeamMembers::isServiceTeamMember('photography')) { ?>
        <h3 class="ui  dividing  header" style="margin-top: 0" id='headerTop'>Photography Service</h3>
        <?php
        $this->widget('zii.widgets.grid.CGridView', array(
            'dataProvider' => $dataPhotography,
            'itemsCssClass' => 'ui basic table segment',
            'columns' => array(
                'id',
                array(
                    'name' => 'item',
                    'type' => 'raw',
                    'value' => 'Settings::model()->findByPk($data->item)->item',
                ),
                'days',
                'location',
                array(
                    'name' => 'fromdate',
                    'value' => 'Yii::app()->dateFormatter->format("d MMM, y",strtotime($data->fromdate))'
                ),
                array(
                    'name' => 'todate',
                    'value' => 'Yii::app()->dateFormatter->format("d MMM, y",strtotime($data->todate))'
                ),
                array(
                    'header' => 'Bill Dept.',
                    'name' => 'bill_dept',
                ),
                array(
                    'header' => 'Requester',
                    'name' => 'user_id',
                    'value' => 'User::model()->findByPk($data->user_id)->username'
                ),
                array(
                    'header' => 'Supervisor',
                    'name' => 'supervisor_id',
                    'value' => 'User::model()->findByPk($data->supervisor_id)->username'
                ),
                array(
                    'name' => 'est_total',
                    'value' => '"BDT-".$data->est_total',
                    'htmlOptions' => array('style' => 'font-weight:bold;')
                ),
            ),
        ));
        ?>
        <hr />
    <?php } ?>
    <?php if (TeamMembers::isServiceTeamMember('design')) { ?>
        <br />
        <h3 class="ui  dividing  header" style="margin-top: 0" id='headerTop'>Design Service</h3>
        <?php
        $this->widget('zii.widgets.grid.CGridView', array(
            'dataProvider' => $dataDesign,
            'itemsCssClass' => 'ui basic table segment',
            'columns' => array(
                'id',
                array(
                    'name' => 'item_id',
                    'type' => 'raw',
                    'value' => 'Settings::model()->findByPk($data->item_id)->item',
                ),
                'size',
                'color',
                'qty',
                array(
                    'header' => 'Est. Delivery',
                    'name' => 'est_delivery_date',
                    'value' => 'Yii::app()->dateFormatter->format("d MMM, y",strtotime($data->est_delivery_date))'
                ),
                //'brief',
                //bill_dept,
                array(
                    'header' => 'Bill Dept.',
                    'name' => 'bill_dept',
                //'value' => 'Yii::app()->dateFormatter->format("d MMM, y",strtotime($data->bill_dept))'
                ),
                array(
                    'header' => 'Requester',
                    'name' => 'user_id',
                    'value' => 'User::model()->findByPk($data->user_id)->username'
                ),
                array(
                    'header' => 'Supervisor',
                    'name' => 'supervisor_id',
                    'value' => 'User::model()->findByPk($data->supervisor_id)->username'
                ),
                array(
                    'name' => 'est_total',
                    'value' => '"BDT-".$data->est_total',
                    'htmlOptions' => array('style' => 'font-weight:bold;')
                ),
            ),
        ));
        ?>
        <hr />
    <?php } ?>

    <?php if (TeamMembers::isServiceTeamMember('audiovisual')) { ?>
        <br />
        <h3 class="ui  dividing  header" style="margin-top: 0" id='headerTop'>Audiovisual Service</h3>
        <?php
        $this->widget('zii.widgets.grid.CGridView', array(
            'dataProvider' => $dataAudiovisual,
            'itemsCssClass' => 'ui basic table segment',
            'columns' => array(
                'id',
                array(
                    'name' => 'item_id',
                    'type' => 'raw',
                    'value' => 'Settings::model()->findByPk($data->item_id)->item',
                ),
                'duration',
                array(
                    'name' => 'est_delivery_date',
                    'value' => 'Yii::app()->dateFormatter->format("d MMM, y",strtotime($data->est_delivery_date))'
                ),
                //'brief',
                array(
                    'header' => 'Bill Dept.',
                    'name' => 'bill_dept',
                //'value' => 'Yii::app()->dateFormatter->format("d MMM, y",strtotime($data->bill_dept))'
                ),
                array(
                    'header' => 'Requester',
                    'name' => 'user_id',
                    'value' => 'User::model()->findByPk($data->user_id)->username'
                ),
                array(
                    'header' => 'Supervisor',
                    'name' => 'supervisor_id',
                    'value' => 'User::model()->findByPk($data->supervisor_id)->username'
                ),
                array(
                    'name' => 'est_total',
                    'value' => '"BDT-".$data->est_total',
                    'htmlOptions' => array('style' => 'font-weight:bold;')
                ),
            ),
        ));
        ?>
        <hr />
    <?php } ?>

    <?php if (TeamMembers::isServiceTeamMember('printing')) { ?>
        <br />
        <h3 class="ui  dividing  header" style="margin-top: 0" id='headerTop'>Printing Service</h3>
        <?php
        $this->widget('zii.widgets.grid.CGridView', array(
            'dataProvider' => $dataPrinting,
            'itemsCssClass' => 'ui basic table segment',
            'columns' => array(
                'id',
                'item_id',
                array(
                    'name' => 'design_id',
                    'type' => 'raw',
                    'value' => 'Settings::model()->findByPk($data->design_id)->type',
                ),
                'qty',
                //'brief',
                array(
                    'header' => 'Bill Dept.',
                    'name' => 'bill_dept',
                ),
                array(
                    'header' => 'Requester',
                    'name' => 'user_id',
                    'value' => 'User::model()->findByPk($data->user_id)->username'
                ),
                array(
                    'header' => 'Supervisor',
                    'name' => 'supervisor_id',
                    'value' => 'User::model()->findByPk($data->supervisor_id)->username'
                ),
                array(
                    'name' => 'est_total',
                    'value' => '"BDT-".$data->est_total',
                    'htmlOptions' => array('style' => 'font-weight:bold;')
                ),
            ),
        ));
        ?>
        <hr />
    <?php } ?>
</div>

<script src="<?php echo Yii::app()->theme->baseUrl; ?>/custom/javascript/jq-datetime-picker/dist/jquery-ui-1.10.4.custom.min.js"></script>
<script src="<?php echo Yii::app()->theme->baseUrl; ?>/custom/javascript/jq-datetime-picker/dist/jquery-ui-timepicker-addon.min.js"></script>

<script type="text/javascript">
    $().ready(function () {
        $('#fromdate, #todate').datetimepicker({
            dateFormat: 'yy-mm-dd',
            showTime: false
        });
        $('#reportButton').click(function () {
            $('#myDiv').toggleClass('show');
        });
    });
</script>

