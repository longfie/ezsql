<?php
/**
 * Author:  Lawrence Stubbs <technoexpressnet@gmail.com>
 *
 * Important: Verify that every feature you use will work with your database vendor.
 * ezSQL Query Builder will attempt to validate the generated SQL according to standards.
 * Any errors will return an boolean false, and you will be responsible for handling.
 *
 * ezQuery does no validation whatsoever if certain features even work with the
 * underlying database vendor. 
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */
namespace ezsql;

class ezQuery
{ 		
	protected $select_result = true;
	protected $prepareActive = false;
    
	private $fromtable = null;
    private $iswhere = true;    
    private $isinto = false;
    
    public function __construct() 
    {
    }
        
    public function clean($string) 
    {
        $patterns = array( // strip out:
                '@<script[^>]*?>.*?</script>@si', // Strip out javascript
                '@<[\/\!]*?[^<>]*?>@si',          // HTML tags
                '@<style[^>]*?>.*?</style>@siU',  // Strip style tags properly
                '@<![\s\S]*?--[ \t\n\r]*>@'       // Strip multi-line comments
                );
                
        $string = preg_replace($patterns,'',$string);
        $string = trim($string);
        $string = stripslashes($string);
        
        return htmlentities($string);
    }
    
    /*
    * Return status of prepare function availability in method calls
    */
    public function getPrepare() 
    {
        return $this->prepareActive;
	}
  	
    /*
    * Turn off/on prepare function availability in ezQuery method calls 
    */
    public function setPrepare($on = true) 
    {
        $this->prepareActive = ($on) ? true : false;
		return null;
	}  	
    
    /**
     * Returns array of parameter values for prepare function 
     */
    public function getParameters() 
    {
		return $this->preparedValues;
	}
    
    /**
    * Add parameter values to class array variable for prepare function
    * @param @valuetoadd mixed
    *
    * @return int /array count
    */
    public function setParameters($valueToAdd = null) 
    {
        return array_push($this->preparedValues, $valueToAdd); 
    }
    
    /**
    * Clear parameter values
    */
    public function clearParameters() 
    {
        $this->preparedValues = array();
	}
    
    public function to_string($arrays)  
    {        
        if (is_array( $arrays )) {
            $columns = '';
            foreach($arrays as $val) {
                $columns .= $val.', ';
            }
            $columns = rtrim($columns, ', ');            
        } else
            $columns = $arrays;
        return $columns;
    }
            
    /**
    * desc: specifies a grouping over the results of the query.
    * <code>
    *     $this->selecting('table', 
    *                   columns,
    *                   where(columns  =  values),
    *                   groupBy(columns),
    *                   having(columns  =  values),
    *                   orderBy(order);
    * </code>
    * param: mixed @groupBy The grouping expression.  
	*
    * returns: string - GROUP BY SQL statement, or false on error
    */
    public function groupBy($groupBy)
    {
        if (empty($groupBy)) {
            return false;
        }
        
        $columns = $this->to_string($groupBy);
        
        return 'GROUP BY ' .$columns;
    }

    /**
    * Specifies a restriction over the groups of the query. 
	* format: having( array(x, =, y, and, extra) ) or having( "x  =  y  and  extra" );
	* example: having( array(key, operator, value, combine, extra) ); or having( "key operator value combine extra" );
    * @param mixed @array or @string double spaced "(
    *   key, - table column  
    *   operator, - set the operator condition, either '<','>', '=', '!=', '>=', '<=', '<>', 'in', 'like', 'between', 'not between', 'is null', 'is not null'
	*   value, - will be escaped
    *   combine, - combine additional where clauses with, either 'AND','OR', 'NOT', 'AND NOT' or  carry over of @value in the case the @operator is 'between' or 'not between'
	*   extra - carry over of @combine in the case the operator is 'between' or 'not between')"
    * @return bool/string - HAVING SQL statement, or false on error
    */
    public function having(...$having)
    {
        $this->iswhere = false;
        return $this->where( ...$having);
    }
 
    /**
    * Specifies an ordering for the query results.  
    * @param  @order The ordering direction. 
    * @return string - ORDER BY SQL statement, or false on error
    */
    public function orderBy($orderBy, $order)
    {
        if (empty($orderBy)) {
            return false;
        }
        
        $columns = $this->to_string($orderBy);
        
        $order = (in_array(strtoupper($order), array( 'ASC', 'DESC'))) ? strtoupper($order) : 'ASC';
        
        return 'ORDER BY '.$columns.' '. $order;
    }
   
