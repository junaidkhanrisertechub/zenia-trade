
<?php

$logo = '';
//$logo = $message->embed(public_path() . '/img/logo.png');
//$emaillogo = $message->embed(public_path() . '/img/email.png');
//$facebook = $message->embed(public_path() . '/img/facebook.png');

$projectname = Config::get('constants.settings.projectname');
echo $msg = '<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Confirmation Mail </title>
    <link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
  </head>
  <body style="background-color:#f4f3f3; font-family:"Raleway", sans-serif;">
    <div class="warp" style=" width: 600px; background: #ffe0; margin: 30px auto; display: block;border: 1px solid #c0c0c0;">
         <div class="wrapper-header" style=" padding: 20px 0; text-align: center; background: white;">
          <img src=' . $logo . ' alt="" width="200">
        </div>

          <div class="wrapper-body" style="background: #fff; padding: 40px;">

              <h3> Password Reset Succcessfully!!</h3>
               <br>
               <p>
                <b>
               Thank you for using password recovery option.
                </br>


               </p>
               <p>
                <b>Your password reset successfully.<br>


               </p>


               <p style="margin: 0 0 16px;">
                          Not sure why you received this email? Please
                          <a href="mailto:support@awaato.com" class="hover-underline" style="--text-opacity: 1; color: #7367f0; color: rgba(115, 103, 240, var(--text-opacity)); text-decoration: none;">let us know</a>.
                        </p>
                <p style="margin: 0 0 16px;">Thanks, <br>The Zenia Team</p>
          </div>
           </div>
      </div>
    </div>
  </body>';
