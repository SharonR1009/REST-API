<?php
require 'libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

function connectDB(){
	$user = 'root';
	$pass = '';
	$db = 'rest_api';
	$db = new mysqli('localhost',$user,$pass,$db) or die("Unable to connect");
	return $db;
}

function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

//Retrieve all skID
$app->get('/SkID',function() use ($app) {
	$db = connectDB();
	$sql = "SELECT SkID FROM infoTable";
	$stmt = $db->prepare($sql);
	$stmt->execute();

	$tmp = $stmt->get_result();
	while($r = $tmp->fetch_assoc())
		$msg[] = array("SkID" => $r['SkID']);
	$result = $msg;

	echoRespnse(200,$result);
	$stmt->close();	
});

//Search for users with 'Astro' in skBindUser
$app->get('/SkBindUser/search/Astro',function() use ($app){
	$db = connectDB();
	$sql = "SELECT * FROM userTable WHERE skBindUser = ?";
	$stmt = $db->prepare($sql);
	$name = "Astro";
	$stmt->bind_param("s",$name);
	$stmt->execute();

	$tmp = $stmt->get_result();
	while($r = $tmp->fetch_assoc())
		$jsonData[] = $r;
	$result = $jsonData;

	$stmt->close();
	echoRespnse(200,$result);
});

//Add a new skID
$app->post('/SkID',function() use ($app){
	$db = connectDB();
	$sql = "INSERT INTO infoTable(skID) VALUES (?)";
	$stmt = $db->prepare($sql);
	$skID = $app->request->post('SkID');
	if(!isSkIDExists($skID)){
		$stmt->bind_param("i",$skID);
		$stmt->execute();
		$stmt->close();
		if($skID != null){
			echo "Successfully Added SkID: $skID";
		}else{
			echo "Please input new SkID";
		}
	}else{
		echo "SkID already existed";
	}	
});

//update SkID based on primary key
$app->put('/SkID/:id',function($id) use ($app) {
	$db = connectDB();
	$sql = "UPDATE infoTable SET SkAddr = ?, SkBindUser = ?, SkOnline = ?, SkFd = ? WHERE SkID = ?";
	//$sql = "UPDATE infoTable SET SkFd = ? WHERE SkID = ?";
	$stmt = $db->prepare($sql);

	$SkID = $id;
	$SkAddr = $app->request->put('SkAddr');
	$SkBindUser = $app->request->put('SkBindUser');
	$SkOnline = $app->request->put('SkOnline');
	$SkFd = $app->request->put('SkFd');
	$stmt->bind_param("ssiii",$SkAddr,$SkBindUser,$SkOnline,$SkFd,$SkID);

	$stmt->execute();
	$num_affected_rows = $stmt->affected_rows;
	$stmt->close();
	if($num_affected_rows == 0){
		echo "The requested resource doesn't exists";
	}else{
		echo "Successfully Updated";
	}
});

//delete skID based on primary key
$app->delete('/SkID/:id',function($id) use ($app){
	$db = connectDB();
	$sql = "DELETE FROM infoTable WHERE SkID = ?";
	$stmt = $db->prepare($sql);

	$SkID = $id;
	$stmt->bind_param("i",$SkID);
	$stmt->execute();
	$num_affected_rows = $stmt->affected_rows;
	$stmt->close();
	if($num_affected_rows = 0){
		echo "The requested resource doesn't exists";
	}else{
		echo "Successfully Deleted";
	}
});

//Retrieve skID based on primary key
$app->get('/SkID/:id',function($id) use ($app){
	$db = connectDB();
	$sql = "SELECT * FROM infoTable WHERE SkID = ?";
	$stmt = $db->prepare($sql);

	$SkID = $id;
	$stmt->bind_param("i",$SkID);
	$stmt->execute();

	$tmp = $stmt->get_result();
	while($r = $tmp->fetch_assoc())
		$jsonData[] = $r;
	$result = $jsonData;

	$stmt->close();
	echoRespnse(200,$result);
});

/**
* Checking for duplicate info by SkID
*/
function isSkIDExists($SkID) {
	$db = connectDB();
	$sql = "SELECT SkID FROM infoTable WHERE SkID = ?";
	$stmt = $db->prepare($sql);
	$stmt->bind_param("i",$SkID);
	$stmt->execute();
	$stmt->store_result();
	$num_rows = $stmt->num_rows;
	$stmt->close();
	return $num_rows > 0;
}

/**
 *用于浏览器直接访问
 */
$app->get('/', function() use($app) {
    echoRespnse(201, 'HelloWorld');
});

$app->run();

?>