 	/**
    * desc: helper returns an WHERE sql clause string 
	* format: where( array(x, =, y, and, extra) ) or where( "x  =  y  and  extra" );
	* example: where( array(key, operator, value, combine, extra) ); or where( "key operator value combine extra" );
    * @param: mixed @array
    *   key, - table column  
    *   operator, - set the operator condition, either '<','>', '=', '!=', '>=', '<=', '<>', 'in', 'like', 
    *                       'not like', 'between', 'not between', 'is null', 'is not null'
	*   value, - will be escaped
    *   combine, - combine additional where clauses with, either 'AND','OR', 'NOT', 
    *                       'AND NOT' or carry over of @value in the case the @operator is 'between' or 'not between'
	*   extra - carry over of @combine in the case the operator is 'between' or 'not between')"
	* @return mixed bool/string - WHERE SQL statement, or false on error
	*/        
    public function where( ...$getWhereKeys) 
    {      
        $whereorhaving = ($this->iswhere) ? 'WHERE' : 'HAVING';
        $this->iswhere = true;
        
		if (!empty($getWhereKeys)) {
			if (is_string($getWhereKeys[0])) {
                if ((strpos($getWhereKeys[0], 'WHERE') !== false) || (strpos($getWhereKeys[0], 'HAVING') !== false))
                    return $getWhereKeys[0];
				foreach ($getWhereKeys as $makearray) 
					$WhereKeys[] = explode('  ', $makearray);	
			} else 
				$WhereKeys = $getWhereKeys;			
		} else 
			return '';
		
		foreach ($WhereKeys as $values) {
			$operator[] = (isset($values[1])) ? $values[1]: '';
			if (!empty($values[1])){
				if (strtoupper($values[1]) == 'IN') {
					$WhereKey[ $values[0] ] = array_slice((array) $values,2);
					$combiner[] = (isset($values[3])) ? $values[3]: _AND;
					$extra[] = (isset($values[4])) ? $values[4]: null;				
				} else {
					$WhereKey[ (isset($values[0])) ? $values[0] : '1' ] = (isset($values[2])) ? $values[2] : '' ;
					$combiner[] = (isset($values[3])) ? $values[3]: _AND;
					$extra[] = (isset($values[4])) ? $values[4]: null;
				}				
			} else {
                $this->setParameters();
				return false;
            }                
		}
        
        $where='1';    
        if (! isset($WhereKey['1'])) {
            $where='';
            $i=0;
            $needtoskip=false;
            foreach($WhereKey as $key=>$val) {
                $iscondition = strtoupper($operator[$i]);
				$combine = $combiner[$i];
				if ( in_array(strtoupper($combine), array( 'AND', 'OR', 'NOT', 'AND NOT' )) || isset($extra[$i])) 
					$combinewith = (isset($extra[$i])) ? $combine : strtoupper($combine);
				else 
					$combinewith = _AND;
                if (! in_array( $iscondition, array( '<', '>', '=', '!=', '>=', '<=', '<>', 'IN', 'LIKE', 'NOT LIKE', 'BETWEEN', 'NOT BETWEEN', 'IS', 'IS NOT' ) )) {
                    $this->setParameters();
                    return false;
                } else {
                    if (($iscondition=='BETWEEN') || ($iscondition=='NOT BETWEEN')) {
						$value = $this->escape($combinewith);
						if (in_array(strtoupper($extra[$i]), array( 'AND', 'OR', 'NOT', 'AND NOT' ))) 
							$mycombinewith = strtoupper($extra[$i]);
						else 
                            $mycombinewith = _AND;
						if ($this->getPrepare()) {
							$where.= "$key ".$iscondition.' '._TAG." AND "._TAG." $mycombinewith ";
							$this->setParameters($val);
							$this->setParameters($combinewith);
						} else 
							$where.= "$key ".$iscondition." '".$this->escape($val)."' AND '".$value."' $mycombinewith ";
						$combinewith = $mycombinewith;
					} elseif ($iscondition=='IN') {
						$value = '';
						foreach ($val as $invalues) {
							if ($this->getPrepare()) {
								$value .= _TAG.', ';
								$this->setParameters($invalues);
							} else 
								$value .= "'".$this->escape($invalues)."', ";
						}													
						$where.= "$key ".$iscondition." ( ".rtrim($value, ', ')." ) $combinewith ";
					} elseif(((strtolower($val)=='null') || ($iscondition=='IS') || ($iscondition=='IS NOT'))) {
                        $iscondition = (($iscondition=='IS') || ($iscondition=='IS NOT')) ? $iscondition : 'IS';
                        $where.= "$key ".$iscondition." NULL $combinewith ";
                    } elseif((($iscondition=='LIKE') || ($iscondition=='NOT LIKE')) && ! preg_match('/[_%?]/',$val)) return false;
                    else {
						if ($this->getPrepare()) {
							$where.= "$key ".$iscondition.' '._TAG." $combinewith ";
							$this->setParameters($val);
						} else 
							$where.= "$key ".$iscondition." '".$this->escape($val)."' $combinewith ";
					}
                    $i++;
                }
            }
            $where = rtrim($where, " $combinewith ");
        }
		
        if (($this->getPrepare()) && !empty($this->getParameters()) && ($where!='1'))
			return " $whereorhaving ".$where.' ';
		else
			return ($where!='1') ? " $whereorhaving ".$where.' ' : ' ' ;
    }        
    
