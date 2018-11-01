<?php
class Mutex
{
	var $filename = '';
	var $fp;

	function Mutex($filename, $block = false)
	{
		$this->filename = $filename;
		$this->block = $block;
	}

	/**
	 * @return true if successfuly locked
	 * false if was already locked.
	 * error string otherwise
	 */
	function lock()
	{
		if (defined('DISABLE_MUTEX') && DISABLE_MUTEX == true) return true;
		if(($this->fp = @fopen($this->filename, "r")) == false)
		{
			return "error opening mutex file : $this->filename";
		}
		$would_block = false;
		$ret = flock($this->fp, LOCK_EX + ($this->block ? 0 : LOCK_NB), $would_block);
		if ($would_block)
		{
			return false;
		}
		
		if ($ret === false)
		{
			return "Error locking $this->filename";
		}
		
		return true;
	}

	function unlock()
	{
		if (defined('DISABLE_MUTEX') && DISABLE_MUTEX == true) return true;
		
		if(flock($this->fp, LOCK_UN) == false)
		{
			return "error unlocking mutex file $this->filename";
		}

		if ($this->fp)
		{
			fclose($this->fp);
		}
		return true;
	}
}
?>
