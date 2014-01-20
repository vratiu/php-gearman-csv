<?php
return array(
	'db'=> array(
			'host' => 'animotodbinstance.cqofokujvgcy.us-east-1.rds.amazonaws.com',
			'user' => 'animoto',
			'password' => 'Anim0toDB',
			'database' => 'virgil_stage'
		),
	'gearman' => array(
			'host' => '127.0.0.1',
			'port' => 4730
		),
	'table' => 'airports',
	'chunk_size' => 100
);