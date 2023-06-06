<?php
function historyPZM($last_id = 0)
{
	if ($last_id) {
		$url = 'http://localhost:8888/history?fromid=' . $last_id;
	} else {
		$url = 'http://localhost:8888/history';
	}
	$page = '';
	$result = get_web_page($url);
	if (($result['errno'] != 0) || ($result['http_code'] != 200)) {
		$error = $result['errmsg'];
	} else {
		$page = $result['content'];
	}
	$array_new = array();
	$xcmorewrite = explode("\n", str_replace("\r", '', $page));
	foreach ($xcmorewrite as $value) {
		if ($value) {
			$array_new[] = explode(";", $value);
		}
	}
	return $array_new;
}


/*Retriving page*/
function get_web_page($url)
{
	$uagent = "Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.14";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // recovers the web page         
           curl_setopt($ch, CURLOPT_HEADER, 0); // doesn’t recover headers   
           curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // follows redirects 
           curl_setopt($ch, CURLOPT_ENCODING, ""); // handles all encodings 
          curl_setopt($ch, CURLOPT_USERAGENT, $uagent); // useragent 
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); // time-out of the connection   
          curl_setopt($ch, CURLOPT_TIMEOUT, 20); // time-out of the answer 
          curl_setopt($ch, CURLOPT_MAXREDIRS, 2); // stops after the 10th redirect

           $content = curl_exec($ch);
	$err = curl_errno($ch);
	$errmsg = curl_error($ch);
	$header = curl_getinfo($ch);
	curl_close($ch);

	$header['errno'] = $err;
	$header['errmsg'] = $errmsg;
	$header['content'] = $content;
	return $header;
}

/*secondary function for coin send*/
function destinationPZM($pzm)
    {
        $url = 'http://localhost:8888/publickey?destination=' . $pzm;
        $page = '';
        $result = get_web_page($url);
        if (($result['errno'] != 0) || ($result['http_code'] != 200)) {
            $error = $result['errmsg'];
            return '';
        } else {
            $page = $result['content'];
            $haystack = "Public key absent";
            $haystack2 = "Send error!";
            $pos = strripos($page, $haystack);
            $pos2 = strripos($page, $haystack2);
            if ($pos === false AND $pos2 === false) {
                $xcmorewrite = explode(' ', $page);
                $page = trim($xcmorewrite[0]);
                return $page;
            } else {
                return '';
            }
        }
        return $page;
    }

    /*Coin Send*/
    public function payPZM($summa, $pzm, $public_key, $text)
	{
		$p2 = SENDKEY;   //  this is the password that you specified during setup
		$return = false;
		$url = 'http://localhost:8888/send?sendkey=' . $p2 . '&amount=' . $summa . '&comment=' . urlencode($text) . '&destination=' . $pzm . '&publickey=' . $public_key;
		$page = '';
		$result = get_web_page($url);

		if (($result['errno'] != 0) || ($result['http_code'] != 200)) {
			$error = $result['errmsg'];
		} else {
			$page = $result['content'];
		}

		if (preg_match('/^\+?\d+$/', $page)) {
			$return = true;
		} else {
			$return = false;
		}
		return $return;
	}

	/*Receiving current balance*/
	function getBalancePZM($pzm)
    {
        $ip = '*******';  // пример 192.168.1.1:9976  with port
		$url = 'http://'.$ip.'/prizm?requestType=getAccount&account=' . $pzm;
        $page = '';
        $result = get_web_page($url);
		//print_r($result); die;
        if (($result['errno'] != 0) || ($result['http_code'] != 200)) {
            $error = $result['errmsg'];
            return '';
        } else {
            $page = $result['content'];
            $page = json_decode($page, true);
            if ( isset($page['balanceNQT']) ) {
              return $page['balanceNQT'] / 100;
            } else {
              return 0;
            }
        }
    }

?>