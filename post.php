<?php
	include_once "dbtwconfig.php";
	require_once('twitteroauth/twitteroauth.php');
	require_once('facebook.php');
	require_once('config.php');

	if(!($argc)) {
		exit();		
	}

	// access database
	$conn = mysql_connect($host,$user, $password);
	if (!$conn) {
		die('Could not connect: ' . mysql_error());
	}
	mysql_select_db($database);
	$strSQL = "SELECT id, content, lat, lon FROM tweets where posted=0 and CONVERT_TZ(pubdate,'GMT','SYSTEM') <= NOW()";
	$result = mysql_query($strSQL);
	while ($row = mysql_fetch_assoc($result))	{
		if(postToTwitter($twitterusername,$twitterpassword,$row["content"],$row["lat"],$row["lon"])){
			$strUpdate = "UPDATE tweets SET posted=1 WHERE id=" . $row["id"];
			mysql_query($strUpdate);
		}
		postToFacebook($row["content"]);
	}
	mysql_close($conn);


/*
 * 
 * PostToTwitter
 * use oAuth to post a Twitter stauts update
 * 
 */
function PostToTwitter($twitterusername,$twitterpassword,$message,$lat,$lon){
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_TOKEN_SECRET);
    if($lat==''){  
    	$connection->post('statuses/update', array('status' => $message));	
    }else{
    	$connection->post('statuses/update', array('status' => $message, 'lat' => $lat, 'lon' => $lon));
    }
    return 1;
}



/*
 * 
 * PostToFacebook
 * post a status update to Facebook
 * 
 */
function PostToFacebook($status)
{
	try {
	 	$facebook = new Facebook(FB_APIKEY, FB_SECRET);
		$facebook->api_client->session_key = FB_SESSION;
	 	$fetch = array('friends' =>
	 		array('pattern' => '.*',
	 			'query' => "select uid2 from friend where uid1={$user}"));
			echo $facebook->api_client->admin_setAppProperties(array('preload_fql' => json_encode($fetch)));
			$message = $status;
			$page_id ="143831765103";
		if($facebook->api_client->stream_publish($message, $attachment,$action_links, null,$page_id))
	 		return 1;
	} catch(Exception $e) {
 		return 0;
	}
}

?>
