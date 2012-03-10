<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

# get settings
	define ("DB_USER", "postgres");
	define ("DB_PASS", "i56kfm");
	define ("DB_DB", "cubit");
	

	$ALINK = 0;
	function db_conn_main ($db){
		global $ALINK;
		$ALINK = pg_connect("user=".DB_USER." password=".DB_PASS." dbname=".$db) or die ("Unable to connect to database server.\n\n");
		return $ALINK;
	}
	function db_exec ($query){
		global $ALINK;
		return pg_exec($ALINK, $query);
	}

	# Remove blk1
	db_conn_main("cubit");
	
	$Sl="SELECT * FROM companies";
	$Ri=pg_exec($Sl);
	
	$cd=pg_fetch_array($Ri);
	
	$VERSION=$cd['ver'];
	$run=0;
	
	while(($VERSION<2.3)&&($run<10)) {
		$run++;
		
		$OUTPUT = update();
		
		db_conn_main("cubit");
		
		$Sl="SELECT * FROM companies";
		$Ri=pg_exec($Sl);
		
		$cd=pg_fetch_array($Ri);
		
		$VERSION=$cd['ver'];
		
	}

print "Update complete. Please check Help > 'General Help' to make sure the versions is correct";

function getfldnames($tab){
	$names = "";
	$rs = pg_exec("SELECT * FROM $tab");
	for($i = 0; $i < pg_num_fields($rs); $i++){
		$name = pg_field_name($rs, $i);
		if($i+1 == pg_num_fields($rs)){
			$names .= "$name";
		}else{
			$names .= "$name,";
		}
	}
	return $names;
}

# View details
function update()
{

	$DB="y2005_audit_aaaa";
	$cmp['code']='aaaa';
	
	db_conn_main("cubit_".$cmp['code']);

	$Sl="SELECT cusnum,balance FROM customers";
	$Rw=pg_exec($Sl);
	
	while($cd=pg_fetch_array($Rw)) {

		$balance=$cd['balance'];
		
		db_conn_main($DB);
		
		$Sl="SELECT * FROM closedprd ORDER BY id DESC";
		$Ro=pg_exec($Sl);
		
		while($pd=pg_fetch_array($Ro)) {
		
			$Sl="SELECT * FROM $pd[prdname]_custledger WHERE cusnum='$cd[cusnum]' ORDER BY id DESC";
			$Rk=pg_exec($Sl);
			
			if(pg_num_rows($Rk)>0) {
				while($wd=pg_fetch_array($Rk)) {
				
					if($balance>0) {
						$dbal=$balance;
						$cbal=0;
					} elseif($balance<0) {
						$dbal=0;
						$cbal=$balance*-1;
					} else {
						$dbal=0;
						$cbal=0;
					}
				
					$Sl="UPDATE $pd[prdname]_custledger SET dbalance='$dbal',cbalance='$cbal' WHERE id='$wd[id]'";
					$Rd=pg_exec($Sl);
					
					$balance=$balance+$wd['credit']-$wd['debit'];
					
				}
			} else {
			
				if($balance>0) {
					$dbal=$balance;
					$cbal=0;
				} elseif($balance<0) {
					$dbal=0;
					$cbal=$balance*-1;
				} else {
					$dbal=0;
					$cbal=0;
				}
				
				$Sl="INSERT INTO $pd[prdname]_custledger (cusnum,contra,edate,eref,descript,credit,debit,div,dbalance,cbalance) VALUES 
				('$cd[cusnum]','0','$date','','Balance','0','0','2','$dbal','$cbal')";
				
				$Rd=pg_exec($Sl);
			}
		}
	}
	
	
	
	#SUP
	
	db_conn_main("cubit_".$cmp['code']);

	$Sl="SELECT supid,balance FROM suppliers";
	$Rw=pg_exec($Sl);
	
	while($cd=pg_fetch_array($Rw)) {

		
		$balance=$cd['balance'];
		
		db_conn_main($DB);
		
		$Sl="SELECT * FROM closedprd ORDER BY id DESC";
		$Ro=pg_exec($Sl);
		
		while($pd=pg_fetch_array($Ro)) {
		
			$Sl="SELECT * FROM $pd[prdname]_suppledger WHERE supid='$cd[supid]' ORDER BY id DESC";
			$Rk=pg_exec($Sl);
			
			if(pg_num_rows($Rk)>0) {
				while($wd=pg_fetch_array($Rk)) {
				
					if($balance<0) {
						$dbal=$balance*-1;
						$cbal=0;
					} elseif($balance>0) {
						$dbal=0;
						$cbal=$balance;
					} else {
						$dbal=0;
						$cbal=0;
					}
				
					$Sl="UPDATE $pd[prdname]_suppledger SET dbalance='$dbal',cbalance='$cbal' WHERE id='$wd[id]'";
					$Rd=pg_exec($Sl);
					
					$balance=$balance-$wd['credit']+$wd['debit'];
					
				}
			} else {
			
				if($balance<0) {
					$dbal=$balance*-1;
					$cbal=0;
				} elseif($balance>0) {
					$dbal=0;
					$cbal=$balance;
				} else {
					$dbal=0;
					$cbal=0;
				}
				
				$Sl="INSERT INTO $pd[prdname]_suppledger (supid,contra,edate,eref,descript,credit,debit,div,dbalance,cbalance) VALUES 
				('$cd[supid]','0','$date','','Balance','0','0','2','$dbal','$cbal')";
				
				$Rd=pg_exec($Sl);
			}
		}
	}
	
	

	return "\nSuccess<br>\n";
}
?>
