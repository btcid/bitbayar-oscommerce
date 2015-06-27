<?php

function bbcurlPost($url, $data) 
{
	if(empty($url) OR empty($data))
	{
		return 'Error: invalid Url or Data';
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST,count($data));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);  # Set curl to return the data instead of printing it to the browser.
	curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)"); # Some server may refuse your request if you dont pass user agent

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);
	return $result;
}

function bbLog($contents)
{
	error_log($contents);
}