<?php

namespace ezsql;

use ezsql\ezQuery;
use ezsql\ezsqlModelInterface;
use function ezsql\functions\getVendor;

/**
 * Core class containing common functions to manipulate query result
 * sets once returned
 */
class ezsqlModel extends ezQuery implements ezsqlModelInterface
{
	protected $isSecure = false;
	protected $secureOptions = null;
	protected $sslKey = null;
	protected $sslCert = null;
	protected $sslCa = null;
	protected $sslPath = null;

	/**
	 * If set to true (i.e. $db->debug_all = true;) Then it will print out ALL queries and ALL results of your script.
	 * @var boolean
	 */
	protected $debugAll = false;

	// same as $debug_all
	protected $trace = false;
	protected $debugCalled = false;
	protected $varDumpCalled = false;

	/**
	 * Current show error state
	 * @var boolean
	 */
	protected $showErrors = true;

	/**
	 * Keeps track of exactly how many 'real' (not cached)
	 * queries were executed during the lifetime of the current script
	 * @var int
	 */
	protected $numQueries = 0;

	protected $connQueries = 0;
	protected $capturedErrors = array();

	/**
	 * Specify a cache dir. Path is taken from calling script
	 * @var string
	 */
	protected $cacheDir = 'tmp' . \_DS . 'ez_cache';

	/**
	 * Disk Cache Setup
	 * (1. You must create this dir. first!)
	 * (2. Might need to do chmod 775)
	 *
	 * Global override setting to turn disc caching off (but not on)
	 * @var boolean
	 */
	protected $useDiskCache = false;

	/**
	 * Cache expiry, this is hours
	 * @var int
	 */
	protected $cacheTimeout = 24;

	/**
	 * if you want to cache EVERYTHING just do..
	 *
	 * $use_disk_cache = true;
	 * $cache_queries = true;
	 * $cache_timeout = 24;
	 */

	/**
	 * By wrapping up queries you can ensure that the default
	 * is NOT to cache unless specified
	 * @var boolean
	 */
	protected $cacheQueries = false;
	protected $cacheInserts = false;

	/**
	 * Log number of rows the query returned
	 * @var int Default is null
	 */
	protected $numRows = null;

	protected $dbConnectTime = 0;
	protected $sqlLogFile = false;
	protected $profileTimes = array();

	/**
	 * ID generated from the AUTO_INCREMENT of the previous INSERT operation (if any)
	 * @var int
	 */
	protected $insertId = null;

	/**
	 * Use to keep track of the last query for debug..
	 * @var string
	 */
	protected $lastQuery = null;

	/**
	 * Use to keep track of last error
	 * @var string
	 */
	protected $lastError = null;

	/**
	 * Saved info on the table column
	 * @var mixed
	 */
	protected $colInfo = array();

	protected $timers = array();
	protected $totalQueryTime = 0;
	protected $traceLog = array();
	protected $useTraceLog = false;
	protected $doProfile = false;

	/**
	 * The last query result
	 * @var object Default is null
	 */
	protected $lastResult = null;

	/**
	 * Get data from disk cache
	 * @var boolean Default is false
	 */
	protected $fromDiskCache = false;

	/**
	 *  Needed for echo of debug function
	 * @var boolean Default is false
	 */
	protected $debugEchoIsOn = false;

	/**
	 * Whether the database connection is established, or not
	 * @var boolean Default is false
	 */
	protected $_connected = false;

	/**
	 * Contains the number of affected rows of a query
	 * @var int Default is 0
	 */
	protected $_affectedRows = 0;

	/**
	 * Function called
	 * @var string
	 */
	private $funcCall;

