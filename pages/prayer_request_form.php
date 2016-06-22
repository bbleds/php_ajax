<?php
// require and connect

$valid = true;
$success = false;
$id = (isset($_GET['id'])) ? new MongoId($_GET['id']) : '';
$requiredFields = array('first_name','last_name','email','prayer_request','share_preference');
$requestToEdit = array();
$dataSet = isset($_POST['request']) ? $_POST['request'] : array();
$errorMsgs = array();
$path = explode('/', $_SERVER['PHP_SELF']);

// $action is equal to the script name or the action specified after the script name if it exists  
$action = $path[count($path)-1];

// Util functions
$util = new Util();

/**
 * _edit()
 *
 * Prints elements for edit and add functionality
 *
 * @access public
 *
 * @return $html
 */
function _edit($site, $success, $id, $dataSet){
	$preferences = array('share'=>'Share','share_anonymously'=>'Share Anonymously', 'private'=>'Private');
	$actionUrl = empty($id) ? 'http://'.$site.'/ajax/pages/prayer_request_form.php' : 'http://'.$site.'/ajax/pages/prayer_request_form.php?id='.$id;
	
	if($success){
		$message = !empty($id) ? 'Edited Record Successfully' : 'Prayer request submitted. Waiting on admin approval!';
		$html = '<h2 class="alert alert-success">'.$message.'</h2><a href="http://'.$site.'/ajax/pages/prayer_request_form.php"><button class="btn btn-primary">Go Back!</button</a>';
	} else {
	
		$html = '<form id="prayerRequestForm" action='.$actionUrl.' method="POST">'.
							'<label>First Name*</label>'.
							'<input class="form-control required" type="text" name="request[first_name]" value="'.get_existing_field('first_name', $dataSet).'" />'.
							'<label>Last Name*</label>'.
							'<input class="form-control required" type="text" name="request[last_name]" value="'.get_existing_field('last_name', $dataSet).'" />'.
							'<label>Email*</label>'.
							'<input class="form-control required" type="email" name="request[email]" value="'.get_existing_field('email', $dataSet).'" />'.
							'<label>Prayer Request*</label>'.
							'<textarea class="form-control required" name="request[prayer_request]" rows="5">'.get_existing_field('prayer_request', $dataSet).'</textarea>'.
							'<label>Share Preference*</label>'.
							generate_select($preferences, 'share_preference', $dataSet).
							'<button class="btn btn-primary">Save</button>'.
						'</form>'.
						'<a href="http://'.$site.'/ajax/pages/prayer_request_form.php"><button class="btn btn-danger">Cancel</button</a>';
	}
	
	return $html;
}

/**
 * _delete()
 *
 * Prints elemets for delete functionality
 *
 * @access public
 *
 * @return $html
 */
function _delete($site, $success, $id){
	if($success){
		$html = '<h2 class="alert alert-success">Deleted Record Successfully!</h2><a href="http://'.$site.'/ajax/pages/prayer_request_form.php"><button class="btn btn-primary">Go Back!</button</a>';
	} else {
		$html = '<h2>Are you sure you want to delete this prayer request?</h2>'.
						'<form method="POST" action="prayer_request_form.php/delete?id='.$id.'">'.
							'<input type="hidden" name="delete" value="1" />'.
							'<button class="btn btn-danger" type="submit">Yes</button>'.
						'</form>'.
						'<a href="http://'.$site.'/ajax/pages/prayer_request_form.php"><button class="btn btn-primary">Cancel</button</a>';
	}
	return $html;
}

/**
 * save()
 *
 * Saves or updates a saved record in the db
 *
 * @access public
 *
 * @param string $collectionName
 * @param obj $id
 * @param string $action
 * @param array $record
 *
 * @return bool $success
 */
function save($collectionName, $id, $action, $record){
	$success = true;
	
	// update
	if(!empty($id)){
		$resp = MDB::update($collectionName, array('_id'=>$id), array('$set'=>$record));;
	} else {
	// default
		$resp =  MDB::insert($collectionName, $record);
	}
	
	if($resp['error']){
		$success = false;
	} 
	
	return $success;
}

// validate id
if(!empty($id)){
	
	if(validate_id($id)){
		$resp = MDB::find('ajax_project', array('_id'=>$id));
		
		if(!$resp['empty']){
			$requestToEdit = $resp['data']['rows'][0];
			$dataSet = isset($_POST['request']) ? $_POST['request'] : $requestToEdit;
		}
		
	} else {
		die('<h1>Invalid id, please try again!</h1><a href="http://'.$projectUrl.'/ajax/pages/prayer_request_form.php"><button class="btn btn-primary">Go Back!</button</a>');	
	}
}

// delete
if(isset($_POST['delete'])){
	$resp = MDB::delete('ajax_project', array('_id'=>$id));
	if(!$resp['error']){
		$success = true;
	} else {
		$errorMsgs[] = '<p>There was a problem deleting that record, please try again!</p>';	
	}
}

// validate, format, and save data
if(isset($_POST['request'])){
	
	// validate required fields	
	foreach($requiredFields as $field){
		
		if(empty($_POST['request'][$field])){
			$valid = false;
			$errorMsgs[] = "<p>Please enter $field!</p>";
			
		} elseif($field == 'email'){
			//validate email
			if(!$util->validEmail($_POST['request'][$field])){
				$valid = false;
				$errorMsgs[] = "<p>Please enter a valid email address!</p>";
			}
		}
	}
	
	if($valid){	
		
		// format data		
		$newRecord = $_POST['request'];
		
		foreach($newRecord as $key=>$value){
				$newRecord[$key] = strtolower(htmlspecialchars(trim($value)));
		}
		
		$newRecord['date_posted'] = new MongoDate();
		$newRecord['prayer_counter'] = 0;
		
		// save data
		$success = save('ajax_project', $id, $action, $newRecord);
	}
	
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<meta name="keywords" content="ben" />
	<meta name="description" content="Ben." />
	<meta name="author" content="Ben" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no" />

	<link type="text/css" rel="stylesheet" href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css'/>
	<link type="text/css" rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery.ui.all.css"/>

	<title>Prayer Requests</title>

</head>

<body>
	<div role="document" class="container-fluid">
	<?php print $navbar; ?>
	<div class="row">
		
		<div class="col-md-8 col-md-offset-2">
<?php
if(empty($id)){
	print "<h1>Submit Prayer Request</h1><hr>";
} elseif($action == 'delete') {
	print "<h1>Delete Prayer Request</h1><hr>";
} else {
	print "<h1>Edit Prayer Request</h1><hr>";
}
?>
			<div id="errorBox">
<?php 
if(!empty($errorMsgs)){
	print str_replace('_',' ',implode('',$errorMsgs));
}
?>
			</div>
		</div>
		
		<div class="col-md-8 col-md-offset-2">
<?php
 
	if($action == 'delete' && !empty($id)){
		print _delete($projectUrl, $success, $id);
	} else {
		print _edit($projectUrl, $success, $id, $dataSet);
	}
?>
		</div>
	</div>
	</div>

	<script type="text/javascript" src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
	<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="../scripts/js/post_prayer.js"></script>  
</body>                               
