
function ajaxlookup(event) {
	
	if($("updater").disabled
	&& event.target.id!="newButton"
	&& event.target.id!="button"){
		return;
	}

    //the users entered steam id value
	var steamidy = $("steamid").value;

	//user option box for number of achievements
	var num_col = $("num_column").checked;

	//user option box for date of achievements
	var date_col = $("date_column").checked;

    var split_opt;

	//Split options
	if ($("year").checked) {
		split_opt = "year";
	} else {
		split_opt = "month";
	}

	//char Options
	var e = $("charOption");
	var char = e.options[e.selectedIndex].value;
	if (char=="blank"){
		char = String.fromCharCode(8194);
	}

	//sort descending or ascending
	var j = $("sortOption");
	var sortopt = j.options[j.selectedIndex].value;

	//enclosing characters options
	var k = $("closeOption");
	var surrounding = k.options[k.selectedIndex].value;
    
    //the new username box. 
	var newNamey = $("newUser").value;

	var excludey = $("exclude").value

	console.log(newNamey);
	console.log(num_col);
	console.log(date_col);
	console.log(steamidy);
	console.log(split_opt);
	console.log(char);
	console.log(sortopt);
	console.log(surrounding);
	console.log(excludey)

    var buttonType;

    //If clicked create new user. 
    if(event.target.id=="newButton")
    	buttonType = "new";
    else
		buttonType = "regular";
	

    //if they already entered an id and want to 
    //update with new options
    if(event.target.id=="updater")
		steamidy = $("entered").textContent;
	
	if(buttonType=="new"){
		steamidy = $("newID").value;
	}	

    $("updater").disabled = false;
        
    $("entered").textContent = steamidy;

    $("content").textContent = "loading...please wait";
	
	console.log(buttonType);
	console.log(steamidy);

	
	new Ajax.Request("./achievement.php", {
							onSuccess: lookup_success,
							onFailure: lookup_failure,				
							parameters:
							{
								steamid: steamidy,
								button: buttonType,
								num_column: num_col,
								date_column: date_col,
								split: split_opt,
								schar: char,
								sort: sortopt,
                                surrChar:surrounding,
								newName: newNamey,
								exclude:excludey
							}
						}
	);

}

function lookup_failure(ajax) {
	console.log("Failed");
	console.log(ajax.status);
	console.log(ajax.statusText);
}

function lookup_success(ajax) {

	$("copyButton").disabled = false;
	$("copyButton").textContent = "Copy to Clipboard";
    
    var response = ajax.responseText;
    
    //put the response in the content box
	$("content").textContent = response;


}	

/*
	Copies the output pane content to the user clipboard

	This function triggered when user clicks
	"Copy to Clipboard" button

*/
function copyToClipboard(elem) {

	target = $('content');

    target.focus();
    target.setSelectionRange(0, target.value.length);
    
    // copy the selection
    var succeed;
    try {
    	succeed = document.execCommand("copy");
    } catch(e) {
        succeed = false;
    }

    if(succeed)
   		$('copyButton').textContent="Copied!";

    return succeed;
}

/*
	This function used to change output options
	when the user chooses one of the possible presets

	This function triggered when the option in the presets
	select box changes. 

*/
function preset(elem){

	var presetBox = $("presets");

	var presetString = presetBox.options[presetBox.selectedIndex].value;

	if(presetString=="amap"){
		$("num_column").checked=false;
		$("date_column").checked=false;
		$("year").checked=true;
		$("charOption").selectedIndex=6;
		$("closeOption").selectedIndex=3;
	}else if (presetString=="info"){
		$("num_column").checked=true;
		$("date_column").checked=true;
		$("month").checked=true;
		$("charOption").selectedIndex=0;
		$("closeOption").selectedIndex=2;
	}else if(presetString=="mine"){
		$("num_column").checked=false;
		$("date_column").checked=false;
		$("month").checked=true;
		$("charOption").selectedIndex=6;
		$("closeOption").selectedIndex=3;
	}

}

window.onload = function() {
	$("button").onclick = ajaxlookup;
	$("newButton").onclick = ajaxlookup;
    $("copyButton").onclick = copyToClipboard;
    $("updater").onclick = ajaxlookup;
    $("presets").onchange = preset;
    $("num_column").checked=false;
	$("date_column").checked=false;
	$("year").checked=true;
	$("charOption").selectedIndex=6;
	$("closeOption").selectedIndex=3;
}
