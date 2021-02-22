<?php 
    session_start();

    include './pass.php';
	/*
        Arguments:
		$array - array to add surrounding chars to each element
		$chartype - a string of two chars, the opening char then closing char. 

		return the given array with all surrounding chars added. 

	*/
	function addSurroundingChars ($array,$chartype){

		foreach ($array as &$element){
			$element=$chartype[0].$element.$chartype[1];
		}
		return $array;
	}

	#Thanks to qeremy at php.net for this function. http://php.net/manual/en/function.str-split.php#107658
    # Splits a string while taking unicode into account. 
	function str_split_unicode($str, $l = 0) {
	    if ($l > 0) {
	        $ret = array();
	        $len = mb_strlen($str, "UTF-8");
	        for ($i = 0; $i < $len; $i += $l) {
	            $ret[] = mb_substr($str, $i, $l, "UTF-8");
	        }
	        return $ret;
	    }
	    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
	}


	#width of given string. 
    # Width is defined by the width value given to individual characters in 
    # getSizeOfChar function
	function lengthOfChars($string){

		$str_arr = str_split_unicode($string);
		$len = 0;
		foreach ($str_arr as $char){
				$len+=getSizeOfChar($char);
		}
		return $len;
		
	}

	#length values based on steam font in profile info boxes looked at by eye. Not exact...
	#sizes are relative, ex: Capital W(16) looks 4 times bigger than Capital J(4)
	function getSizeOfChar($char){

		$charArray = [
		//special chars
		'['=>6,']'=>6,'{'=>6,'}'=>6,'('=>6,')'=>6,'#'=>8,' '=>7,' '=>8,'-'=>6,'+'=>11,':'=>6,'.'=>6,'Û'=>12,','=>7,'!'=>7,'6'=>7,'*'=>7, '`'=>6,
            
		//lowercase alpha
		'a'=>8,'b'=>8,'c'=>8,'d'=>8,'e'=>8,'f'=>7,'g'=>8,'h'=>8,'i'=>4,'j'=>4,'k'=>8,'l'=>4,'m'=>14,'n'=>8,'o'=>8,'p'=>8,'q'=>8,'r'=>7,
			's'=>8,'t'=>7,'u'=>8,'v'=>8,'w'=>12,'x'=>8,'y'=>8,'z'=>8,
            
		//digits
		'1'=>8,'2'=>8,'3'=>8,'4'=>8,'5'=>8,'6'=>8,'7'=>8,'8'=>8,'9'=>8,'0'=>8,
            
		//uppercase alpha
		'A'=>12,'B'=>12,'C'=>12,'D'=>12,'E'=>12,'F'=>11,'G'=>13,'H'=>12,'I'=>7,'J'=>4,'K'=>12,'L'=>8,'M'=>14,'N'=>12,'O'=>13,'P'=>12,
			'Q'=>13,'R'=>12,'S'=>12,'T'=>11,'U'=>12,'V'=>12,'W'=>16,'X'=>12,'Y'=>12,'Z'=>11
        ];

		if(array_key_exists($char, $charArray))
			return $charArray[$char];
		else 
			return 8;

	}

	#Return a map of how many games were completed in a year
	#returns: [year -> Num Games Completed That Year]
	function createYearArray ($datesArray){

		//make the array of dates one big string
		$text = implode(",",$datesArray);

		//match all years store result in yearArray
		preg_match_all('/\d\d\d\d/', $text,$yearArray,PREG_PATTERN_ORDER);

		//returns array of form year => number of occurences. 
		$returnArray = array_count_values($yearArray[0]);
	
		return $returnArray;

	}

	#Return array of how many games completed per month
	#returns: [year-month=>numGamesCompleted]
	function createMonthArray ($datesArray){

		$text =implode(",",$datesArray);

		//match all year-month combos, store result in monthArray
		preg_match_all('/\d\d\d\d-\d\d/', $text,$monthArray,PREG_PATTERN_ORDER);

		//count the number of values in each year-month combo
		//returns array of form year-month => number of occurences. 
		$returnArray=array_count_values($monthArray[0]);

		return $returnArray;

	}

	//arg: str of form dddd-dd-dd
	//return: dddd
	function getYear($str){

		preg_match('/\d\d\d\d/', $str,$temp);
		return $temp[0];

	}

	//arg: str of form dddd-dd-dd
	//return: dddd-dd
	function getMonthYearNum($str){
		preg_match('/\d\d\d\d-\d\d/', $str,$temp);
		return $temp[0];
	}


	/*
	function: getMonthYearString($str)

	Arg: $str - a string containing the form dddd-dd-dd for a date

	Return: a string of format Month Year (May 2017)

	*/

	function getMonthYearString($str){
			
		$formatstring="";

		$months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 =>'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];

		//get all sequences of digits
		preg_match_all('/\d+/', $str,$temp);

		//the month value will be in the first set(perfect matches), and be the second match (after year).
		$month_num = $temp[0][1];
		$month_num = (int)$month_num;

		//temp[0][0] = year
		$formatstring.=$months[$month_num]." ".$temp[0][0];

		return $formatstring;
	}

	/*

		function: getAstatsInfo($steamid)

		Accepts a steamid64 as argument and gets contents of the astats page that corresponds to this steam id

		url args{
			Limit = 0 Show all games on one page. 
			PerfectOnly = 1 Only show 100% games.
		}

		Errors: Check if the user has a profile/entered id correctly/has any games completed 100%

		Returns: content of that Astats page as a string. 

	*/
	function getAstatsInfo($steamid){

		$url = "http://astats.astats.nl/astats/User_Games.php?Limit=0&PerfectOnly=1&Hidden=1&SteamID64=$steamid&DisplayType=1";
		$achievement_page = @file_get_contents($url);

		if(!$achievement_page){
			echo "ERROR: Failed to connect to astats site.";
            session_unset();
            session_destroy();
			exit;
		}

		if(preg_match('/No profile found/',$achievement_page)||preg_match("/Could not find profile/",$achievement_page)){
			return "not found";
		}
        
        if(empty($achievement_page)){
            echo "Enter a SteamId64: Should be 17 digits long\nMake sure the account has an astats profile generated.";
	        session_unset();
            session_destroy();
    		exit;
        }

		if(preg_match('/No results/',$achievement_page)){
			echo "Astats: Didn't find any 100% completed games.";
            session_unset();
            session_destroy();
			exit;
		}

		return $achievement_page;

	}

    /*   STEAMAPI
        Access the steam api with 'steamid' to see all the games that user owns on their account
        returns an array of all the games. 
    */
	function getSteamGames($steamid){
        
        $constant='constant';

        $url = "http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={$constant('steam_api_key')}&steamid=$steamid&format=json";

        $achievement_page = @file_get_contents($url);

        if(!$achievement_page||preg_match('/Internal Server Error/',$achievement_page)){
            echo "Couldn't find that steam account";
            exit;
        }

		$achievement_page = json_decode($achievement_page,true);
            
        if(isset($achievement_page['response']['game_count'])){
            if($achievement_page['response']['game_count']==0){
                echo "No games found under that steamid";
                exit;
            }
        }

		return $achievement_page['response']['games'];
	}

	/* STEAMAPI
		function: getPlayedGames($gamesArray)

		arg: $gamesArray Array of form [int] =>{ 'appid':
												'playtime_forever':
												}

		return: an array consisting of all appids for games which have playtime>0

	*/
	function getPlayedGames($gamesArray){

        $playedGames = array();

		foreach ($gamesArray as $game){

			if($game['playtime_forever']>0)
				$playedGames[]=$game['appid'];

		}

        if(count($playedGames)==0){
            echo "No completed games found for this account.";
            exit;
        }

		return $playedGames;
	}

    /* STEAM API
        Uses the gamesArray from getSteamGames to create a list of the 
        games the user has completed all achievements for. 
    */
	function getCompletedGames($gamesArray,$steamid){

		$completed = true;
        
        $completedTemp = array();

        $completedGames = array();
        
        //get all games as objects from api and place in completedTemp array. 
		foreach ($gamesArray as $game){
            
            $constant = 'constant';

			$url = "http://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v0001/?appid=$game&key={$constant('steam_api_key')}&steamid=$steamid";

            $gameObject=json_decode(@file_get_contents($url),true);

            $completedTemp[]=$gameObject;

		}

        //create completedGames array;
        foreach($completedTemp as $gameObject){


            if ((is_array($gameObject) || is_object($gameObject))&&(isset($gameObject['playerstats']['achievements']))){

                $achObject=$gameObject['playerstats']['achievements'];

                //for every game loop through all achievements
                foreach($achObject as $achievement){

                    //if any achievement is not achieved, they have not gotten 100%, completed=false.
                    if($achievement['achieved']==0){
                        $completed = false;
                    }

                }

                //place every completed game in the completeGames array
                if($completed==true){
                    $completedGames[]=$gameObject['playerstats'];
                }
                $completed=true;
            }

        }

        if(count($completedGames)==0){
            echo "No games have been completed for this account";
            exit;
        }

        // return the array of completed games. 
        return $completedGames;

	}

	/*
		Add line number to the left of each line of output.
		Arg: Array to add numbers to  
		Format: #(value)
		Return: Array with numbers added. 
	*/
	function addLineCount($array){

		$count = count($array);

		for($i=0;$i<$count;$i++){
			$len = $count - $i;
			if(($len)<10)
				$array[$i] = "#0$len - " . $array[$i];
			else
				$array[$i] = "#$len - "  . $array[$i];
		}

		return $array;

	}


    //*****   MAIN    ********
    
    /*
        first check if there is already session data for either astats or steam.
        If not look for an astats account. 
        If cant find astats account use steam to search. 
        The steam process functions will check for errors regarding the existence of the account/whether
        the account has any games completed.  
    */

    require_once("./Database.php");

    $myAdaptor = new DataBase();
       
        if(
       isset($_POST["steamid"]) && isset($_POST["date_column"]) && isset($_POST["num_column"]) 
	&& isset($_POST["split"])   && isset($_POST["schar"])       && isset($_POST["sort"])      
    && isset($_POST["surrChar"]))
        {
            
        // All user options
       $date_column = $_POST['date_column'];
       $num_column =  $_POST["num_column"];
       $split = $_POST["split"];
       $schar = $_POST["schar"];
       $sort = $_POST["sort"];
       $surrChar = $_POST['surrChar'];
       $button = $_POST['button'];
       $exclude = $_POST['exclude'];
       $excludeList = array_map('trim', explode(',', $exclude));
       array_push($excludeList,"thisIsNotASteamGame"); 
       $excludeList = array_map('strtolower', $excludeList);

       //a new username. 
       $newName = htmlspecialchars($_POST['newName']);
       $steamid = htmlspecialchars($_POST["steamid"]);
       
       //if trying to create new SAF account
       if($button == "new"){
            
            //check steam id value. 
          if(preg_match("/[0-9]{17}/",$steamid)!=1){
            echo "The steamdid value entered was invalid, this should be a 17 digit value";
            return;
          }

            // check if name already exists. 
          if($myAdaptor->isValid($newName)>0){

              echo "Sorry, that Username has been taken, try again.";  
              return;

              //if name works add to database. 
          }else{
            $myAdaptor->insertAccount($newName, $steamid);
            echo "You account has been added, please enter your username and click loadprofile.";
            return;
          }

        //if trying to log in with already made account
       }else{

            //if not a digit value, try database. 
            if(preg_match("/[0-9]{17}/",$steamid)==0){

                //if the name is valid then set steamid to the 17 digit value rather than the username. 
                if($myAdaptor->isValid($steamid)>0){

                    $steamid = $myAdaptor->getSteamID($steamid);

                //if not in database and not 17 digit value, name is invalid give error;
                }else{
                    echo "Could not find the given username or steam id. If trying to enter a steamd id, it should be 17 digits long.";
                    return;
                }
            }
       }
                    
	}else{
		echo "Enter a SteamId64: Should be 17 digits long";
        return;
	}
        
    //if the session is prepared. 
    if(isset($_SESSION["achievement_page"]) && isset($_SESSION["mysteamid"]) && $_SESSION["mysteamid"]==$steamid){
        $achievement_page = $_SESSION['achievement_page'];
        $source = 'astats'; 
        $_SESSION['homesteamid'] = $steamid;

    }else if(isset($_SESSION["steam_games"]) && isset($_SESSION["mysteamid"]) && $_SESSION["mysteamid"]===$steamid){

        $completed_games = $_SESSION['steam_games'];
        $source = "steam_done";

    }else{
        
        unset($_SESSION['homesteamid']);
        
        $achievement_page = getAstatsInfo($steamid);
        
        //DONT set session mysteamid yet for steam, will get set below if provided id is valid. 
        if($achievement_page == "not found"){
            //echo "No astats profile found with that id.";
            // exit;
            $source = 'steam';
        }else{
            $source = 'astats'; 
            $_SESSION['achievement_page'] = $achievement_page;
            $_SESSION['mysteamid'] = $steamid;
        }
    }
    
    if($source == 'astats'){

        //slimpage gets just the game data html, remove most website styling. 
        preg_match("/<tbody>[\s\S]*<\/tbody>/",$achievement_page,$temp);
        $slimpage = $temp[0];

        //create array of each games html elements. 
        preg_match_all('/<a href="Steam_Game_Info.+?<\/a>/', $slimpage,$temp1,PREG_PATTERN_ORDER);
        $names = $temp1[0];

        //delete garbage from name strings. 
        foreach ($names as &$element){
            $element=preg_replace("/<a href=.*AEE\'>/",'',$element);
            $element=preg_replace("/<\/a>/",'',$element);
        }

        //extract num achievements into $total array
        preg_match_all("/<\/a>.{46}\d+/", $slimpage,$temp2,PREG_PATTERN_ORDER);
        $tempStr=implode(",",$temp2[0]);
        preg_match_all("/AEE'>\d+/", $tempStr,$temp2,PREG_PATTERN_ORDER);
        $tempStr=implode(",",$temp2[0]);
        preg_match_all("/\d+/", $tempStr,$temp2,PREG_PATTERN_ORDER);
        $total = $temp2[0];

        //get dates from slimpage.
        preg_match_all('/\d*-\d*-\d*/', $slimpage,$temp3,PREG_PATTERN_ORDER);
        $dates = $temp3[0];

        $names = str_replace("<del>","",$names);
    
    }

    //If we are pulling data from steam
    if($source == "steam"){
    
        $x = getPlayedGames(getSteamGames($steamid));
        $completed_games = getCompletedGames($x,$steamid);
        $_SESSION['steam_games'] = $completed_games;
        $_SESSION['mysteamid'] = $steamid;
          
        $names = array_column($completed_games,'gameName'); 
    } 

    if($source == "steam_done"){ 

        foreach($completed_games as $game){
            
             $total[] = count($game['achievements']);
            
             $achievement_times = array_column($game['achievements'],'unlocktime');
             $dates[] = date("Y-m-d",max($achievement_times));

        }

        //sort dates descending order, sort names and total based on dates. 
        array_multisort($dates,SORT_DESC,$names,$total);
    }
    
    // ***** BEGIN MAIN ALGORITHM, RUNS REGARDLESS OF STEAM OR ASTATS SOURCE FROM HERE ON. *******

    //shorten very long game names, add ... to end only if using both column. 
    if($num_column=='true' && $date_column == 'true')
        $reduce = 260;
    else
        $reduce = 450;

    foreach($names as &$line){

        foreach($excludeList as $game){
            if(strpos(strtolower($line), $game) !== false){

                unset($total[array_search($line,$names)]);
                unset($dates[array_search($line,$names)]);
                unset($names[array_search($line,$names)]);

                $total = array_values($total);
                $names = array_values($names);
                $dates = array_values($dates);
            }
        }

        if(lengthOfChars($line)>=$reduce){
            $diff = lengthOfChars($line)-$reduce;
            $diff = $diff/8;
            $line = substr($line,0,strlen($line)-$diff);
            $line = $line . "...";
        }
    }

    if($surrChar == "none"){

    }else{
        $dates=addSurroundingChars($dates,$surrChar);
        $total=addSurroundingChars($total,$surrChar);
        $names=addSurroundingChars($names,$surrChar);
    }

    $names = addLineCount($names);

    $greatest=0;

    //find the length of the longest name, used to determine how many seperator chars to add. 
    foreach ($names as $item){
        $len = lengthOfChars($item);
        if($len>$greatest)
            $greatest=$len;
    }

    //Add a few extra chars so that the longest name has seperation too. 
    $greatest = $greatest+40;

    //if user wants either column, add the seperator char in after the names. 
    if($num_column=='true'||$date_column=='true'){

        foreach ($names as &$line){

            if($schar=='single'){
                $line.=' ';
                continue;
            }

            //(the length of the longest name + 100) - how long this name is. 
            $difference = $greatest - lengthOfChars($line);

            $numspace = $difference/getSizeOfChar($schar);

            while ($numspace>0){
                $line.=$schar;
                $numspace = $numspace - 1;
            }
        }
    }

    $greatest -=200;

    if($date_column=='true'&&$num_column=='true'){
        foreach ($total as &$line){

            if($schar=='single'){
                $line.=' ';
                continue;
            }

            $difference = $greatest - lengthOfChars($line);

            $numspace = $difference/getSizeOfChar($schar);

            while ($numspace>0){
                $line.=$schar;
                $numspace = $numspace - 1;
            }
        }
    }

    if($split=="year")
        $dateHash=createYearArray($dates);
    else
        $dateHash=createMonthArray($dates);

    $newFile = "";

    $least = min(count($names),count($dates),count($total));

    //build up a line of all the elements that the user wants. 
    for ($i=0; $i<$least;$i++){

        if($sort=="dateD")
            $index = $i;
        else if($sort == "dateA")
            $index = $least-1-$i;

        $theline = $names[$index];
        $numline = $total[$index];
        $dateline = $dates[$index];
    
        if($split=="year"&&$dateHash[getYear($dates[$index])]>0){
            
            $newFile .= "[h1]" . getYear($dates[$index]) . " - ";
            $newFile .= $dateHash[getYear($dates[$index])] . " Games Completed[/h1] \n";	
            $dateHash[getYear($dates[$index])]=-1;
            
        }else if($split=="month"&&$dateHash[getMonthYearNum($dates[$index])]>0){
            
            $newFile .= "[h1]" . getMonthYearString($dates[$index]) . " - ";
            
            if($dateHash[getMonthYearNum($dates[$index])]>1)
                $newFile .= $dateHash[getMonthYearNum($dates[$index])] . " Games Completed[/h1] \n";
            else
                $newFile .= $dateHash[getMonthYearNum($dates[$index])] . " Game Completed[/h1] \n";
            
            $dateHash[getMonthYearNum($dates[$index])]=-1;
        }

        if($num_column=='true')
            $theline.=$numline;
        if($date_column=='true')
            $theline.=$dateline;

        if($i!=$least-1)
            $theline.="\n";
        $newFile.=$theline;
    }

    echo $newFile;

?> 