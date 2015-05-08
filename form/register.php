<!doctype html>
<?php
/*
	Rename functions to something more verbose. Wtf do the different functions actually do? 
	Some are intended to filter and validate data, and then output the filtered data.
	Some are intended for option lists or radio buttons.
	
	Some are intended for behind-the-scenes validation. Are these actually required?

*/

function submitted() {
	return !empty($_GET);
}

$okfields = array();

function filterField($field, $silent=false, $canbeempty=false, $maxlen=1024, $linebreaksok=true) {
	global $okfields;
	# Check if $_POST is set, and whether $field is present:
	# FIXME: replace $_GET with $_POST
	if (empty($_GET)) return $okfields[$field] = $canbeempty;
	if (!array_key_exists($field, $_GET)) return $okfields[$field] = $canbeempty;
	
	$value = trim($_GET[$field]);
	if (!$linebreaksok) $value = str_replace(array("\r", "\n"), "", $value);
	$value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
	$value = substr($value, 0, $maxlen);
	
	if (!$silent) echo $value;
	// A string was either provided, or is not required:
	return $okfields[$field] = strlen($value) > 0 || $canbeempty;
}
function radio_selected($sel, $silent=false) {
	if ($sel && !$silent) echo " checked";
	return $sel;
}
function radio($field, $possibilities, $val, $silent=false) {
	# Check if the value of $field matches $val.
	# If missing, No is selected by default
	if (empty($_GET) || !array_key_exists($field, $_GET)) return radio_selected($val == $possibilities[0], $silent);
	$value = $_GET[$field];
	// Check if user submitted value is even allowed:
	if (!in_array($value, $possibilities)) return radio_selected($val == $possibilities[0], $silent);
	// Check if the user submitted value matches that of current field:
	return radio_selected($val == $value, $silent) ? $val : false;
}
function option_selected($sel, $silent=false) {
	if ($sel && !$silent) {
		echo ' selected="selected"';
	}
	return $sel;
}
function option($field, $possibilities, $val, $silent=false) {
	# Check if the value of $field matches $val.
	# If missing, No is selected by default
	if (empty($_GET) || !array_key_exists($field, $_GET)) return option_selected($val == $possibilities[0], $silent);
	$value = $_GET[$field];
	// Check if user submitted value is even allowed:
	if (!in_array($value, $possibilities)) return option_selected($val == $possibilities[0], $silent);
	// Check if the user submitted value matches that of current field:
	return option_selected($val == $value, $silent) ? $val : false;
}


function validateEmail($email){
    $re = '^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$';

    $atpos = strpos($email, "@"); 
    $dotpos = strrpos($email, "."); # reverse search -> _last_ dot
    
    if (strlen($email) == 0) return false; # empty
    if (!preg_match($re, $email)) return false; # invalid email address
    if ($atpos === false                # missing @
		|| $dotpos === false            # missing .
		|| $dotpos < $atpos + 2         # . before @ (isn't this checked by the regex?)
		|| $dotpos + 2 > strlen($email) # top level domain name is too short.
		) return false;

	return true; # email address seems to have passed validation tests!
}
function confirmEmail($email, $confemail) {
	return validateEmail($email) && $email == $confemail;
}

function validateform(&$message) {
	global $okfields;
	$fields = array('FirstName', 'Surname', 'Affil', 'BotTest');
	$err = 0;
	
	// FIXME: can probably ignore $fields.
	// FIXME: search through the global array $okfields, count errors from the number of false statements.
	// FIXME: We may possibly wish to output a more informative list of errors. Like, print whichever fields failed!
	for ($i = 0; $i < count($fields); $i++) {
		if (!filterField($fields[$i], true)) {
			$err++;
			$message = "Failed validation: ".$fields[$i]."\n";
		}
	}
	
	if (!confirmemail) $err++;
	
	if ($err > 0) {
		switch ($err) {
			case 1: $message .= "An error was detected. \n\n Please fill in the required field."; break;
			default: $message .= "A total of ".$err." errors were detected. \n\n Please fill in the required fields.";
		}
		return false;
	} else {
		return true;
	}
}

/*
Bot test: 
What's the city where astronomdagarna 2015 will be held: uppsala/upsala

*/