	/**
	 * All functions called
	 * @var array
	 */
	private $allFuncCalls = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Magic methods for Calling Non-Existent Functions, handling Getters and Setters.
	 * @method set/get{property} - a property that needs to be accessed
	 *
	 * @method void setDebugAll($args);
	 * @method void setTrace($args);
	 * @method void setDebugCalled($args);
	 * @method void setVarDumpCalled($args);
	 * @method void setShowErrors($args);
	 * @method void setNumQueries($args);
	 * @method void setConnQueries($args);
	 * @method void setCapturedErrors($args);
	 * @method void setCacheDir($args);
	 * @method void setUseDiskCache($args);
	 * @method void setCacheTimeout($args);
	 * @method void setCacheQueries($args);
	 * @method void setCacheInserts($args);
	 * @method void setNumRows($args);
	 * @method void setDbConnectTime($args);
	 * @method void setSqlLogFile($args);
	 * @method void setProfileTimes($args);
	 * @method void setInsertId($args);
	 * @method void setLastQuery($args);
	 * @method void setLastError($args);
	 * @method void setColInfo($args);
	 * @method void setTimers($args);
	 * @method void setTotalQueryTime($args);
	 * @method void setTraceLog($args);
	 * @method void setUseTraceLog($args);
	 * @method void setDoProfile($args);
	 * @method void setLastResult($args);
	 * @method void setFromDiskCache($args);
	 * @method void setDebugEchoIsOn($args);
	 * @method void setFuncCall($args);
	 * @method void setAllFuncCalls($args);
	 *
	 * @method string getDebugAll();
	 * @method string getTrace();
	 * @method string getDebugCalled();
	 * @method string getVarDumpCalled();
	 * @method string getShowErrors();
	 * @method string getNumQueries();
	 * @method string getConnQueries();
	 * @method string getCapturedErrors();
	 * @method string getCacheDir();
	 * @method string getUseDiskCache();
	 * @method string getCacheTimeout();
	 * @method string getCacheQueries();
	 * @method string getCacheInserts();
	 * @method string getNumRows();
	 * @method string getDbConnectTime();
	 * @method string getSqlLogFile();
	 * @method string getProfileTimes();
	 * @method string getInsertId();
	 * @method string getLastQuery();
	 * @method string getLastError();
	 * @method string getColInfo();
	 * @method string getTimers();
	 * @method string getTotalQueryTime();
	 * @method string getTraceLog();
	 * @method string getUseTraceLog();
	 * @method string getDoProfile();
	 * @method string getLastResult();
	 * @method string getFromDiskCache();
	 * @method string getDebugEchoIsOn();
	 * @method string getFuncCall();
	 * @method string getAllFuncCalls();
	 *
	 * @property-read function
	 * @property-write args
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call($function, $args)
	{
		$prefix = \substr($function, 0, 3);
		$property = \lcfirst(\substr($function, 3, \strlen($function)));
		// Todo: make properties PSR-1, add following for backward compatibility
		if (\strpos($property, '_') !== false)
			$property = \str_replace('_', '', $property);

		if (($prefix == 'set') && \property_exists($this, $property)) {
			$this->$property = $args[0];
		} elseif (($prefix == 'get') && \property_exists($this, $property)) {
			return $this->$property;
		} else {
			throw new \Exception("$function does not exist");
		}
	}

	public function get_host_port(string $host, bool $default = false)
	{
		$port = $default;
		if (false !== \strpos($host, ':')) {
			list($host, $port) = \explode(':', $host);
			$port = (int) $port;
		}
		return array($host, $port);
	}

	public function register_error(string $err_str, bool $displayError = true)
	{
		// Keep track of last error
		$this->lastError = $err_str;

		// Capture all errors to an error array no matter what happens
		$this->capturedErrors[] = array(
			'error_str' => $err_str,
			'query'     => $this->lastQuery
		);

		if ($this->showErrors && $displayError)
			\trigger_error(\htmlentities($err_str), \E_USER_WARNING);

		return false;
	}

	public function show_errors()
	{
		$this->showErrors = true;
	}

	public function hide_errors()
	{
		$this->showErrors = false;
	}

	/**
	 * Turn on echoing of debug info, for `debug()`
	 */
	public function debugOn()
	{
		$this->debugEchoIsOn = true;
	}

	/**
	 * Turn off echoing of debug info, the default, for `debug()`
	 */
	public function debugOff()
	{
		$this->debugEchoIsOn = false;
	}

	public function flush()
	{
		// Get rid of these
		$this->lastResult = null;
		$this->colInfo = array();
		$this->lastQuery = null;
		$this->allFuncCalls = array();
		$this->fromDiskCache = false;
		$this->clearPrepare();
	}

	public function log_query(string $query)
	{
		// Log how the last function was called
		$this->funcCall = $query;

		// Keep an running Log of all functions called
		\array_push($this->allFuncCalls, $this->funcCall);
	}

