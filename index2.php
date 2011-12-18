<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include('bootstrap.php');
	function printDetails($status){

	echo "<table border='1'>";

	        echo "<tr><td>Memcache Server version:</td><td> ".$status ["version"]."</td></tr>";
	        echo "<tr><td>Process id of this server process </td><td>".$status ["pid"]."</td></tr>";
	        echo "<tr><td>Number of seconds this server has been running </td><td>".$status ["uptime"]."</td></tr>";
	        echo "<tr><td>Accumulated user time for this process </td><td>".$status ["rusage_user"]." seconds</td></tr>";
	        echo "<tr><td>Accumulated system time for this process </td><td>".$status ["rusage_system"]." seconds</td></tr>";
	        echo "<tr><td>Total number of items stored by this server ever since it started </td><td>".$status ["total_items"]."</td></tr>";
	        echo "<tr><td>Number of open connections </td><td>".$status ["curr_connections"]."</td></tr>";
	        echo "<tr><td>Total number of connections opened since the server started running </td><td>".$status ["total_connections"]."</td></tr>";
	        echo "<tr><td>Number of connection structures allocated by the server </td><td>".$status ["connection_structures"]."</td></tr>";
	        echo "<tr><td>Cumulative number of retrieval requests </td><td>".$status ["cmd_get"]."</td></tr>";
	        echo "<tr><td> Cumulative number of storage requests </td><td>".$status ["cmd_set"]."</td></tr>";

	        $percCacheHit=((real)$status ["get_hits"]/ (real)$status ["cmd_get"] *100);
	        $percCacheHit=round($percCacheHit,3);
	        $percCacheMiss=100-$percCacheHit;

	        echo "<tr><td>Number of keys that have been requested and found present </td><td>".$status ["get_hits"]." ($percCacheHit%)</td></tr>";
	        echo "<tr><td>Number of items that have been requested and not found </td><td>".$status ["get_misses"]."($percCacheMiss%)</td></tr>";

	        $MBRead= (real)$status["bytes_read"]/(1024*1024);

	        echo "<tr><td>Total number of bytes read by this server from network </td><td>".$MBRead." Mega Bytes</td></tr>";
	        $MBWrite=(real) $status["bytes_written"]/(1024*1024) ;
	        echo "<tr><td>Total number of bytes sent by this server to network </td><td>".$MBWrite." Mega Bytes</td></tr>";
	        $MBSize=(real) $status["limit_maxbytes"]/(1024*1024) ;
	        echo "<tr><td>Number of bytes this server is allowed to use for storage.</td><td>".$MBSize." Mega Bytes</td></tr>";
	        echo "<tr><td>Number of valid items removed from cache to free memory for new items.</td><td>".$status ["evictions"]."</td></tr>";

	echo "</table>";
	}


	   printDetails(Cache::getStats());
	?>