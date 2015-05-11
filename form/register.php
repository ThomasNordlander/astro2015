<!doctype html>
<?php
function submitted() { // did the user actually submit data?
	return !empty($_GET);
}
function finished() {
	$field = "Submit";
	return !empty($_GET) && array_key_exists($field, $_GET) && $_GET[$field] == "Submit";
}

$okfields = array(); // store results of validation

// Execute sanity filtering for user input, and check whether the input is
//  valid for this field. The input value is overwritten after filtering.
function filterField($field, $canbeempty=false, $maxlen=1024, $linebreaksok=true, &$value) {
	global $okfields;
	$value = "";
	# Check if $_POST is set, and whether $field is present:
	# FIXME: replace $_GET with $_POST
	if (empty($_GET)) return $okfields[$field] = $canbeempty;
	if (!array_key_exists($field, $_GET)) return $okfields[$field] = $canbeempty;
	
	$value = trim($_GET[$field]);
	if (!$linebreaksok) $value = str_replace(array("\r", "\n"), "", $value);
	$value = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
	$value = substr($value, 0, $maxlen);
	
	$_GET[$field] = $value; // store filtered input value
	
	// A string was either provided, or is not required:
	return $okfields[$field] = strlen($value) > 0 || $canbeempty;
}
// Validate radio button input. If no valid data are present, the default
//  option will be highlighted. The default is always index 0 in $possibilities.
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
// HTML output to indicate which radio button is selected:
function radio_selected($sel, $silent=false) {
	if ($sel && !$silent) echo " checked";
	return $sel;
}
// Validate input for an option list. If no valid data are present, the default
//  option will be highlighted. The default is always index 0 in $possibilities.
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
// HTML output to indicate which option is selected
function option_selected($sel, $silent=false) {
	if ($sel && !$silent) echo ' selected="selected"';
	return $sel;
}

