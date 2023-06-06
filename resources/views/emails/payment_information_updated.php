<?php

$envelope = $message->embed(public_path() . '/user_files/images/envelope.png');
$phone = $message->embed(public_path() . '/user_files/images/mobile.png');
$projectname = Config::get('constants.settings.projectname');
$domainname = Config::get('constants.settings.domain');
$mail = Config::get('constants.settings.enquiry_email');
echo $msg = '<!DOCTYPE HTML>
<head>
   <title>HSCC | Payment information updated.</title>
   <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
   <style type="text/css">
      table tr {
      display: block;
      }
      table tr th {
      display: block;
      }
      table tr td {
      display: block;
      }
      table tr td table {
      display: block;
      }
      table tr td table tbody {
      display: block;
      }
      table tr td table tr td {
      display: block;
      }
   </style>
</head>
<body style="padding: 10px;background: #efefef;font-family: Poppins, sans-serif;">
   <table cellpadding="0" cellspacing="0" border="0" cellpadding="0" cellspacing="0" align="center" width="600" height="100%" style="background:#000;padding: 0px;color: #fff;text-align: center;position: relative;border-radius: 12px 12px 0 0;">
      <tbody>
         <tr>
            <td>
               <table align="center" width="100%">
                  <tbody style="background: #000;padding:0 50px">
                     <tr>
                        <td>
                          <img src="https://thehscc.co.uk/img/logo-white.webp" style="margin-top:10px">
                        </td>
                       <!--  <td>
                            <h1 style="font-size: 24px;font-weight: 600;margin-bottom: 0px;">Hello, ' .$name. '</h1>
                           
                        </td> -->
                        
                        <td>
                           <div style="background:#EB292B;width:fit-content;text-align:center;margin: 0 auto !important;padding:5px">
                             <p style="color:#fff !important;">You have successfully updated your wallet address. </p>
                             <p style="color:#fff !important;"> Member ID: '.$username.' </p>
                              <p style="color:#fff !important;">'.$currency_update_info.' </p>
                              <p style="color:#fff !important;">If this was you, you were successful. </p>

                           </div>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </td>
         </tr>
      </tbody>
   </table>
   <table cellpadding="0" cellspacing="0" border="0" cellpadding="0" cellspacing="0" align="center" width="600" height="100%" style="background:#ffffff;padding: 0px;color: #000;text-align: center;position: relative;border-radius: 0 0 12px 12px;">
      <tbody>
         <tr>
            <td>
               <table align="center" width="100%">
                  <tbody style="background: #ffffff;padding:30px">
                     <tr>
                        <td>
                           <h6 style="color: #000;font-size: 16px;line-height: 24px;margin: 10px 0;">If you did not initiate this action, your account may be compromised. Please contact our support team immediately at <a href="href:support@thehscc.co.uk" style="color:#000;">support@thehscc.co.uk</a> or reach out to us through our social media channels for assistance.
                           </h6>
                        </td>
                        <!--  <td>
                           <p style="font-size: 16px;line-height: 24px;font-weight: bold;">
                             <span style="display: block;color: #000;font-weight: bold;font-size: 25px;">IMPORTANT</span> If there’s any error in your login credentials or <br> you’d like to change your login details.
                           </p>
                           <p style="font-size: 12px;line-height: 24px;">Please get in touch with our support team at: <b>'.$mail.'</b>
                           </p>
                           <p style="font-weight:600;font-size:20px;text-align:center;line-height: 24px;">Start your investment journey <br> and enjoy huge rewards and incomes. </p>
                           </td> -->
                     </tr>
                  </tbody>
               </table>
            </td>
         </tr>
         <tr style="background:#000;margin:8px 20px;
            color: #fff;
            padding: 15px 50px 6px 15px;border-radius:12px;">
            <!--  <td><h3 style="font-weight: bold;font-size: 30px;margin: 0;">Happy Investing!</h3></td> -->
            <td style="display:flex;flex-direction: row;position: relative;">
               <p style="font-size: 16px;line-height: 25px;">
                  <span style="font-weight: bold;font-size: 22px;margin: 0;">Cheers!</span>
                  <br> HSCC is an investment company that aims to enhance its user’s financial stability and offer them easy and reliable solutions to depend on, for their economic growth. <br> Regards, <b>HSCC</b> <br>
                  <a href="https://www.thehscc.co.uk/" target="_blank" style="font-size: 12px;margin: 0;color: #fff;">www.thehscc.co.uk</a>
               </p>
               
            </td>
         </tr>
      </tbody>
      <tfoot>
         <tr>
            <td>
               <ul style="list-style: none;margin: 0;padding: 7px 0;background: #EB292B;border-radius: 0 0 12px 12px;">
                  <li style="display: inline-block;font-size: 20px;color: #fff;margin-right: 40px;">
                     <img src="' . $phone . '">
                     <a href="tel:+447537168069" style="color: #fff;text-decoration: none;">+44 7537 168069</a>
                  </li>
                  <li style="display: inline-block;font-size: 20px;color: #fff;">
                     <img src="' . $envelope . '">
                     <a href="mailto:'.$mail.'" target="_blank" style="color: #fff;text-decoration: none;">'.$mail.'</a>
                  </li>
               </ul>

            </td>
         </tr>
      </tfoot>
   </table>
</body>
</html>
';