	/**
    * Returns an sql string or result set given the table, fields, by operator condition or conditional array
    *<code>
    *selecting('table', 
    *        'columns',
    *        where( eq( 'columns', values, _AND ), like( 'columns', _d ) ),
    *        groupBy( 'columns' ),
    *        having( between( 'columns', values1, values2 ) ),
    *        orderBy( 'columns', 'desc' );
    *</code>    
    *
    * @param @table, - database table to access
    *        @fields, - table columns, string or array
    *        @WhereKey, - where clause ( array(x, =, y, and, extra) ) or ( "x  =  y  and  extra" )
    *        @groupby, - 
    *        @having, - having clause ( array(x, =, y, and, extra) ) or ( "x  =  y  and  extra" )
    *        @orderby - 	*   
    * @return result set - see docs for more details, or false for error
	*/
    public function selecting($table='', $fields='*', ...$get_args) 
    {    
		$getfromtable = $this->fromtable;
		$getselect_result = $this->select_result;       
		$getisinto = $this->isinto;
        
		$this->fromtable = null;
		$this->select_result = true;	
		$this->isinto = false;	
        
        $skipwhere = false;
        $WhereKeys = $get_args;
        $where = '';
		
        if (empty($table)) {
            $this->setParameters();
            return false;
        }
        
        $columns = $this->to_string($fields);
        
		if (isset($getfromtable) && ! $getisinto) 
			$sql="CREATE TABLE $table AS SELECT $columns FROM ".$getfromtable;
        elseif (isset($getfromtable) && $getisinto) 
			$sql="SELECT $columns INTO $table FROM ".$getfromtable;
        else 
			$sql="SELECT $columns FROM ".$table;

        if (!empty($get_args)) {
			if (is_string($get_args[0])) {
                $args_by = '';
                $groupbyset = false;      
                $havingset = false;             
                $orderbyset = false;   
				foreach ($get_args as $where_groupby_having_orderby) {
                    if (strpos($where_groupby_having_orderby,'WHERE')!==false ) {
                        $args_by .= $where_groupby_having_orderby;
                        $skipwhere = true;
                    } elseif (strpos($where_groupby_having_orderby,'GROUP BY')!==false ) {
                        $args_by .= ' '.$where_groupby_having_orderby;
                        $groupbyset = true;
                    } elseif (strpos($where_groupby_having_orderby,'HAVING')!==false ) {
                        if ($groupbyset) {
                            $args_by .= ' '.$where_groupby_having_orderby;
                            $havingset = true;
                        } else {
                            $this->setParameters();
                            return false;
                        }
                    } elseif (strpos($where_groupby_having_orderby,'ORDER BY')!==false ) {
                        $args_by .= ' '.$where_groupby_having_orderby;    
                        $orderbyset = true;
                    }
                }
                if ($skipwhere || $groupbyset || $havingset || $orderbyset) {
                    $where = $args_by;
                    $skipwhere = true;
                }
			}		
		} else {
            $skipwhere = true;
        }        
        
        if (! $skipwhere)
            $where = $this->where( ...$WhereKeys);
        
        if (is_string($where)) {
            $sql .= $where;
            if ($getselect_result) 
                return (($this->getPrepare()) && !empty($this->getParameters())) ? $this->get_results($sql, OBJECT, true) : $this->get_results($sql);     
            else 
                return $sql;
        } else {
            $this->setParameters();
            return false;
        }             
    }
	
