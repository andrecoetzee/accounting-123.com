<?

require("settings.php");



db_conn('cubit');

$Sl="SELECT * FROM test";

$Ri=db_exec($Sl);


$Sl="DELETE FROM test";

$Re=db_exec($Sl);

while($data=pg_fetch_array($Ri)) {
	print "sasasa".$data['id'];
}

exit;







/*
	db_conn('cubit');
	
	$Sl="select nextval ('invnum_generator') AS num";
	$Ri=db_exec($Sl);
	
	$data=pg_fetch_array($Ri);
	
	print $data['num'];
	
	$Sl="INSERT INTO rec(val1) VALUES('$data[num]')";
	$Ri=db_exec($Sl);
	
	exit;*/
	
