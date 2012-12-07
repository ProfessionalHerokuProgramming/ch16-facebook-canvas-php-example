<?php

// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && 
        $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
    header('Location: https://'. $_SERVER['HTTP_HOST'] . 
        $_SERVER['REQUEST_URI']);
    exit();
}

// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');

require_once('sdk/src/facebook.php');

$facebook = new Facebook(array(
    'appId'  => AppInfo::appID(),
    'secret' => AppInfo::appSecret(),
    'sharedSession' => true,
    'trustForwarded' => true,
));

$user_id = $facebook->getUser();
if ($user_id) {
    try {
        // Fetch the viewer's basic information
        $basic = $facebook->api('/me');
    } catch (FacebookApiException $e) {
        // If the call fails we check if we still have a user. The user will be
        // cleared if the error is because of an invalid accesstoken
        if (!$facebook->getUser()) {
            header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
            exit();
        }
    }
}

?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0,
            maximum-scale=2.0, user-scalable=yes" />

        <title><?php echo he($app_name); ?></title>
        <link rel="stylesheet" href="stylesheets/screen.css" media="Screen"
            type="text/css" />
        <link rel="stylesheet" href="stylesheets/mobile.css" media="handheld,
            only screen and (max-width: 480px), only screen and
            (max-device-width: 480px)" type="text/css" />

        <style type="text/css">
            #guides div {
                margin-bottom: 20px;
            }
            
            #error {
                border:           1px solid red;
                padding:          15px;
                background-color: #FFE6E6;
                color:            red;
                font-weight:      bold;
            }
        </style> 

        <!--[if IEMobile]>
        <link rel="stylesheet" href="mobile.css" media="screen" 
            type="text/css" />
        <![endif]-->
    
        <!-- These are Open Graph tags.  They add meta data to your  -->
        <!-- site that facebook uses when your content is shared     -->
        <!-- over facebook.  You should fill these tags in with      -->
        <!-- your data.  To learn more about Open Graph, visit       -->
        <!-- 'https://developers.facebook.com/docs/opengraph/'       -->
        <meta property="og:title" content="<?php echo he($app_name); ?>" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="<?php echo AppInfo::getUrl(); ?>" />
        <meta property="og:image" content="<?php echo 
            AppInfo::getUrl('/logo.png'); ?>" />
        <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
        <meta property="og:description" 
            content="Vandelay Enterprises Facebook Contest" />
        <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />
    
        <script type="text/javascript" 
            src="/javascript/jquery-1.7.1.min.js"></script>

        <script type="text/javascript">
            function logResponse(response) {
                if (console && console.log) {
                  console.log('The response was', response);
                }
            }
    
            $(function() {
                // Set up so we handle click on the buttons
                $('#postToWall').click(function() {
                    FB.ui(
                        {
                            method: 'feed',
                            link: $(this).attr('data-url'),
                            name: 'Vandelay Enterprises Facebook Contest',
                            description: 'I just entered the Vandelay ' + 
                                'Enterprises Facebook Contest.  You should too!'
                        }, function (response) {
                            // If response is null the user canceled the dialog
                            if (response != null) {
                                logResponse(response);
                            }
                        }
                    );
                });
                
                $('#sendToFriends').click(function() {
                    FB.ui(
                        {
                            method: 'send',
                            link: $(this).attr('data-url'),
                            name: 'Vandelay Enterprises Facebook Contest',
                            description: 'Hey buddy.  I just entered the ' +
                                'Vandelay Enterprises Facebook Contest.  ' +
                                'You should too!'
                        }, function (response) {
                            // If response is null the user canceled the dialog
                            if (response != null) {
                                logResponse(response);
                            }
                        }
                    );
                });
            });
        </script>

        <!--[if IE]>
          <script type="text/javascript">
            var tags = ['header', 'section'];
            while(tags.length)
                document.createElement(tags.pop());
          </script>
        <![endif]-->
    </head>
  
    <body>
        <div id="fb-root"></div>
        <script type="text/javascript">
            window.fbAsyncInit = function() {
                FB.init({
                    appId      : '<?php echo AppInfo::appID(); ?>', // App ID
                    channelUrl : 
                        '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html',
                         // Channel File
                    status     : true, // check login status
                    cookie     : true, 
                        // enable cookies to allow the server to access the
                        // session
                    xfbml      : true // parse XFBML
                });

                // Listen to the auth.login which will be called when the user 
                // logs in using the Login button
                FB.Event.subscribe('auth.login', function(response) {
                    // We want to reload the page now so PHP can read the cookie
                    // that the Javascript SDK sat. But we don't want to use
                    // window.location.reload() because if this is in a canvas
                    //  there was a post made to this page and a reload will 
                    // trigger a message to the user asking if they want to send
                    // data again.
                    window.location = window.location;
                });

                FB.Canvas.setAutoGrow();
            };

            // Load the SDK Asynchronously
            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "//connect.facebook.net/en_US/all.js";
                fjs.parentNode.insertBefore(js, fjs);
            } (document, 'script', 'facebook-jssdk'));
        </script>

        <header class="clearfix">
            <div>
                <h1>Vandelay Enterprises Facebook Contest</h1>
            </div>
        </header>

        <section id="guides" class="clearfix">
