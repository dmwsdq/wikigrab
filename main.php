<?php

set_time_limit(0);

// the URL of API
$url = 'http://id.wikipedia.org/w/api.php'; // change to your own mediawiki api URL if you need

// POST request to API
function apireq($q){
	global $url;
	$url = $url.'?'.$q;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Your Name/1.0 (http://yoursite.com/)');
	$result = curl_exec($ch);
	if (!$result) {
	  exit('cURL Error: '.curl_error($ch));
	}
	return $result;
}

// Query to API with format is forced to json, change to xml if you prefer xml
function wquery($a){
	$q = 'format=json&action=query&'.http_build_query($a);
	return apireq($q);	
}

// Get array of titles where namespace ID (ns) is 0 and category is specified
function getcm($c){
	$r = array();
	$c = 'Category:'.$c;
	$cmcontinue = false;
	do {
		$a = json_decode(wquery(array('list'=>'categorymembers','cmtitle'=>$c,'cmcontinue'=>$cmcontinue))); 
		$m = $a->query->categorymembers;
		foreach ($m as $v){
			if ((intval($v->ns)==0) and ($v->title != 'Halaman Utama')){
				array_push($r,$v->title);	
			}
		}
		if (property_exists($a,'query-continue')){
			$theEscapeForHyphen = 'query-continue'; 
			$cmcontinue = $a->$theEscapeForHyphen->categorymembers->cmcontinue;
		} else {
			$cmcontinue = false;
		}
	} while ($cmcontinue != false);
	return $r;
}

// get first key of assoc array / object --- when key is unknown
function gfk($a){
	foreach ($a as $k=>$v){
		return $k;
	}
}

// cleaning the content
function clncon($con0){
	$unwanted = array(); //specify regexes to be cleaned here, warning: actually, parsing html with regex is not really good
	$temp = $con0;
	foreach($unwanted as $elem){
		$temp = preg_replace($elem,"",$temp);
	}
	return $temp;
}

// Get content of a title, cleaned
function getcon($t){
	$con0 = json_decode(wquery(array('titles'=>$t,'prop'=>'revisions','rvprop'=>'content','rvparse'=>true)));
	$con1 = $con0->query->pages; $temp = gfk($con1);
	$con2 = $con1->$temp->revisions[0]; unset($temp); $temp = gfk($con2);
	$con3 = $con2->$temp; unset($temp);
	$con4 = '<hr> <h1> '.$t.' </h1> <br /> '.$con3;
	$con5 = clncon($con4);
	return $con5; 
}

// join contents of a category
function jcon($c,$s = null){
	$ar = array(); 
	$cm = getcm($c); 
	$ks = array_search($s, $cm); 
	if (!$ks){
		$ks = 0;
	} 
	for ($i = $ks; $i < count($cm); ++$i){
		$con = getcon($cm[$i]); 
		array_push($ar,$con);		
	}
	$jc = implode(" ", $ar);
	return $jc;
}

$c = $_POST['category'];
$s = $_POST['start'];
echo jcon($c,$s);

?>