	public function get_var(string $query = null, int $x = 0, int $y = 0, bool $use_prepare = false)
	{
		// Log how the function was called
		$this->log_query("\$db->get_var(\"$query\",$x,$y)");

		// If there is a query then perform it if not then use cached results..
		if ($query) {
			$this->query($query, $use_prepare);
		}

		// Extract public out of cached results based x,y values
		if (isset($this->lastResult[$y])) {
			$values = \array_values(\get_object_vars($this->lastResult[$y]));
		}

		// If there is a value return it else return null
		return (isset($values[$x]) && $values[$x] !== null) ? $values[$x] : null;
	}

	public function get_row(string $query = null, $output = \OBJECT, int $y = 0, bool $use_prepare = false)
	{
		// Log how the function was called
		$this->log_query("\$db->get_row(\"$query\",$output,$y)");

		// If there is a query then perform it if not then use cached results..
		if ($query) {
			$this->query($query, $use_prepare);
		}

		if ($output == OBJECT) {
			// If the output is an object then return object using the row offset..
			return isset($this->lastResult[$y]) ? $this->lastResult[$y] : null;
		} elseif ($output == \ARRAY_A) {
			// If the output is an associative array then return row as such..
			return isset($this->lastResult[$y]) ? \get_object_vars($this->lastResult[$y]) : null;
		} elseif ($output == \ARRAY_N) {
			// If the output is an numerical array then return row as such..
			return isset($this->lastResult[$y]) ? \array_values(\get_object_vars($this->lastResult[$y])) : null;
		} else {
			// If invalid output type was specified..
			$this->showErrors ? \trigger_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N", \E_USER_WARNING) : null;
		}
	}

	public function get_col(string $query = null, int $x = 0, bool $use_prepare = false)
	{
		$new_array = array();

		// If there is a query then perform it if not then use cached results..
		if ($query) {
			$this->query($query, $use_prepare);
		}

		// Extract the column values
		if (\is_array($this->lastResult)) {
			$j = \count($this->lastResult);
			for ($i = 0; $i < $j; $i++) {
				$new_array[$i] = $this->get_var(null, $x, $i, $use_prepare);
			}
		}

		return $new_array;
	}

	public function get_results(string $query = null, $output = \OBJECT, bool $use_prepare = false)
	{
		// Log how the function was called
		$this->log_query("\$db->get_results(\"$query\", $output, $use_prepare)");

		// If there is a query then perform it if not then use cached results..
		if ($query) {
			$this->query($query, $use_prepare);
		}

		if ($output == OBJECT) {
			return $this->lastResult;
		} elseif ($output == \_JSON) {
			return \json_encode($this->lastResult); // return as json output
		} elseif ($output == \ARRAY_A || $output == \ARRAY_N) {
			$new_array = [];
			if ($this->lastResult) {
				$i = 0;
				foreach ($this->lastResult as $row) {
					$new_array[$i] = \get_object_vars($row);
					if ($output == \ARRAY_N) {
						$new_array[$i] = \array_values($new_array[$i]);
					}
					$i++;
				}
			}
			return $new_array;
		}
	}

	public function get_col_info(string $info_type = "name", int $col_offset = -1)
	{
		if ($this->colInfo) {
			$new_array = [];
			if ($col_offset == -1) {
				$i = 0;
				foreach ($this->colInfo as $col) {
					$new_array[$i] = $col->{$info_type};
					$i++;
				}

				return $new_array;
			}

			return $this->colInfo[$col_offset]->{$info_type};
		}
	}

	public function create_cache(string $path = null)
	{
		$cache_dir = empty($path) ? $this->cacheDir : $path;
		if (!\is_dir($cache_dir)) {
			$this->cacheDir = $cache_dir;
			@\mkdir($cache_dir, ('\\' == \DIRECTORY_SEPARATOR ? null : 0755), true);
		}
	}

	public function store_cache(string $query, bool $is_insert = false)
	{
		// The would be cache file for this query
		$cache_file = $this->cacheDir . \_DS . \md5($query);

		// disk caching of queries
		if (
			$this->useDiskCache
			&& ($this->cacheQueries && !$is_insert) || ($this->cacheInserts && $is_insert)
		) {
			$this->create_cache();
			if (!\is_dir($this->cacheDir)) {
				return $this->register_error("Could not open cache dir: $this->cacheDir");
			} else {
				// Cache all result values
				$result_cache = array(
					'col_info' => $this->colInfo,
					'last_result' => $this->lastResult,
					'num_rows' => $this->numRows,
					'return_value' => $this->numRows,
				);

				\file_put_contents($cache_file, \serialize($result_cache));
				if (\file_exists($cache_file . ".updating"))
					\unlink($cache_file . ".updating");
			}
		}
	}

