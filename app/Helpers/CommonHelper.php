<?php

/**
 * CommonHelper
 *
 */

/**
 * Return's admin assets directory
 *
 * CALLING PROCEDURE
 *
 * In controller call it like this:
 * $string = queryResultToString($result,$key);
 *
 * In View call it like this:
 * {{ queryResultToString(queryResultToString($result,$key)) }}
 *
 * @param array $result
 * @param string $key
 * @return string
 */


function queryResultToString($result,$key)
{
	$data = [];
    if(is_object($result)) {
    	foreach ($result as $value) {
    		$data[] = $value->$key;
    	}
    	$string = implode(', ', $data);
    } else {
    	$string = $result;
    }
    return $string;
}

/**
 * Set Date Format
 */
function offerDateFormat($date)
{
    return date('M-d-Y', strtotime($date));
}


function hideChr($string, $no_show = -2)
{
    //return str_pad(substr($string, $no_show), strlen($string), '*', STR_PAD_LEFT);
    return substr($string, $no_show);
}

function limitText($text, $limit) {
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        $text = substr($text, 0, $pos[$limit]) . '...';
    }
    return $text;
}

function splitStores($stores)
{
    $result = [];
    foreach ($stores as $store) {
        $chr = strtoupper(substr(trim($store['name']), 0,1));
        if(is_numeric($chr)){
            $result['0-9'][] = $store;
        } else
            $result[$chr][] = $store;
    }
    ksort($result);
    return $result;
}

function offersCountSorting($offers)
{
    $result['total'] = 0 ;
    foreach ($offers as $offer) {
        $key = strtolower($offer['type_name']);
        $key = str_replace(' ', '_', $key);
        $result[$key] = $offer['total'];
        $result['total'] += $offer['total'];
    }
    return $result;
}

function sideBarTopOffers($offers)
{
    $result = [];
    $filterArr = [];
    foreach ($offers as $offer) {
        if(!in_array($offer['offer_type'], $filterArr)){
            $result[] = $offer;
            array_push($filterArr, $offer['offer_type']);
        }
    }
    return $result;
}

function searchDataTransformation($data)
{
    $result = [];
    foreach ($data as $row) {
        $row['url'] = route('page', $row['website']);
        $row['logoUrl'] = uploadsUrl($row['logo']);
        $result['stores'][] = $row;
        $result['catIds'][] = $row['id'];
    }
    return $result;
}

function searchCatDataTransformation($data)
{
    $result = [];
    foreach ($data as $row) {
        $row['url'] = route('category.details', $row['slug']);
        $row['logoUrl'] = uploadsUrl($row['image_small']);
        $result[] = $row;
    }
    return $result;   
}

function setDiscountLabel($label, $offer_type)
{
    //free-shipping, off, sale
    $offer_type = str_replace(' ', '_', $offer_type);
    $label = strtolower($label);
    $result = [];
    if($offer_type == 'free_shipping'){
        $result['class'] = 'free-shipping';
        $label = str_replace('free', '<span>Free</span>', $label);    
    } else {
        $result['class'] = ($offer_type == 'codes') ? 'off' : 'sale';
        preg_match("([\£|\$]\d{1,5}|\d{1,5}\%?)u", $label, $match);
        if(isset($match[0])){
            $label = str_replace($match[0], '<span>'.$match[0].'</span>', $label); 
        }
    }

    if($label == 'sale')
        $label = str_replace('sale', '<span>Sale</span>', $label);

    $result['text'] = $label;

    return $result;
}

function textToShare($str)
{
    return $str = str_replace('%', '%25', $str);
}

/**
 * check file exist
 * @param  sting $url   url of the file
 * @return boolen     
 */
function remoteFileExists($url) {
    $curl = curl_init($url);

    //don't fetch the actual page, you only want to check the connection is ok
    curl_setopt($curl, CURLOPT_NOBODY, true);

    //do request
    $result = curl_exec($curl);

    $ret = false;

    //if request did not fail
    if ($result !== false) {
        //if request was ok, check response code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);  

        if ($statusCode == 200) {
            $ret = true;   
        }
    }

    curl_close($curl);

    return $ret;
}

function urlFilters($url) {
    $url = str_replace(' ', '%20', $url);
    return $url;
}

/**
 * copied from voucherpro opencart project then modified
 * @return array  
 */
function couponValueRange(){
    $result = [];
    for($i=1; $i<=99; $i++) {
        $result[] = $i.'%';
        $result[] = '$' .$i;
        $result[] = '£' .$i;
    }

    $result[] = 'Free';
    return $result;
}