<?php
class Log extends AppModel {
	var $name = 'Log';
	var $displayField = 'created';
	var $validate = array(
		'user_id' => array(
			'numeric' => array(
				'rule' => array('numeric')
			),
		),
		'ip' => array(
			'notempty' => array(
				'rule' => array('notempty')
			),
		),
		'activity_id' => array(
			'numeric' => array(
				'rule' => array('numeric')
			),
		),
	);

	var $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Activity' => array(
			'className' => 'Activity',
			'foreignKey' => 'activity_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>