	public function get_cache(string $query)
	{
		// The would be cache file for this query
		$cache_file = $this->cacheDir . \_DS . \md5($query);

		// Try to get previously cached version
		if ($this->useDiskCache && \file_exists($cache_file)) {
			// Only use this cache file if less than 'cache_timeout' (hours)
			if ((\time() - \filemtime($cache_file)) > ($this->cacheTimeout * 3600)
				&& !(\file_exists($cache_file . ".updating")
					&& (\time() - \filemtime($cache_file . ".updating") < 60))
			) {
				\touch($cache_file . ".updating"); // Show that we in the process of updating the cache
			} else {
				$result_cache = \unserialize(\file_get_contents($cache_file));

				$this->colInfo = $result_cache['col_info'];
				$this->lastResult = $result_cache['last_result'];
				$this->numRows = $result_cache['num_rows'];

				$this->fromDiskCache = true;

				// If debug ALL queries
				$this->trace || $this->debugAll ? $this->debug() : null;

				return $result_cache['return_value'];
			}
		}
	}

	public function varDump($mixed = null)
	{
		// Start output buffering
		\ob_start();

		echo "<p><table><tr><td bgcolor=ffffff><blockquote><font color=000090>";
		echo "<pre><font face=arial>";

		if (!$this->varDumpCalled) {
			echo "<font color=800080><b>ezSQL</b> (v" . \EZSQL_VERSION . ") <b>Variable Dump..</b></font>\n\n";
		}

		$var_type = \gettype($mixed);
		\print_r(($mixed ? $mixed : "<font color=red>No Value / False</font>"));
		echo "\n\n<b>Type:</b> " . \ucfirst($var_type) . "\n";
		echo "<b>Last Query</b> [$this->numQueries]<b>:</b> " . ($this->lastQuery ? $this->lastQuery : "NULL") . "\n";
		echo "<b>Last Function Call:</b> " . ($this->funcCall ? $this->funcCall : "None") . "\n";

		if (\count($this->allFuncCalls) > 1) {
			echo "<b>List of All Function Calls:</b><br>";
			foreach ($this->allFuncCalls as $func_string)
				echo "  " . $func_string . "<br>\n";
		}

		echo "<b>Last Rows Returned:</b><br>";
		echo ((!empty($this->lastResult) && \count($this->lastResult) > 0)  ? print_r($this->lastResult[0]) : 'No rows returned') . "\n";
		echo "</font></pre></font></blockquote></td></tr></table>"; //.$this->donation();
		echo "\n<hr size=1 noshade color=dddddd>";

		// Stop output buffering and capture debug HTML
		$html = \ob_get_contents();
		\ob_end_clean();

		// Only echo output if it is turned on
		if ($this->debugEchoIsOn) {
			echo $html;
		}

		$this->varDumpCalled = true;

		return $html;
	}

	/**
	 * @internal ezsqlModel::varDump
	 */
	public function dump_var($mixed = null)
	{
		return $this->varDump($mixed);
	}