    /**
     * Get sql statement from selecting method instead of executing get_result
     * @return string
     */
    public function select_sql($table='', $fields='*', ...$get_args) 
    {
		$this->select_result = false;
        return $this->selecting($table, $fields, ...$get_args);	            
    }
    
	/** 
    * Does an create select statement by calling selecting method
    * @param @newtable, - new database table to be created 
    *	@fromcolumns - the columns from old database table
    *	@oldtable - old database table 
    *   @WhereKey, - where clause ( array(x, =, y, and, extra) ) or ( "x  =  y  and  extra" )
    *   example: where( array(key, operator, value, combine, extra) ); or where( "key operator value combine extra" );
    * @return mixed bool/result
	*/
    public function create_select($newtable, $fromcolumns, $oldtable=null, ...$fromwhere) 
    {
		if (isset($oldtable))
			$this->fromtable = $oldtable;
		else {
            $this->setParameters();
			return false;            
        }
			
        $newtablefromtable = $this->select_sql($newtable, $fromcolumns, ...$fromwhere);			
        if (is_string($newtablefromtable))
            return (($this->getPrepare()) && !empty($this->getParameters())) ? $this->query($newtablefromtable, true) : $this->query($newtablefromtable); 
        else {
            $this->setParameters();
            return false;    		
        }
    }
    
    /**
    * Does an select into statement by calling selecting method
    * @param @newtable, - new database table to be created 
    *	@fromcolumns - the columns from old database table
    *	@oldtable - old database table 
    *   @WhereKey, - where clause ( array(x, =, y, and, extra) ) or ( "x  =  y  and  extra" )
	*   example: where( array(key, operator, value, combine, extra) ); or where( "key operator value combine extra" );
    * @return mixed bool/result
	*/
    public function select_into($newtable, $fromcolumns, $oldtable=null, ...$fromwhere) 
    {
		$this->isinto = true;        
		if (isset($oldtable))
			$this->fromtable = $oldtable;
		else {
			$this->setParameters();
            return false;          			
		}  
			
        $newtablefromtable = $this->select_sql($newtable, $fromcolumns, ...$fromwhere);
        if (is_string($newtablefromtable))
            return (($this->getPrepare()) && !empty($this->getprepared())) ? $this->query($newtablefromtable, true) : $this->query($newtablefromtable); 
        else {
			$this->setParameters();
            return false;          			
		}  
    }
		
	/**
	* Does an update query with an array, by conditional operator array
	* @param @table, - database table to access
	*	@keyandvalue, - table fields, assoc array with key = value (doesn't need escaped)
	*   @WhereKey, - where clause ( array(x, =, y, and, extra) ) or ( "x  =  y  and  extra" )
	*   example: where( array(key, operator, value, combine, extra) ); or where( "key operator value combine extra" );
	* @return mixed bool/results - false for error
	*/
    public function update($table='', $keyandvalue, ...$WhereKeys) 
    {        
        if ( ! is_array( $keyandvalue ) || empty($table) ) {
			$this->setParameters();
            return false;
        }
        
        $sql="UPDATE $table SET ";
        
        foreach($keyandvalue as $key=>$val) {
            if(strtolower($val)=='null') {
				$sql.= "$key = NULL, ";
            } elseif(in_array(strtolower($val), array( 'current_timestamp()', 'date()', 'now()' ))) {
				$sql.= "$key = CURRENT_TIMESTAMP(), ";
			} else {
				if ($this->getPrepare()) {
					$sql.= "$key = "._TAG.", ";
					$this->setParameters($val);
				} else 
					$sql.= "$key = '".$this->escape($val)."', ";
			}
        }
        
        $where = $this->where(...$WhereKeys);
        if (is_string($where)) {   
            $sql = rtrim($sql, ', ') . $where;
            return (($this->getPrepare()) && !empty($this->getParameters())) ? $this->query($sql, true) : $this->query($sql) ;       
        } else {
			$this->setParameters();
            return false;
		}
    }   
         
