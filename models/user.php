<?php
class User extends AppModel {
	var $name = 'User';
	var $displayField = 'username';
	var $validate = array(
		'username' => array(
			'notempty' => array(
				'rule' => array('notempty')
			),
		),
		'token' => array(
			'notempty' => array(
				'rule' => array('notempty')
			),
		),
		'email' => array(
			'email' => array(
				'rule' => array('email')
			),
		),
	);

	var $hasMany = array(
		'Log' => array(
			'className' => 'Log',
			'foreignKey' => 'user_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}
?>