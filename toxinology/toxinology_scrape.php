<?php
//try and get echos to come straight through
ob_implicit_flush(true);
//make sure it doesn't timeout
set_time_limit(0);

//open file for output
if (!($fp = fopen('toxinology.csv', 'w'))) {
    return;
}

// First, include Requests
include('./Requests/library/Requests.php');

// Next, make sure Requests can load internal classes
Requests::register_autoloader();

$num_array= array(array());
$tox_array= array(array());

//URLs
$tox_base = "http://toxinology.com/fusebox.cfm?fuseaction=";
$tox_tail = "&Common_Names_term=&Family_term=&Incidence_Key_term=&Genus_term=&Species_term=&countries_terms=&region_terms=";

function extractPosTag($str,$pos,$start_tag,$end_tag)
 {
      //str - string to search
      //pos - start position
      //start_tag - start delimiter
     //end_tag - end delimiter
         
         if($pos) 
         {
                 $pos_start_tag = stripos($str,$start_tag,$pos);
         }
         else
                 $pos_start_tag = stripos($str,$start_tag); //if no pos value get first tag found
                
         //get position one char in front of end delimiter
         $pos_end_tag = stripos($str,$end_tag,$pos_start_tag);
         //length of start and end delimiters
         $end_tag_len = strlen($end_tag);
         $start_tag_len = strlen($start_tag);
         //length of string to extract
         $len = (($pos_end_tag-$end_tag_len)-$pos_start_tag)-$end_tag_len;
         //Extract the tag
         $tag = substr($str,$pos_start_tag+$start_tag_len,$len);
         return $tag;
 }
 
 function extractTag($str,$id,$start_tag,$end_tag)
 {
      //str - string to search
      //id - text to search for
      //start_tag - start delimiter
     //end_tag - end delimiter
         
         if($id) 
         {
                 $pos_srch = stripos($str,$id);
                 //extract string up to id value
                 $beg = substr($str,0,$pos_srch);
                 
                 //get position of start delimiter
                 $pos_start_tag = strripos($beg,$start_tag);
         }
         else
                $pos_start_tag = stripos($str,$start_tag); //if no id value get first tag found     
         //get position of end delimiter
         $pos_end_tag = stripos($str,$end_tag,$pos_start_tag);
         //length of start and end delimiters
         $end_tag_len = strlen($end_tag);
         $start_tag_len = strlen($start_tag);
         //length of string to extract
         $len = (($pos_end_tag-$end_tag_len)-$pos_start_tag);
         //Extract the tag
         $tag = substr($str,$pos_start_tag+$start_tag_len,$len);
         
         return $tag;
 }