	public function debug($print_to_screen = true)
	{
		// Start output buffering
		\ob_start();

		echo "\n\n<blockquote>";

		// Only show ezSQL credits once..
		if (!$this->debugCalled) {
			echo "<font color=800080 face=arial size=2><b>ezSQL</b> (v" . \EZSQL_VERSION . ")\n <b>Debug.. \n</b></font><p>";
		}

		if ($this->lastError) {
			echo "<font face=arial size=2 color=000099><b>Last Error --</b> [<font color=000000><b>$this->lastError \n</b></font>]<p>";
		}

		if ($this->fromDiskCache) {
			echo "<font face=arial size=2 color=000099><b>Results retrieved from disk cache</b></font><p>\n";
		}

		echo "<font face=arial size=2 color=000099><b>Query</b> [$this->numQueries]  \n<b>--</b>";
		echo "[<font color=000000><b>$this->lastQuery \n</b></font>]</font><p>";

		echo "<font face=arial size=2 color=000099><b>Query Result..</b></font>\n";
		echo "<blockquote>\n";

		if ($this->colInfo) {
			// Results top rows
			echo "<table cellpadding=5 cellspacing=1 bgcolor=555555>\n";
			echo "<tr bgcolor=eeeeee><td nowrap valign=bottom><font color=555599 face=arial size=2><b>(row)</b></font></td>\n";

			for ($i = 0, $j = count($this->colInfo); $i < $j; $i++) {
				echo "<td nowrap align=left valign=top><font size=1 color=555599 face=arial>\n";
				/* when `select` count(*) the maxlengh is not set, size is set instead. */
				if (isset($this->colInfo[$i]->type))
					echo "{$this->colInfo[$i]->type}";

				if (isset($this->colInfo[$i]->size))
					echo "{$this->colInfo[$i]->size}";

				if (isset($this->colInfo[$i]->max_length))
					echo "{$this->colInfo[$i]->max_length}";

				echo "\n</font><br><span style='font-family: arial; font-size: 10pt; font-weight: bold;'>";

				if (isset($this->colInfo[$i]->name))
					echo "{$this->colInfo[$i]->name}";

				echo "\n</span></td>";
			}
			echo "</tr>\n";

			// print main results
			if ($this->lastResult) {
				$i = 0;
				foreach ($this->get_results(null, \ARRAY_N) as $one_row) {
					$i++;
					echo "<tr bgcolor=ffffff><td bgcolor=eeeeee nowrap align=middle><font size=2 color=555599 face=arial>$i \n</font></td>";

					foreach ($one_row as $item) {
						echo "<td nowrap><font face=arial size=2>$item \n</font></td>";
					}
					echo "</tr>\n";
				}
			} else {
				// if last result
				echo "<tr bgcolor=ffffff><td colspan=" . (\count($this->colInfo) + 1) . "><font face=arial size=2>No Results</font></td></tr>\n";
			}

			echo "</table>\n";
		} else {
			// if col_info
			echo "<font face=arial size=2>No Results \n</font>";
		}

		//echo "</blockquote></blockquote>".$this->donation()."<hr noshade color=dddddd size=1>";

		// Stop output buffering and capture debug HTML
		$html = \ob_get_contents();
		\ob_end_clean();

		// Only echo output if it is turned on
		if ($this->debugEchoIsOn && $print_to_screen) {
			echo $html;
		}

		$this->debugCalled = true;

		return $html;
	}

	public function timer_get_cur()
	{
		list($usec, $sec) = \explode(" ", \microtime());
		return ((float) $usec + (float) $sec);
	}

	public function timer_start($timer_name)
	{
		$this->timers[$timer_name] = $this->timer_get_cur();
	}

	public function timer_elapsed($timer_name)
	{
		return \round($this->timer_get_cur() - $this->timers[$timer_name], 2);
	}

	public function timer_update_global($timer_name)
	{
		if ($this->doProfile) {
			$this->profileTimes[] = array(
				'query' => $this->lastQuery,
				'time' => $this->timer_elapsed($timer_name)
			);
		}
		$this->totalQueryTime += $this->timer_elapsed($timer_name);
	}

	public function count($all = true, $increase = false)
	{
		if ($increase) {
			$this->numQueries++;
			$this->connQueries++;
		}

		return ($all) ? $this->numQueries : $this->connQueries;
	}

	public function secureSetup(
		string $key = 'certificate.key',
		string $cert = 'certificate.crt',
		string $ca = 'cacert.pem',
		string $path = '.' . \_DS
	) {
		if (!\file_exists($path . $cert) || !\file_exists($path . $key)) {
			$vendor = getVendor();
			if (($vendor != \SQLITE) || ($vendor != \MSSQL))
				$path = ezQuery::createCertificate();
		} elseif ($path == '.' . \_DS) {
			$ssl_path = \getcwd();
			$path = \preg_replace('/\\\/', \_DS, $ssl_path) . \_DS;
		}

		$this->isSecure = true;
		$this->sslKey = $key;
		$this->sslCert = $cert;
		$this->sslCa = $ca;
		$this->sslPath = $path;
	}

	public function secureReset()
	{
		$this->isSecure = false;
		$this->sslKey = null;
		$this->sslCert = null;
		$this->sslCa = null;
		$this->sslPath = null;
		$this->secureOptions = null;
	}

	/**
	 * Returns `true` if the database connection is established.
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->_connected;
	} // isConnected

	/**
	 * Returns the `number` of affected rows of a query.
	 *
	 * @return int
	 */
	public function affectedRows()
	{
		return $this->_affectedRows;
	} // affectedRows

	/**
	 * Returns the last query `result`.
	 *
	 * @return object
	 */
	public function queryResult()
	{
		return $this->lastResult;
	}
} // ezsqlModel
