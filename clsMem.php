<?php
   // helloacm.com
   
	define('__MEMHOST','localhost');
	define('__MEMPORT',11211);
	
	// memcached class
	class clsMem extends Memcache 
   {
      // connect object
		static private $m_objMem = NULL;
      
      // get the key value of table
      static function getTableNs($_table, $g=false)
      {
         if ($g)
         {
            $keykey = "TABLE_".$_table."_KEY";
         }
         else
         {
            $keykey = "TABLE_".$_table.$_SESSION['userId']."_KEY";
         }
			if (self::$m_objMem == NULL) 
         {
				self::$m_objMem=memcache_connect(__MEMHOST,__MEMPORT);
			}
		   $ns_key = self::$m_objMem->get($keykey);
         if($ns_key == false) 
         {
            $key=rand(1,10000);
            self::$m_objMem->set($keykey, $key, 0, 0);
            return $key;
         }
         return (integer)$ns_key;
      }
      
      // get the item key
      static function getQueryKey($query, $tables, $limit=-1, $offset=0)
      {
         $key="SQL".md5($query);
         foreach ($tables as $k=>$v)
         {
            $key=$key.$k.self::getTableNs($k,$v);
         }
         $key=$key."-".$limit."-".$offset;
         return $key;      
      }
      
      // expire tables
      static function expireTables($tables)
      {
			if (self::$m_objMem == NULL) 
         {
				self::$m_objMem = memcache_connect(__MEMHOST,__MEMPORT);
			}
         if (self::$m_objMem == NULL) return;
         foreach ($tables as $k=>$v)
         {
            if ($v)
            {
               self::$m_objMem->increment("TABLE_".$k."_KEY");
            }
            else
            {
               self::$m_objMem->increment("TABLE_".$k.$_SESSION['userId']."_KEY");            
            }
         }
      }
      
      // expire tables
      static function expireOtherTables($tables, $uid)
      {
			if (self::$m_objMem == NULL) 
         {
				self::$m_objMem = memcache_connect(__MEMHOST,__MEMPORT);
			}
         if (self::$m_objMem == NULL) return;
         foreach ($tables as $k=>$v)
         {
            if ($v)
            {
               self::$m_objMem->increment("TABLE_".$k."_KEY");
            }
            else
            {
               self::$m_objMem->increment("TABLE_".$k.$uid."_KEY");            
            }
         }
      }      
      
      // return reference
		static function getMem() 
      {
			if (self::$m_objMem == NULL) 
         {
				self::$m_objMem=memcache_connect(__MEMHOST,__MEMPORT);
			}
			return self::$m_objMem;
		}
	}
   /*
   ************************************************************************
   ***********************************************************************
   *
   * Below are functions that can be automatically validated
   *
   *               
   */	
	function cvSQL_GetOne($sSQL, $context, $tables, $timeout=0)  
   {
      $mmobj=clsMem::getMem();
      if ($mmobj==NULL)
      {
         return $context->db->GetOne($sSQL);
      }
      else
      {
         $querykey=clsMem::getQueryKey($sSQL,$tables);
         if ($objResultset=$mmobj->get($querykey))  
         {
   	      return $objResultset;
         }
   	   else  
         {
   	     $objResultset=$context->db->GetOne($sSQL);
   	     $mmobj->set($querykey, $objResultset, MEMCACHE_COMPRESSED, $timeout);
   	     return $objResultset;
   	   }
	   }
	}	
	
	function cvSQL_GetRow($sSQL, $context, $tables, $timeout=0)  
   {
      $mmobj=clsMem::getMem();
      if ($mmobj==NULL)
      {
         return $context->db->GetRow($sSQL);
      }
      else
      {
         $querykey=clsMem::getQueryKey($sSQL,$tables);
         if ($objResultset=$mmobj->get($querykey))  
         {
   	      return $objResultset;
         }
   	   else  
         {
   	     $objResultset=$context->db->GetRow($sSQL);
   	     $mmobj->set($querykey, $objResultset, MEMCACHE_COMPRESSED, $timeout);
   	     return $objResultset;
   	   }
	   }
	}		
	
	function cvSQL_SelectLimit($sSQL, $context, $tables, $timeout=0 ,$limit=-1,$offset=0)  
   {
      $mmobj=clsMem::getMem();
      $limit=(integer)$limit;
      $offset=(integer)$offset;
      if ($mmobj==NULL)
      {
         return $context->db->SelectLimit($sSQL,$limit,$offset);
      }
      else
      {
         $querykey=clsMem::getQueryKey($sSQL,$tables,$limit,$offset);
         if ($objResultset=$mmobj->get($querykey))  
         {
   	      return $objResultset;
         }
   	   else  
         {
   	     $objResultset=$context->db->SelectLimit($sSQL,$limit,$offset);
   	     if ($objResultset)
   	     {
   	        $t=$objResultset->GetArray();
   	        $mmobj->set($querykey, $t, MEMCACHE_COMPRESSED, $timeout);
              return $t;
           }
           return NULL;
   	   }
	   }
	}	

   /*
   ************************************************************************
   ***********************************************************************
   *
   * Below are functions that have a timeout expiry.
   *
   *               
   */
	function cSQL_GetOne($sSQL,$context,$timeout)  
   {
      $mmobj=clsMem::getMem();
      if ($mmobj==NULL)
      {
         return $context->db->GetOne($sSQL);
      }
      else
      if ($objResultset=$mmobj->get(md5('sql:'.$sSQL)))  
      {
	      return $objResultset;
      }
	   else  
      {
	     $objResultset=$context->db->GetOne($sSQL);
	     $mmobj->set(md5('sql:'.$sSQL), $objResultset, MEMCACHE_COMPRESSED, $timeout);
	     return $objResultset;
	   }
	}	

	function cSQL_GetRow($sSQL,$context,$timeout)  
   {
      $mmobj=clsMem::getMem();
      if ($mmobj==NULL)
      {
         return $context->db->GetRow($sSQL);
      }
      else
      if ($objResultset=$mmobj->get(md5('sql:'.$sSQL)))  
      {
	      return $objResultset;
      }
	   else  
      {
	     $objResultset=$context->db->GetRow($sSQL);
	     $mmobj->set(md5('sql:'.$sSQL), $objResultset, MEMCACHE_COMPRESSED, $timeout);
	     return $objResultset;
	   }
	}	

	function cSQL_GetCol($sSQL,$context,$timeout)  
   {
      $mmobj=clsMem::getMem();
      if ($mmobj==NULL)
      {
         return $context->db->GetCol($sSQL);
      }
      else
      if ($objResultset=$mmobj->get(md5('sql:'.$sSQL)))  
      {
	      return $objResultset;
      }
	   else  
      {
	     $objResultset=$context->db->GetCol($sSQL);
	     $mmobj->set(md5('sql:'.$sSQL), $objResultset, MEMCACHE_COMPRESSED, $timeout);
	     return $objResultset;
	   }
	}	
	
	function cSQL_GetArray($sSQL,$context,$timeout)  
   {
      $mmobj=clsMem::getMem();
      if ($mmobj==NULL)
      {
         return $context->db->GetArray($sSQL);
      }
      else
      if ($objResultset=$mmobj->get(md5('sql:'.$sSQL)))  
      {
	      return $objResultset;
      }
	   else  
      {
	     $objResultset=$context->db->GetArray($sSQL);
	     $mmobj->set(md5('sql:'.$sSQL), $objResultset, MEMCACHE_COMPRESSED, $timeout);
	     return $objResultset;
	   }
	}		

	function cSQL_SelectLimit($sSQL,$context,$timeout,$limit=-1,$offset=0)  
   {
      $mmobj=clsMem::getMem();
      $limit=(integer)$limit;
      $offset=(integer)$offset;
      if ($mmobj==NULL)
      {
         return $context->db->SelectLimit($sSQL,$limit,$offset);
      }
      else
      if ($objResultset=$mmobj->get(md5('sql:'.$sSQL." -Limit ".$limit." Offset ".$offset)))  
      {
	      return $objResultset;
      }
	   else  
      {
	     $objResultset=$context->db->SelectLimit($sSQL,$limit,$offset);
	     if ($objResultset)
	     {
	        $t=$objResultset->GetArray();
	        $mmobj->set(md5('sql:'.$sSQL." -Limit ".$limit." Offset ".$offset), $t, MEMCACHE_COMPRESSED, $timeout);
           return $t;
        }
        return NULL;
	   }
	}
	
	


?>
