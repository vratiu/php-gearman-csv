<?php
include 'profiler.php';
/**
 * Process CSV worker
 * Binds to process,
 * Reads chunks from csv file
 * Insert records into db
 */
class Worker
{
	protected $profiler;
	/**
	 * Configuration
	 * @var array
	 */
	protected $config = array();

	/**
	 * File reader
	 * @var SplFileObject
	 */
	protected $fileReader = null;

	/**
	 * Database connection
	 * @var PDO
	 */
	protected $db;

	/**
	 * Geaerman worker handler
	 * @var GearmanWorker
	 */
	protected $worker;

	public function __construct()
	{
		$this->profiler = new Profiler();
	}

	public function connectDb()
	{
		$config = $this->config['db'];
		try{
			$this->db = new PDO("mysql:host=".$config['host'].";dbname=".$config['database'],
								$config['user'],
								$config['password']
								);
		}catch(PDO_Exception $ex){
			//nothing really ro do in this app
			throw $ex;
		}
		return $this;
	}

	public function disconnectDb()
	{
		// if(!is_a($this->db, "PDO")){
		// 	return false;
		// }
//		$this->db->close();
		$this->db = null;
	}

	public function insertBulk($table, $columns, $values)
	{
		$sql = "INSERT INTO `".$table."` (`".implode("`,`", $columns)." )  VALUES ";
		$valuesStr = "";
		$len = count($values);
		for($i = 0; $i < $len; $i++){
			$escaped = array_map(array($this->db, 'quote'), $values[$i]);
			$valuesStr.="(".implode(",", $escaped).")";
		}

		$sql.=$valuesStr;

		$this->db->prepare($sql)->execute();
	}

	public function connectGearman()
	{
		$config = $this->config['gearman'];
		$this->worker =  new GearmanWorker();
		$this->worker->addServer($config['host'], $config['port']);

		return $this;
	}

	public function bind()
	{
		$this->worker->addFunction('process-csv', array($this, "processCSVJob"));
		return $this;
	}

	public function work()
	{
		$this->worker->work();
	}

	public function processCSVJob(GearmanJob $job)
	{
		return $this->processCSV($job->workload());
	}

	public function processCSV($filePath)
	{
		$this->profiler->start();

		$this->initReader($filePath)
				->connectDb();
		$size = $this->config['chunk_size'];
		$headers = array_shift($this->readRows(0,1));
		$offset = 1;
		do{
			$readRows = $this->readRows($offset, $size);
			$this->insertBulk($this->config['table'], $headers, $readRows);
			$this->profiler->printUsage();
			$readCount = count($readRows);
			$offset+=$readCount;
		}while($readCount == $size);

		$this->disconnectDb();

		$this->profiler->stop();
		$this->profiler->printStatistics();
	}

	public function readRows($offset, $count)
	{
		$rows = array();
		$this->fileReader->seek($offset);
		while($count-- && $this->fileReader->eof() == false){
			$row = $this->fileReader->fgetcsv();
			if(!empty($row)){
				$rows[] = $row;
			}else{
				//ignore increment limit
				$count++;
			}
		}

		return $rows;
	}

	public function initReader($csvPath)
	{
		if(file_exists($csvPath) == false){
			throw new Exception("Data file does not exist");
		}

		if(pathinfo($csvPath, PATHINFO_EXTENSION) !==  'csv'){
			throw new Exception("Data file has invalid extension");
		}

		$this->fileReader = new SplFileObject($csvPath);
		return $this;
	}

	public function readConfig($path)
	{
		if(file_exists($path) == false){
			throw new Exception("Configuration file does not exist");
		}
		$this->config = include $path;
		return $this;
	}

}