<?php 
    // Check that user is logged in and that we can get basic FB info for user
    if (isset($basic)) { 
      
        // Creates the connection string for our Postgres DB using DATABASE_URL
        extract(parse_url($_ENV["DATABASE_URL"]));
        $pg_conn = "user=$user password=$pass host=$host port=$port " . 
                "dbname=" . substr($path, 1) . " sslmode=require";
        $db = pg_connect($pg_conn);
        
        // Check if user ID is already in DB entries table
        $result = pg_query($db, "SELECT * FROM entries WHERE fb_id = '" .
            pg_escape_string($basic['id']) . "' LIMIT 1;");

        if (pg_num_rows($result) != 0) {
?>
            <div>
                Sorry, <?php echo he(idx($basic, 'first_name')); ?>, but you
                have already entered this contest.  There is a maximum of one
                entry per person.
            </div>
<?php
        } else {
            // Check that form was submitted and user has agreed to rules
            if (isset($_POST["submit"]) and isset($_POST["agreed"]) and
                $_POST["agreed"] == 'yes') {

                // Insert new entry in the DB
                $result = pg_query($db, "INSERT INTO entries (fb_id, " .
                    "first_name, last_name, date_of_birth, gender, email, " .
                    "created) VALUES ('" .
                    pg_escape_string($basic['id']) . "', '" .
                    pg_escape_string($basic['first_name']) . "', '" .
                    pg_escape_string($basic['last_name']) . "', '" .
                    pg_escape_string($basic['birthday']) . "', '" .
                    pg_escape_string($basic['gender']) . "', '" .
                    pg_escape_string($basic['email']) . 
                        "', clock_timestamp());");
    
                if ($result != null) {
?>
            <div>
                Thanks, <?php echo he(idx($basic, 'first_name')); ?>!  Your 
                entry for the contest has been <b>successfully recorded</b>.  We
                will let you know shortly if you have won.
            </div>

            <div>
                <p>Why not <b>share this contest</b> with your friends?</p>
                <span>
                    <a href="#" class="facebook-button" id="postToWall"
                        data-url="<?php echo AppInfo::getUrl(); ?>">
                        <span class="plus">Post to Wall</span>
                    </a>
                </span>
                <span>
                    <a href="#" class="facebook-button speech-bubble"
                        id="sendToFriends" data-url="<?php 
                            echo AppInfo::getUrl(); ?>">
                        <span class="speech-bubble">Send Message</span>
                    </a>
                </span>
            </div>

<?php 
                } else { 
?>

            <div>
                Whoops!  Something went wrong and your entry could not be saved
                Please come back and try again later.
            </div>

<?php 
                }
            } else {
                // Form was submitted, but rules were not agreed to
                if (isset($_POST["submit"]) and (!isset($_POST["agreed"]) or
                    $_POST["agreed"] != 'yes')) { 
?>
            <div id="error">
                You must agree to the official rules in order to enter the
                contest.
            </div>
<?php 
                } 
                // Display home page for app if logged in
?>
            <div>Hi, <?php echo he(idx($basic, 'first_name')); ?>!</div>

            <div>
                <form method="post" id="myform" name="myform" 
                        style="margin: 0px;">
                    <input type="hidden" name="submit" value="submit" />

                    <div>
                        We are running a contest where you can win some really,
                        really neat prizes. All you have to do to enter, is
                        <b>check off the checkbox below</b>, indicating that 
                        you have read the rules.
                    </div>
    
                    <div>
                        Don't worry, we won't spam you.  We only need your
                        e-mail address to contact you if you win.  For details,
                        see our <a class="privacy" href="#"
                        onclick="javascript:alert('Privacy Policy:\n\nBlah, ' +
                            'blah, blah...');">Privacy Policy</a>.
                    </div>
                    
                    <div>
                        <input type="checkbox" name="agreed" value="yes" /> I
                        have read and agree to the <a class="rules" href="#"
                        onclick="javascript:alert('Contest Rules:\n\nBlah, ' +
                            'blah, blah...');">official rules</a>.
                    </div>

                    <div>
                        <input type="submit" name="btn-submit" 
                            value="Enter the Contest" />
                    </div>
                </form>
            </div>
<?php 
            } 
        } 
    } else { 
        // Could not get FB user info, they must not be logged in
?>
            <div>
                To enter the contest, you must first log in to the
                application.
            </div>
            
            <div class="fb-login-button" data-scope="user_birthday,email"></div>
<?php 
    } 
?>
        </section>
        <br /><br />
    </body>
</html>