	/** 
    * Helper does the actual delete query with an array
	* @return mixed bool/results - false for error
	*/
    public function delete($table='', ...$WhereKeys) 
    {   
        if ( empty($table) ) {
			$this->setParameters();
            return false;          			
		}  
		
        $sql="DELETE FROM $table";
        
        $where = $this->where(...$WhereKeys);
        if (is_string($where)) {   
            $sql .= $where;						
            return (($this->getPrepare()) && !empty($this->getParameters())) ? $this->query($sql, true) : $this->query($sql) ;  
        } else {
			$this->setParameters();
            return false;          			
		}  
    }
    
	/**
    * Helper does the actual insert or replace query with an array
	* @return mixed bool/results - false for error
	*/
    public function _query_insert_replace($table='', $keyandvalue, $type='', $execute=true) 
    {  
        if ((! is_array($keyandvalue) && ($execute)) || empty($table)) {
			$this->setParameters();
            return false;          			
		}  
        
        if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ))) {
			$this->setParameters();
            return false;          			
		}  
            
        $sql="$type INTO $table";
        $v=''; $n='';

        if ($execute) {
            foreach($keyandvalue as $key => $val) {
                $n.="$key, ";
                if(strtolower($val)=='null') 
                    $v.="NULL, ";
                elseif(in_array(strtolower($val), array( 'current_timestamp()', 'date()', 'now()' ))) 
                    $v.="CURRENT_TIMESTAMP(), ";
                else  {
					if ($this->getPrepare()) {
						$v.= _TAG.", ";
						$this->setParameters($val);
					} else 
						$v.= "'".$this->escape($val)."', ";
				}               
            }
            
            $sql .= "(". rtrim($n, ', ') .") VALUES (". rtrim($v, ', ') .");";

			if (($this->getPrepare()) && !empty($this->getParameters())) 
				$ok = $this->query($sql, true);
			else 
				$ok = $this->query($sql);
				
            if ($ok)
                return $this->insert_id;
            else {
				$this->setParameters();
				return false;          			
			}  
        } else {
            if (is_array($keyandvalue)) {
                if (array_keys($keyandvalue) === range(0, count($keyandvalue) - 1)) {
                    foreach($keyandvalue as $key) {
                        $n.="$key, ";                
                    }
                    $sql .= " (". rtrim($n, ', ') .") ";                         
                } else {
					return false;          			
				}          
            } 
            return $sql;
        }
	}
        
	/**
    * Does an replace query with an array
    * @param @table, - database table to access
    *   @keyandvalue - table fields, assoc array with key = value (doesn't need escaped)
    * @return mixed bool/id of replaced record, or false for error
	*/
    public function replace($table='', $keyandvalue) 
    {
            return $this->_query_insert_replace($table, $keyandvalue, 'REPLACE');
        }

	/**
    * Does an insert query with an array
    * @param @table, - database table to access
    * 		@keyandvalue - table fields, assoc array with key = value (doesn't need escaped)
    * @return mixed bool/id of inserted record, or false for error
	*/
    public function insert($table='', $keyandvalue) 
    {
        return $this->_query_insert_replace($table, $keyandvalue, 'INSERT');
    }
    
	/**
    * Does an insert into select statement by calling insert method helper then selecting method
    * @param @totable, - database table to insert table into 
    *   @tocolumns - the receiving columns from other table columns, leave blank for all or array of column fields
    *   @WhereKey, - where clause ( array(x, =, y, and, extra) ) or ( "x = y and extra" )
    *
    *   example: where( array(key, operator, value, combine, extra) ); or where( "key operator value combine extra" );
    * @return mixed bool/id of inserted record, or false for error
	*/
    public function insert_select($totable = '', $tocolumns = '*', $fromtable = null, $fromcolumns = '*', ...$fromwhere) 
    {
        $puttotable = $this->_query_insert_replace($totable, $tocolumns, 'INSERT', false);
        $getfromtable = $this->select_sql($fromtable, $fromcolumns, ...$fromwhere);
        if (is_string($puttotable) && is_string($getfromtable))
            return (($this->getPrepare()) && !empty($this->getParameters())) ? $this->query($puttotable." ".$getfromtable, true) : $this->query($puttotable." ".$getfromtable) ;
        else {
			$this->setParameters();
            return false;          			
		}                 
    }    
}
