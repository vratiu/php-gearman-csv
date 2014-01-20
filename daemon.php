<?php
include __DIR__."/worker.php";
$worker  = new Worker();
$worker->readConfig(__DIR__."/config.php")
		->connectGearman()
		->bind()
		->work();