
<?php

$logo = '';
//$logo = $message->embed(public_path().'/img/logo.png');
//$emaillogo = $message->embed(public_path() . '/img/email.png');
//$facebook = $message->embed(public_path() . '/img/facebook.png');

$path        = Config::get('constants.settings.domainpath');
$projectname = Config::get('constants.settings.projectname');
echo $msg    = '<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Confirmation Mail </title>
    <link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
  </head>
  <body style="background-color:#f4f3f3;  font-family:"Raleway", sans-serif;">
    <div class="warp" style=" width: 600px; background: #ffe0; margin: 30px auto; display: block;border: 1px solid #c0c0c0;">
         <div class="wrapper-header" style=" padding: 20px 0; text-align: center; background: white;">
          <img src='.$logo.' alt="" width="200">
        </div>

          <div class="wrapper-body" style="background: #fff; padding: 40px;">

              <h2>Password Reset</h2>
               <p>
                <b>
                If you have lost your password or wish to reset it, click the link below. </b>
                </br>

               </p>

               <p>
               <a href="'.$path.'resetPassword/'.$reset_token.'/'.$user_id.'" style="    background: #7375f5;
                  padding: 8px;
                  color: #fff;
                  border: 1px solid #c1c1c1;
                  cursor: pointer;
text-decoreation:none;
">Link Here..</a>


               </p>
               <p>
                <b>If you did not request a new password please ignore this mail. Your password will remain the same.</b> <br>


               </p>



               <p style="margin: 0 0 16px;">
                          Not sure why you received this email? Please
                          <a href="mailto:support@awaato.com" class="hover-underline" style="--text-opacity: 1; color: #7367f0; color: rgba(115, 103, 240, var(--text-opacity)); text-decoration: none;">let us know</a>.
                        </p>
                <p style="margin: 0 0 16px;">Thanks, <br>The HSCC Team</p>
          </div>
           </div>
      </div>
    </div>
  </body>
  </html>';
