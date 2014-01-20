<?php
class Profiler
{
	protected $startTime = 0;

	protected $endTime = 0;

	public function printUsage()
	{
		$usage =  memory_get_usage(true);
		echo str_repeat("=", $usage / (pow(1024,2) /10))." ".$usage."\n";
	}

	public function printStatistics()
	{
		echo "Current memory usage: ".memory_get_usage(true)."\n";
		echo "Peak memory usage: ".memory_get_peak_usage(true)."\n";
		echo "Time: ".($this->endTime - $this->startTime)." ms";

	}

	public function start()
	{
		$this->startTime = microtime(true);
	}

	public function stop()
	{
		$this->endTime = microtime(true);
	}


}