<?php
//$logo = $message->embed(public_path() . '/user_files/img/loginlogo.png');
//$backimg = $message->embed(public_path() . '/user_files/img/bg-img.jpg');
$logo = $message->embed(public_path() . '/user_files/images/hlogo.png');
$path       = Config::get('constants.settings.domainpath');
$linkexpire = Config::get('constants.settings.linkexpire');
//$logo       = asset('img/logo_new.png');
// $emaillogo  = asset('img/email.png');
// $facebook   = asset('img/facebook.png');

$projectname = Config::get('constants.settings.projectname');
$domainname = Config::get('constants.settings.domain');
echo $msg = '<!DOCTYPE HTML>

<head>
  <title>Transfer Amount mail</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>

<body style=" padding:30px; border:1px solid #eee; font-family: Poppins, sans-serif;">
<table width="600" cellspacing="0" cellpadding="0" bordercolor="#000000" border="0" align="center" style=" padding: 30px 0; background: transparent linear-gradient(338deg, #BCDD37 0%, #1E2128 100%) 0% 0% no-repeat padding-box; border: 8px solid #BCDD37; border-radius: 45px;">
    <tbody><tr>
      <td>
          <table width="100%" cellspacing="0" cellpadding="0">
            <tbody><tr>
              <td>
                <center>
                <img src="' . $logo . '" alt="" width="200">
                </center>
                 <h2 style="color: #ffffff;
    text-align: center;
    margin: 0px 0;
    line-height: 1.6;
    font-size: 30px;
    font-weight: 600;"> Dear User '.$username.' </h2>


             
                <p style="color: #ffffff;
    text-align: center;
    margin: 10px 0;
    line-height: 1.6;
    font-size: 20px;
    font-weight: 700;">

                '.$content.'


  <p style="    text-align: center;
    color: #ffffff;
    font-size: 18px;">
You can login here: <a href="'.$domainname.'" target="_blank" style="color: #2b5cdd;
    text-decoration: underline;">"'.$domainname.'" </a> <br/>


Thank you.<br/>
</p>
 
              </td>
            </tr>
          </table>

      </td>
    </tr>
  </table>
</body>

</html>

';