function getToxArray($mpurl,$idstr)
{
	try {
		$response = Requests::get($mpurl, array('Accept-Encoding' => 'identity'));
		}catch (Requests_Exception $e) {
		sleep(rand(7,22)); //big sleeps so toxinology doesn't get too flustered
		$response = Requests::get($mpurl);
	}
	

	$bodystr= $response->raw;
	
	$tablesrc=extractTag($bodystr,'class="page_title_brown"','table','/table');

	//find <tr> tag
	$pos_ID=stripos($tablesrc,'</tr>');
	$pos_ID=stripos($tablesrc,'<tr>', $pos_ID+1);
	while ($pos_ID !== false )
	{
		$row=extractPosTag($tablesrc,$pos_ID+1,'<tr>','</tr>');
		$url=extractTag($row,'target=','<a href="','" target=');
		$pos_nm=stripos($row,'_blank">');
		$pos_nm=stripos($row,'_blank">', $pos_nm+4);
		if($pos_nm !== false && $pos_ID !== false )
		{
			$name=extractPosTag($row,$pos_nm,'_blank">','</a>');
			
			$pos_nm=stripos($row,'_blank">', $pos_nm+4);
			if($pos_nm !== false)
			{ //we have a normal page with family species etc..
				$genusname=$name;
				$speciesname=extractPosTag($row,$pos_nm,'_blank">','</a>');
				$pos_nm=stripos($row,'_blank">', $pos_nm+4);
				$commonname=extractPosTag($row,$pos_nm,'_blank">','</a>');
				$name=$genusname . " " . $speciesname;
				if (strlen($commonname) > 0)
				{
					//clean up funny apostrophes
					$commonname = str_replace('&#39;',"'",$commonname);
					$commonname = str_replace('',"'",$commonname);
					$name = $commonname . " - " . $name;
				}
			}
			//shove it in an array
			$num_array[]=array($name,$url);
		}else
		{
			$name="";
		}
		
		$pos_ID=stripos($tablesrc,'<tr>', $pos_ID+1);
		//var_dump($pos_ID,$name);
	}
	
	//$num_array = array_unique ($num_array);
	//var_dump($num_array);
	return $num_array;
}	
	

	//Marine poisons
	$mpurl=$tox_base . "main.marine_poisonous.results" . "&Type_of_Poisoning_Te_term=";
	$num_array = getToxArray($mpurl,"MP");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got marine poisons\n";
	//Marine Invertebrates
	$mpurl=$tox_base . "main.marine_invertebrates.results" . "&Common_Names_term=&Phylum_term=&Class_term=&SubClass_term=&ord_term=&Genus_term=&Species_term=&countries_terms=&region_terms=&General_Information__term=";
	$num_array = getToxArray($mpurl,"MI");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got marine inverts\n";
	//Marine Vertebrates
	$mpurl=$tox_base . "main.marine_vertebrates.results" . "&Common_Names_term=&Class_term=&ord_term=&Family_term=&Genus_term=&Species_term=&General_Information__term=&countries_terms=&region_terms=";
	$num_array = getToxArray($mpurl,"MV");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got marine verts\n";
	//~ //Snakes
	$mpurl=$tox_base . "main.snakes.results" . $tox_tail;
	$num_array = getToxArray($mpurl,"SN");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got snakes\n";
	//~ //Spiders
	$mpurl=$tox_base . "main.spiders.results" . "&Common_Names_term=&Suborder_term=&Family_term=&Genus_term=&Species_term=&countries_terms=&region_terms=";
	$num_array = getToxArray($mpurl,"SP");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got spiders\n";
	//~ //Scorpions
	$mpurl=$tox_base . "main.scorpions.results" . "&Common_Names_term=&Family_term=&Genus_term=&Species_term=&countries_terms=&region_terms=&General_Information__term=";
	$num_array = getToxArray($mpurl,"SC");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got scorpions\n";
	//~ //Terrestrial Vertebrates
	$mpurl=$tox_base . "main.terrestrial_vertebrates.results" . "&Common_Names_term=&Class_term=&ord_term=&Suborder_term=&Family_term=&Genus_term=&Species_term=&countries_terms=&region_terms=&Venom_Keys_term=";
	$num_array = getToxArray($mpurl,"TV");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got terr verts\n";
	//~ //Terrestrial Invertebrates
	$mpurl=$tox_base . "main.terrestrial_invertebrates.results" . "&Common_Names_term=&Phylum_term=&Class_term=&SubClass_term=&ord_term=&Genus_term=&Species_term=&General_Information__term=&countries_terms=&region_terms=";
	$num_array = getToxArray($mpurl,"TI");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got terr inverts\n";
	//~ //Plants
	$mpurl=$tox_base . "main.poisonous_plants.results" . "&Common_Names_term=&Family_term=&Genus_term=&Species_term=&countries_terms=&Toxin_Classification_term=&Clinical_Effects_Ove_term=";
	$num_array = getToxArray($mpurl,"PP");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got plants\n";
	//~ //Fungi
	$mpurl=$tox_base . "main.poisonous_mushrooms.results" . "&Common_Names_term=&Phylum_term=&Class_term=&ord_term=&Family_term=&Genus_term=&Species_term=&countries_terms=&Toxin_Classification_term=&Primary_Clinical_Eff_term=";
	$num_array = getToxArray($mpurl,"PM");
	$tox_array = array_merge($tox_array,$num_array);
	echo "got mushrooms\n";


//Dump array to csv
foreach ($tox_array as list($a, $b)) {
    // $a contains the first element of the nested array,
    // and $b contains the second element.
    // echo "A: $a; B: $b\n";
    fprintf($fp, '"%s","http://toxinology.com/%s","Toxinology"%c', $a, $b,10);
}
//
//close file
fclose($fp);
?>
	