// Validate and filter input email address:
function validateEmail($email){
    $re = '/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';

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
// Validate email input, and check whether user actually submitted the same address both times:
function confirmEmail() {
	filterField('Email',false,150,false, $email);
	filterField('EmailConfirm',false,150,false, $confemail);
	return validateEmail($email) && $email == $confemail;
}
// Ensure user is not a bot. The password is (case-insensitive) "Uppsala" or "Upsala".
function botTest() {
	global $okfields;
	$field = 'BotTest';
	filterField($field,false,50,false, $str); // execute filtering to retrieve string
	return $okfields[$field] = preg_match('/up+sala/i', $str); // did user write something related to Uppsala?
}

// Check whether any fields failed validation:
function validateform(&$message) {
	global $okfields;
	$fields = array('FirstName', 'Surname', 'Affil');
	$err = 0;
	$message = "";
	
	
	
	/* FIXME: Could instead search through the global array $okfields, and count errors 
	 *  from the number of false statements. But that makes it more tedious to list
	 *  verbosely which fields failed validation, so yolo.
	 */
	for ($i = 0; $i < count($fields); $i++) {
		switch ($fields[$i]) {
			case 'FirstName': case 'Surname': $ok = filterField($fields[$i],false, 50,false); break;
			case 'Affil':                     $ok = filterField($fields[$i],false,100,false); break;
		}
		if (!$ok) {
			$err++;
			$message .= "Failed validation: ".$fields[$i]."\n";
		}
	}
	
	if (!confirmEmail()) {
		$err++;
		$message .= "Email addresses must be valid, and match.\n";
	}
	
	if (!botTest()) {
		$err++;
		$message .= "You failed the anti-spam mechanism!\n";
	}
	
	if ($err > 0) {
		switch ($err) {
			case 1: $message = "An error was detected. Please fill in the required field.\n\n" . $message ; break;
			default: $message = "A total of ".$err." errors were detected. Please fill in the required fields!\n\n" . $message;
		}
		return false;
	} else {
		return true;
	}
}



// Extract input data:
function get($field) {
	if (empty($_GET) || !array_key_exists($field, $_GET)) return "";
	return $_GET[$field];
}

// Execute validation on input data:
$submitted = !empty($_GET);
$valid = validateform();
$name = get('FirstName');
$surname = get('Surname');
$affil = get('Affil');
$email = get('Email');
$emailconfirm = get('EmailConfirm');
$bottest = get('BotTest');
// Extract these using radio() and option() instead:
$title = get("Title"); if (!in_array($title, array("", "Prof", "Dr", "Ms", "Mrs", "Mr"))) $title = "";

$diet = get("DietRestrictions"); if (!in_array($diet, array("Yes", "No"))) $diet = "No";
filterField('DietRestrictions_Details',false,500,true, $diet_details);
$banquet = get("AtBanquet"); if (!in_array($banquet, array("Attending", "Not Attending"))) $banquet = "Not Attending";
$lunch = get("FridayLunch"); if (!in_array($lunch,   array("Attending", "Not Attending"))) $lunch = "Not Attending";


// echo "<pre>"; $arr = get_defined_vars(); print_r($arr); echo "</pre>";

?>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>CS 19 | registration</title>
        <script src="../js/register.js"></script>
        <link rel="stylesheet" href="../css/form.css">
        <?php if(submitted()) echo '<script type="text/javascript"> window.onload=validateForm; </script>'; ?>

    </head>
    
    <body>
    
    
    <div id="hero-div">
	    <h2 style="float: right;"><a href="">back to homepage <i class="fa fa-arrow-circle-left "></i></a></h2>
    </div>
    
    <div id="form-div">
        <!-- <form name="regForm" onsubmit="return validateForm()"> -->
        <form name="regForm" action="register.php" method="get">
            <h1>Astronomdagarna 2015 Registration</h1>

            <div>
            
<?php
// If user submitted data, execute validation, save them to file and email a copy to SOC, and display victory message

if (finished() && $valid) { // user clicked "submit", and all data are valid!
	// Attempt to store data:
	date_default_timezone_set('Europe/Stockholm'); $time = date("r");
	$filename = date("U").'_'.preg_replace("/[^a-zA-ZåäöÅÄÖ \.]/g", "", str_replace(" ", "_", $name.'_'.$surname));
// 	$ok = file_put_contents(".registration/".$filename, $title."\n".$name."\n".$surname."\n".$affil."\n".$email."\n".$diet."\n".$banquet."\n".$lunch."\n".$diet_details);
// 	if (!$ok) echo "couldn't save file '.$filename";
?>
	<h2> You are now registered! </h2>
	<p class="reg-input">We have registered the following information. If anything appears wrong or unclear, please send an email to [FIXME]</p>
	
	
	<h2> <i class="fa fa-user"></i> Personal Information</h2>
	<label>Name</label>
	<p class="reg-input"><?php echo "$title $name $surname";?></p>
	
	<label>Affiliation</label>
	<p class="reg-input"><?php echo $affil;?></p>
	
	<label>Email</label>
	<p class="reg-input"><?php echo $email;?></p>
	
	<h2><i class="fa fa-coffee"></i> Conference Meals</h2>
	<p class="reg-input"><?php echo ($diet == 'Yes' ? "You have the following dietary restrictions: ". $diet_details : "You do not have any dietary restrictions"); ?></p>
	<p class="reg-input"><?php echo ($banquet == 'Attending' ? 'You will be attending the conference banquet.' : 'You will not be attending the conference banquet.'); ?></p>
	<p class="reg-input"><?php echo ($lunch == 'Attending' ? 'You requested coupons for the Friday lunch.' : 'You have not requested coupons for the Friday lunch. Note that these can still be purchased in the canteen!'); ?></p>
	
	</div>
</body>
</html>
	
	
<?php
	exit;
}

?>
     
            
                <br/>
                
                <!-- PERSONAL INFORMATION SECTION -->
                
                <h2> <i class="fa fa-user"></i> Personal Information</h2>
                
                <label for="Title">Title</label><br/>
                <select class="choice" name="Title" id="Title"><?php $options = array("", "Prof", "Dr", "Ms", "Mrs", "Mr"); ?>
					<option value="" <?php option("Title", $options, ""); ?>>&nbsp;</option>
                    <option value="Prof" <?php option("Title", $options, "Prof"); ?>>Prof</option>
                    <option value="Dr" <?php option("Title", $options, "Dr"); ?>>Dr</option>
                    <option value="Ms" <?php option("Title", $options, "Ms"); ?>>Ms</option>
                    <option value="Mrs" <?php option("Title", $options, "Mrs"); ?>>Mrs</option>
                    <option value="Mr" <?php option("Title", $options, "Mr"); ?>>Mr</option>
                </select>
                <br/>
                <label for="FirstName">First Name</label><br/>
                <input class="reg-input" type="text" required id="FirstName" name="FirstName" maxlength="50" size="30" onblur="validateField(name)" value="<?php echo $name; ?>"/>
                <span id="FirstNameError"></span>
                <br/>
                <label for="Surname">Surname</label><br/>
                <input class="reg-input" type="text" required id="Surname" name="Surname" maxlength="50" size="30" onblur="validateField(name)" value="<?php echo $surname; ?>"/>
                <span id="SurnameError"></span>
                <br/>
                <label for="Affil">Affiliation</label><br/>
                <input class="reg-input" type="text" required id="Affil" name="Affil" maxlength="100" size="50" onblur="validateField(name)" value="<?php echo $affil; ?>"/>
                <span id="AffilError"></span>
                <br/>
                <label for="Email">Email</label><br/>
                <input class="reg-input" type="email" required id="Email" name="Email" size="50" onblur="validateEmail(name)" value="<?php echo $email; ?>"/>
                <span id="EmailError"></span>
                <br/>
                <label for="EmailConfirm">Confirm Email</label><br/>
                <input class="reg-input" type="email" required id="EmailConfirm" name="EmailConfirm" size="50" onblur="confirmEmail(name)" value="<?php echo $emailconfirm; ?>"/>
                <span id="EmailConfirmError"></span>
                <br/>
                <label for="BotTest">Anti-spam mechanism <br/> Please type the name of the city hosting Astronomdagarna 2015</label><br/>
                <input class="reg-input" type="text" required id="BotTest" name="BotTest" maxlength="30" size="30" onblur="botTest(name)" value="<?php echo $bottest; ?>"/>
                <span id="BotTestError"></span>
                <br/>
                <br/>
            	
            	
            	<!-- BANQUET DINNER SECTION -->
            	<h2><i class="fa fa-coffee"></i> Conference Meals</h2>
                
                <label>Do you have any dietary restrictions?</label><br/><br/>
                <input type="radio" id="DietRestrictions_Yes" name="DietRestrictions" value="Yes" <?php radio('DietRestrictions', array('No','Yes'), 'Yes');?>/> <label>&nbsp;Yes</label>
            	<input type="radio" id="DietRestrictions_No"  name="DietRestrictions" value="No" <?php  radio('DietRestrictions', array('No','Yes'), 'No'); ?>/> <label>&nbsp;No</label>
            	<br/><br/>
            	
                <label for="DietRestrictions_Details">If yes, please describe them:</label><br/><br/>
                <textarea name="DietRestrictions_Details" id="DietRestrictions_Details" rows="15" style="width: 90%"><?php echo $diet_details; ?></textarea>
                <br/><br/><br/>
                
            	<label>Conference banquet (150 SEK)</label><br/><br/>
            	<input type="radio" id="AtBanquet_Attending" name="AtBanquet" value="Attending" <?php radio('AtBanquet', array('Not Attending', 'Attending'), 'Attending');?>/> <label>&nbsp;Attending</label>
            	<input type="radio" id="AtBanquet_NotAttending" name="AtBanquet" value="Not Attending" <?php radio('AtBanquet', array('Not Attending', 'Attending'), 'Not Attending');?>/> <label>&nbsp;Not Attending</label>
            	<br/><br/><br/>
                
                <label>Friday lunch</label><br/><br/>
            	<input type="radio" id="FridayLunch_Attending" name="FridayLunch" value="Attending" <?php radio('FridayLunch', array('Not Attending', 'Attending'), 'Attending');?>/> <label>&nbsp;Attending</label>
            	<input type="radio" id="FridayLunch_NotAttending" name="FridayLunch" value="Not Attending" <?php radio('FridayLunch', array('Not Attending', 'Attending'), 'Not Attending');?>/> <label>&nbsp;Not Attending</label>
            	<br/><br/><br/>
                
            </div>
	        
	        <br/>
            <br/>
            <input type="reset" class="button"> <input type="submit" class="button" id="preview" name="submit" value="Preview"/>
<?php 
if (submitted()) {
	if (validateform($message)) {
		echo '            <input type="submit" class="button" id="submit" name="Submit" value="Submit" style="float: right; margin-top: 3px;" />'."\n";
// 		echo '<pre id="errormsg" style="visibility: hidden;"></pre>';
	} else {
		echo "            <pre id='errormsg' class='reg-input' style='clear: both; margin-top: 3em; border: 3px solid rgb(200, 32, 30);'>$message</pre>";
	} 
// } else {
// 	echo '<pre id="errormsg" style="visibility: hidden;"></pre>';
}
?>
        </form>
      </div>

      
<?php
// echo "<pre>"; ob_start(); 
// 	echo "GET: "; var_dump($_GET); echo ""; echo "POST: "; var_dump($_POST); echo "okfields: "; var_dump($okfields); 
// 	$str = ob_get_contents(); ob_end_clean(); echo htmlspecialchars($str, ENT_QUOTES); echo "</pre>"; 
?>
    
    </body>
</html>
