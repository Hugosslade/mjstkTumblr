<?php
# Hugo Scott-Slade, MJSTK
# Wrapper Script for Twitter OAuth
# Based on code by
# Arvin Castro, arvin@sudocode.net
# Mad Props to him

require_once "class-xhttp-php/class.xhttp.php";
require_once "config.php";

$requestTokenURL = "http://www.tumblr.com/oauth/request_token";
$authorizeURL    = "http://www.tumblr.com/oauth/authorize";
$accessTokenURL  = "http://www.tumblr.com/oauth/access_token";
$baseURL		 = "http://api.tumblr.com/v2/";

session_name('tumblroauth');
session_start();

xhttp::load('profile,oauth');
$tumblr = new xhttp_profile();
$tumblr->oauth($consumer_token, $consumer_secret);
$tumblr->oauth_method('get');

if(isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    echo 'You were logged out.<br><br>';
} else if(isset($_GET['login']) and !$_SESSION['loggedin']) {

    # STEP 2: Application gets a Request Token from Tumblr
    $data = array();
    $data['post']['oauth_callback'] = $callbackURL;
    $response = $tumblr->fetch($requestTokenURL, $data);

    if($response['successful']) {
        $var = xhttp::toQueryArray($response['body']);
        $_SESSION['oauth_token']        = $var['oauth_token'];
        $_SESSION['oauth_token_secret'] = $var['oauth_token_secret'];

        # STEP 3: Application redirects the user to Tumblr for authorization.
        header('Location: '.$authorizeURL.'?oauth_token='.$_SESSION['oauth_token'], true, 303);
        die();
		# STEP 4: (Hidden from Application)
		# User gets redirected to Tumblr.
		# Tumblr asks if she wants to allow the application to have access to her account.
		# She clicks on the "Allow" button.

    } else {
        echo 'error - token could not be fetched';
    }

} else if(isset($_GET['login']) and $_SESSION['loggedin']) {
	echo 'already logged in';
} else if($_GET['oauth_token'] == $_SESSION['oauth_token'] and $_GET['oauth_verifier'] and !$_SESSION['loggedin']) {

    # STEP 6: Application contacts Tumblr to exchange Request Token for an Access Token.
    $data = array();
    $data['post']['oauth_verifier'] = $_GET['oauth_verifier'];

    $tumblr->set_token($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    $response = $tumblr->fetch($accessTokenURL, $data);

    if($response['successful']) {

	    # STEP 7: Application now has access to the user's data,
	    # for reading protected entries, sending a post updates.
        $var = xhttp::toQueryArray($response['body']);

        $_SESSION['user_id'] = $var['user_id'];
        $_SESSION['screen_name'] = $var['screen_name'];
        $_SESSION['oauth_token'] = $var['oauth_token'];
        $_SESSION['oauth_token_secret'] = $var['oauth_token_secret'];
        $_SESSION['loggedin'] = true;
		echo 'logged in';
    } else {
        echo 'error - problems logging in';
    }
} else if(isset($_SERVER['QUERY_STRING'])){
	parse_str($_SERVER['QUERY_STRING'],$q);
	$m=$q['method'];
	unset($q['method']);
	$q['api_key']=$consumer_token;
	
	$q=http_build_query($q);
	
    $tumblr->set_token($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    $response = $tumblr->fetch($baseURL.$m."?".$q);
	if($response['successful']){
		header('Content-type: application/json; charset=utf-8');
		echo $response['body'];
	} else {
		echo "error";
	}
} else {
	echo "weird error";
}
?>
