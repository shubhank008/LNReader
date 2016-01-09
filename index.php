<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<?php
 
require_once './include/db_handler.php';
require_once './include/bakatsuki.php';
require './Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
// User id from db - Global Variable
$user_id = NULL;
$api_url = "http://gator3224.hostgator.com/~whycloud/baka/index.php";
 
/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}


/**
 * Baka-Tsuki List Of Light Novels
 * url - /bakatsuki/list
 * method - GET
 * params -
 */
$app->get('/bakatsuki/list', function() use ($app) {
	$list = getList(); 
	$response=array('count'=>count($list),'result'=>$list);
            echoRespnse(200, $response);
        });

/**
 * Baka-Tsuki Light Novel Information
 * url - /bakatsuki/novel
 * method - GET
 * params - title
 */
$app->get('/bakatsuki/novel/:title', function($title) {
	global $api_url;
	$desc =trim(str_replace("\"","'",getDescForTitle($title)));
	$synopsis = trim(str_replace("\"","'",getMUSynopsisForTitle($title)));
	$images = getImageForTitle($title);
	$date = getDateForTitle($title);
	$altTitles = trim(strip_tags(getAltForTitle($title)));
	$genres = trim(str_replace('&nbsp; ',', ',strip_tags(getGenreForTitle($title))));
	$authDat = getAuthForTitle($title);
	$author = trim(strip_tags($authDat[0]));
	$illus = trim(strip_tags($authDat[1]));
	$chps_api = $api_url."/bakatsuki/chapters/".$title;
	$response = array("title"=>$title, "desc"=>$desc, "synopsis"=>$synopsis,"date"=>$date, "alt_title"=>$altTitles, "genres"=>$genres, "author"=>$author, "illus"=>$illus, "chapter_api"=>$chps_api, "images"=>$images);
            echoRespnse(200, $response);
        });
        
/**
 * Baka-Tsuki Chapters For Title
 * url - /bakatsuki/chapters
 * method - GET
 * params - title
 */
$app->get('/bakatsuki/chapters/:title', function($title) {
	$chapters = getVolumeData($title);
            $response = $chapters;
            echoRespnse(200, $response);
        });
 
$app->run();
?>