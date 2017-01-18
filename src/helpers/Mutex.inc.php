<?php
namespace ICE\lib\helpers;

	class Mutex
	{
		private $is_acquired = false;
		private $filename = '';
		private $filepointer;

		function __construct($filename = "")
		{
			if(!empty($filename))
				$this->init($filename);
		}

		public function init($filename)
		{
			$this->filename = $filename;
			return true;
		}

		public function acquire()
		{
			if(($this->filepointer = @fopen($this->filename, "w+")) == false)
			{
				print "error opening mutex file<br>";
				return false;
			}

			if(flock($this->filepointer, LOCK_EX) == false)
			{
				print "error locking mutex file<br>";
				return false;
			}

			$this->is_acquired = true;
			return true;
		}

		public function release()
		{
			if(!$this->is_acquired)
				return true;

			if(flock($this->filepointer, LOCK_UN) == false)
			{
				print "error unlocking mutex file<br>";
				return false;
			}

			fclose($this->filepointer);

			$this->is_acquired = false;
			return true;
		}
	}
