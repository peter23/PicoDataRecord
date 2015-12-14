<?php

	require('PicoDataRecord.php');

	$rec = new PicoDataRecord(array(
		'name' => array(
			'type' => 'text',
			'filter' => array('required'),
		),
		'test' => array(
			'type' => 'text',
			'encode' => 'html',
			'filter' => array('trim', 'required'),
		),
		'group' => array(
			'type' => 'list',
			'filter' => array('required'),
			'values' => array(
				1 => 'group1',
				2 => 'gro"up2',
				3 => 'group3',
			)
		),
		'somelist' => array(
			'type' => 'list',
			'encode' => 'html',
			'values' => array(
				1 => 'group1',
				2 => 'gro"up2',
				3 => 'group3',
			)
		),
		'date' => array(
			'type' => 'date',
			'encode' => 'html',
			'filter' => array('required'),
			'format' => 'd.m.y',
		),
		'datetime' => array(
			'type' => 'datetime',
			'encode' => 'html',
			'filter' => array('required'),
			'format' => 'd.m.y H-i-s',
		),
		'number' => array(
			'type' => 'number',
			'encode' => 'html',
			'filter' => array('required'),
			'format' => '2|,|`',
		),
	));

	var_dump($rec->load(array(
		'test' => '  123"456',
		'group' => 2,
		'date' => '25 May',
		'datetime' => '2015-01-01 01:01:01',
		'number' => 1234567,
	)));

	var_dump($rec->name);

	var_dump($rec->test);

	var_dump($rec->group);

	var_dump($rec->date);

	var_dump($rec->datetime);

	var_dump($rec->number);

	var_dump($rec->allValues('somelist'));