?>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>CS 19 | registration</title>
        <script src="../js/register.js"></script>
    	<link rel="stylesheet" href="../css/form.css">
    </head>
    
    <body>
    
    <div id="hero-div">
	    <h2 align="right"><a href="">back to homepage <i class="fa fa-arrow-circle-left "></i></a></h2>
    </div>
    
    <div id="form-div">
        <!-- <form name="regForm" onsubmit="return validateForm()"> -->
        <form name="regForm" action="register.php" method="get">
            <h1>Astronomdagarna 2015 Registration</h1>

            <div>
                <br/>
                
                <!-- PERSONAL INFORMATION SECTION -->
                
                <h2> <i class="fa fa-user"></i> Personal Information</h2>
                
                <label for="Title">Title</label><br/>
                <select class="choice" name="Title" id="Title"><?php $options = array("", "Prof", "Dr", "Ms", "Mrs", "Mr"); ?>
					<option value="" <?php option("Title", $options, ""); ?>></option>
                    <option value="Prof" <?php option("Title", $options, "Prof"); ?>>Prof</option>
                    <option value="Dr" <?php option("Title", $options, "Dr"); ?>>Dr</option>
                    <option value="Ms" <?php option("Title", $options, "Ms"); ?>>Ms</option>
                    <option value="Mrs" <?php option("Title", $options, "Mrs"); ?>>Mrs</option>
                    <option value="Mr" <?php option("Title", $options, "Mr"); ?>>Mr</option>
                </select>
                <br/>
                <label for="FirstName">First Name</label><br/>
                <input class="reg-input" type="text" required id="FirstName" name="FirstName" maxlength="50" size="30" onblur="validateField(name)" value="<?php filterField('FirstName',false,false,50,false); ?>"/>
                <span id="FirstNameError"></span>
                <br/>
                <label for="MiddleName">Middle</label><br/>
                <input class="reg-input" type="text" id="MiddleName" name="MiddleName" maxlength="1" size="2" value="<?php filterField('MiddleName',false,false,30,false); ?>"/>
                <br/>
                <label for="Surname">Surname</label><br/>
                <input class="reg-input" type="text" required id="Surname" name="Surname" maxlength="50" size="30" onblur="validateField(name)" value="<?php filterField('Surname',false,false,50,false); ?>"/>
                <span id="SurnameError"></span>
                <br/>
                <label for="Affil">Affiliation</label><br/>
                <input class="reg-input" type="text" required id="Affil" name="Affil" maxlength="100" size="50" onblur="validateField(name)" value="<?php filterField('Affil',false,false,100,false); ?>"/>
                <span id="AffilError"></span>
                <br/>
                <label for="Email">Email</label><br/>
                <input class="reg-input" type="email" required id="Email" name="Email" size="50" onblur="validateEmail(name)" value="<?php filterField('Email',false,false,150,false); ?>"/>
                <span id="EmailError"></span>
                <br/>
                <label for="EmailConfirm">Confirm Email</label><br/>
                <input class="reg-input" type="email" required id="EmailConfirm" name="EmailConfirm" size="50" onblur="confirmEmail(name)" value="<?php filterField('EmailConfirm',false,false,150,false); ?>"/>
                <span id="EmailConfirmError"></span>
                <br/>
                <label for="BotTest">Anti-spam mechanism <br/> Please type the name of the city hosting Astronomdagarna 2015</label><br/>
                <input class="reg-input" type="text" required id="BotTest" name="BotTest" maxlength="30" size="30" onblur="botTest(name)" value="<?php filterField('BotTest',false,false,50,false); ?>"/>
                <span id="BotTestError"></span>
                <br/>
                <br/>
            	
            	
            	<!-- BANQUET DINNER SECTION -->
            	<h2><i class="fa fa-coffee"></i> Conference Meals</h2>
                
                <label for="DietRestrictions">Do you have any dietary restrictions?</label><br/><br/>
                <input type="radio" id="DietRestrictions_Yes" name="DietRestrictions" value="Yes" <?php radio('DietRestrictions', array('No','Yes'), 'Yes');?>/> <label>&nbsp;Yes</label>
            	<input type="radio" id="DietRestrictions_No"  name="DietRestrictions" value="No" <?php  radio('DietRestrictions', array('No','Yes'), 'No'); ?>/> <label>&nbsp;No</label>
            	<br/><br/>
            	
                <label for="DietRestrictions_Details">If yes, please describe them:</label><br/><br/>
                <textarea name="DietRestrictions_Details" rows="15" style="width: 90%"><?php filterField('DietRestrictions_Details',false,false,500,true); ?></textarea>
                <br/><br/><br/>
                
            	<label for="AtBanquet">Conference banquet (150 SEK)</label><br/><br/>
            	<input type="radio" id="AtBanquet_Attending" name="AtBanquet" value="Attending" <?php radio('AtBanquet', array('Not Attending', 'Attending'), 'Attending');?>/> <label>&nbsp;Attending</label>
            	<input type="radio" id="AtBanquet_NotAttending" name="AtBanquet" value="Not Attending" <?php radio('AtBanquet', array('Not Attending', 'Attending'), 'Not Attending');?>/> <label>&nbsp;Not Attending</label>
            	<br/><br/><br/>
                
                <label for="FridyLunch">Friday lunch</label><br/><br/>
            	<input type="radio" id="FridayLunch_Attending" name="FridayLunch" value="Attending" <?php radio('FridayLunch', array('Not Attending', 'Attending'), 'Attending');?>/> <label>&nbsp;Attending</label>
            	<input type="radio" id="FridayLunch_NotAttending" name="FridayLunch" value="Not Attending" <?php radio('FridayLunch', array('Not Attending', 'Attending'), 'Not Attending');?>/> <label>&nbsp;Not Attending</label>
            	<br/><br/><br/>
                
            </div>
	        
	        <br/>
            <br/>
            <!-- OBS: Submit button purposely disabled, enable before launch -->
            <input type="reset" class="button"> <input type="submit" class="button" id="submit" value="Preview"/>
<?php 
if (submitted()) {
	if (validateform($message)) {
		echo '<input type="submit" class="button" id="submit" value="Submit" style="float: right; margin-top: 3px;" />';
		echo "\n";
	} else {
		echo "<pre>$message</pre>";
	}
}

?>
        </form>
      </div>

      
<?php echo "<pre>"; ob_start(); echo "GET: "; var_dump($_GET); echo ""; echo "POST: "; var_dump($_POST); echo "okfields: "; var_dump($okfields); $str = ob_get_contents(); ob_end_clean(); echo htmlspecialchars($str, ENT_QUOTES); echo "</pre>"; ?>
    
    </body>
</html>
