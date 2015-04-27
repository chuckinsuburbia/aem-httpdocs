<?php 
$basePath=$_SERVER['DOCUMENT_ROOT'];
require_once($basePath."/conf/config.php");
require_once($basePath.'/lib/functions.php'); 

$debug=false;

if(isset($_GET['action']) && $_GET['action']=="about") {
	echo("<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN' 'http://www.w3.org/TR/html4/strict.dtd'>");
	echo("<html><head><title>About - ".$_GET['alertId']."</title>");
	echo("<link href='css/default.css' rel='stylesheet' type='text/css'></head>");
	echo("<body>");
	$alertTokens=getAlertTokens($_GET['alertId'],"name");
	echo("<table><tr><th colspan=2>Details</th></tr>");
	echo("<tr><td class='emph'>AEM Alert ID</td><td>".$_GET['alertId']."</td></tr>");
	echo("<tr><td class='emph'>IM #</td><td>".$alertTokens['sc_incident_id']."</td></tr>");
	unset($alertTokens['sc_incident_id']);
	echo("<tr><td class='emph'>Severity</td><td>".$alertTokens['aem_severity']."</td></tr>");
	unset($alertTokens['aem_severity']);
	echo("<tr><td class='emph'>Text</td><td>".$alertTokens['text']."</td></tr>");
	unset($alertTokens['text']);
	if(!isset($alertTokens['comment'])) $alertTokens['comment'] = '';
	echo("<tr><td class='emph'>Comments</td><td><pre>".$alertTokens['comment']."</pre></td></tr>");
	unset($alertTokens['comment']);
	echo("<tr><th colspan=2>Other Tokens</th></tr>");
	foreach($alertTokens as $k => $v) {
		echo("<tr><td class='emph'>".$k."</td><td>".$v."</td></tr>");
	}
	echo("</table>");
	die('</body></html>');
}

$refresh = "";

if(isset($_GET['action']) && $_GET['action']=="close" && $adminUser){
	$rc = closeAlert($_GET['alertId']);
	if ($rc == 0){
		$refresh.="<script language='Javascript'> alert ('Alert ".$_GET['alertId']." closed.') </script>";
	}else{
		$refresh.="<script language='Javascript'> alert ('Failed to close Alert ".$_GET['alertId'].".') </script>";
	}
	$refresh.="<script language='Javascript'> window.location='".$_SERVER['PHP_SELF']."'</script>";
} 

$qs = isset($_SERVER['QUERY_STRING']) ? explode('&',$_SERVER['QUERY_STRING']) : array();
foreach($qs as $var){
	if(substr($var,0,6) != "action" && substr($var,0,7) != "alertId")
		$queryString = $var;
}
if(!empty($queryString)) $queryString = "?".$queryString;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php echo $refresh ?>
<title>AEM AAD</title>
<link href='css/default.css' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="/css/tablesorter/blue/style.css" type="text/css" media="print, projection, screen" />
<script type="text/javascript" language="JavaScript1.2" src="./menu/stmenu.js"></script>
<script type="text/javascript" language="JavaScript1.2" src="./menu/pem.js"></script>
<script type="text/javascript" src="./lib/jquery-1.11.0.min.js"></script>

<script language="javascript">
	function checkReloading() {
		var reload = document.getElementById('reloadCB');
		if (reload.checked) {
			timer = setTimeout(function() {
				document.getElementById('mainForm').submit();
			}, 15000);
		} else {
			if (typeof timer != 'undefined') { clearTimeout(timer); }
		}
	}
	$(document).ready(function(){ checkReloading(); });

	//alert("<?php echo $adminUser ? "true" : "false"; ?>");
	actionUrl="<?php echo $_SERVER['PHP_SELF'] ?>";
	function popComments(alertId){
		day = new Date();
		id = day.getTime();
		eval("page" + id + " = window.open('<?php echo $_SERVER['PHP_SELF'] ?>?action=about&alertId=" + alertId + "', '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=600,left = 276,top = 132');");
	}

	function popIM(IMticket){
		day = new Date();
		id = day.getTime();
		eval("page" + id + " = window.open('http://controlm/bip/sc.php?im=" + IMticket + "', '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=600,left = 276,top = 132');");
	}

	function popLogin(){
		document.getElementById('login').style.visibility = "visible";
		document.getElementById('login').style.display = "block";
	}

	lastSelected="";
	lastSev="";

	function clearMsg(){
		document.getElementById('msg').innerHTML = "";
	}

	function menuCmd(func){
		hideMenu('actions');
		switch(func){
			case 'close':
				day = new Date();
				id = day.getTime();
				window.location = "<?php echo $_SERVER['PHP_SELF'] ?>?action=close&alertId=" + alertId;
				break;
			case 'about':
				day = new Date();
				id = day.getTime();
				eval("page" + id + " = window.open('<?php echo $_SERVER['PHP_SELF'] ?>?action=about&alertId=" + alertId + "', '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=600,left = 276,top = 132');");
				break;
			case 'sc':
				day = new Date();
				id = day.getTime();
				eval("page" + id + " = window.open('translateLookup.php?stepname=ServiceContact&alertId=" + alertId + "', '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=600,left = 276,top = 132');");
				break;
			case 'tt':
				day = new Date();
				id = day.getTime();
				eval("page" + id + " = window.open('translateLookup.php?stepname=TextTranslation&alertId=" + alertId + "', '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=600,left = 276,top = 132');");
				break;
			case 'so':
				day = new Date();
				id = day.getTime();
				eval("page" + id + " = window.open('translateLookup.php?stepname=SeverityOverride&alertId=" + alertId + "', '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=600,left = 276,top = 132');");
				break;
			case 'bo':
				day = new Date();
				id = day.getTime();
				eval("page" + id + " = window.open('translateLookup.php?stepname=blackout&alertId=" + alertId + "', '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=800,height=600,left = 276,top = 132');");
				break;
			default:
				alert('Unknown function');
		}
	}

	function popMenu(row){
		if(lastSelected == row.id){
			var last=document.getElementById(lastSelected);
			last.className = lastSev;
			hideMenu('actions');
			lastSelected="";
			lastSev="";
			return;
		}
		if(lastSelected != ""){
			var last=document.getElementById(lastSelected);
			last.className = lastSev;
		}
		lastSelected = row.id;
		lastSev = row.className;
		row.className = "selected";
		alertId=row.id;
		hideMenu('actions');showFloatMenuAt('actions',xValue,yValue);
	}

	var xValue=0;
	var yValue=0;
	var isIE = document.all?true:false;
	if (!isIE) document.captureEvents(Event.MOUSEMOVE);
	document.onmousemove = getMousePosition;

	function getMousePosition(e) {
		var _x;
		var _y;
		if (!isIE) {
			_x = e.pageX;
			_y = e.pageY;
		}
		if (isIE) {
			var st=0;
			var sl=0;
			if (document.documentElement && !document.documentElement.scrollTop){
				// IE6 +4.01 but no scrolling going on
				st=0;
			}else if (document.documentElement && document.documentElement.scrollTop){
				// IE6 +4.01 and user has scrolled
				st=document.documentElement.scrollTop;
			}else if (document.body && document.body.scrollTop){
				// IE5 or DTD 3.2
				st=document.body.scrollTop;
			}
			if (document.documentElement && !document.documentElement.scrollLeft){
				// IE6 +4.01 but no scrolling going on
				sl=0;
			}else if (document.documentElement && document.documentElement.scrollLeft){
				// IE6 +4.01 and user has scrolled
				sl=document.documentElement.scrollLeft;
			}else if (document.body && document.body.scrollLeft){
				// IE5 or DTD 3.2
				sl=document.body.scrollLeft;
			}
			_x = event.clientX + sl;
			_y = event.clientY + st;
		}
		xValue=_x;
		yValue=_y;
		return true;
	}

	//Browser Support Code
	function updateAlerts(){
		var ajaxRequest;  // The variable that makes Ajax possible!
		try{
			// Opera 8.0+, Firefox, Safari
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
			// Internet Explorer Browsers
			try{
				ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try{
					ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e){
					// Something went wrong
					alert("Your browser broke!");
					return false;
				}
			}
		}
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4){
				document.getElementById('alertList').innerHTML = ajaxRequest.responseText;
				window.setTimeout(updateAlerts, 30000);
			}
		}
		ajaxRequest.open("GET", "<?php echo $_SERVER['PHP_SELF'] ?>?update=true&<?php echo $_SERVER['QUERY_STRING'] ?>", true);
		ajaxRequest.send(null); 
	}

	function login(){
		clearMsg();
		var ajaxRequest;  // The variable that makes Ajax possible!
		try{
			// Opera 8.0+, Firefox, Safari
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
			// Internet Explorer Browsers
			try{
				ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try{
					ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e){
					// Something went wrong
					alert("Your browser broke!");
					return false;
				}
			}
		}
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4){
				if(ajaxRequest.responseText == 'SUCCESS'){
					document.getElementById('guest').style.visibility = "hidden";
					document.getElementById('guest').style.display = "none";
					document.getElementById('admin').style.visibility = 'visible';
					document.getElementById('admin').style.display = 'block';
					//document.getElementById('msg').innerHTML = ajaxRequest.responseText;
					tb_remove();
					updateAlerts();
				}else{
					document.getElementById('msg').innerHTML = ajaxRequest.responseText;
					tb_remove();
				}
			}
		}
		ajaxRequest.open("GET", "<?php echo $_SERVER['PHP_SELF'] ?>?username="+document.getElementById('username').value+"&password="+document.getElementById('password').value, true);
		ajaxRequest.send(null); 
	}

	function logout(){
		var ajaxRequest;  // The variable that makes Ajax possible!
		try{
			// Opera 8.0+, Firefox, Safari
			ajaxRequest = new XMLHttpRequest();
		} catch (e){
			// Internet Explorer Browsers
			try{
				ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try{
					ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e){
					// Something went wrong
					alert("Your browser broke!");
					return false;
				}
			}
		}
		// Create a function that will receive data sent from the server
		ajaxRequest.onreadystatechange = function(){
			if(ajaxRequest.readyState == 4){
				document.getElementById('admin').style.visibility = 'hidden';
				document.getElementById('admin').style.display = 'none';
				document.getElementById('guest').style.visibility = 'visible';
				document.getElementById('guest').style.display = 'block';
				document.getElementById('password').value="";
				updateAlerts();
			}
		}
		ajaxRequest.open("GET", "<?php echo $_SERVER['PHP_SELF'] ?>?action=logout", true);
		ajaxRequest.send(null); 
	}
</script>

<?php htmlHead(); ?>

<script language="javascript">
$(document).ready(function()
    {
        $("#myTable").tablesorter();
    }
);
</script>

</head>

<body>

<?php // load top of page and links from function
	topOpg();
?>

<br />

<div style="font-size:10px;text-align:left">
	(updated <?php echo date("m/d/Y H:i:s") ?>)
	<a href="javascript:mainForm.submit();"><img src="images/refresh.png" width="16" height="16" border="0"/></a>
	<form id='mainForm' action='<?php echo($_SERVER['PHP_SELF']); ?>' method='post'>
		<label>
			<input type='checkbox' onclick='checkReloading();' id='reloadCB' name='reloadCB' <?php if(isset($_REQUEST['reloadCB']) && $_REQUEST['reloadCB'] == 'on') { echo(" checked"); } ?> />
			Auto Refresh
		</label>
		&nbsp;-&nbsp;
		<label>
			<input type='checkbox' onclick='this.form.submit();' id='filterSS' name='filterSS' <?php if(isset($_REQUEST['filterSS']) && $_REQUEST['filterSS'] == 'on') { echo(" checked"); } ?> />
			Hide Store Support
		</label>
		&nbsp;-&nbsp;
		<label>
                        <input type='checkbox' onclick='this.form.submit();' id='filterAP' name='filterAP' <?php if(isset($_REQUEST['filterAP']) && $_REQUEST['filterAP'] == 'on') { echo(" checked"); } ?> />
                        Hide Asset Protection
                </label>
		&nbsp;-&nbsp;
		<label>
                        <input type='checkbox' onclick='this.form.submit();' id='filterLogo' name='filterLogo' <?php if(isset($_REQUEST['filterLogo']) && $_REQUEST['filterLogo'] == 'on') { echo(" checked"); } ?> />
                        Hide Logo
                </label>
	</form>
</div>
<table id="myTable" class="tablesorter">
  <thead>
  <tr>
    <th><?php if(isset($adminUser) && $adminUser){ ?>
      <a href="javascript:logout();"><img src="images/unlock.gif" alt="login" width="20" height="20" border="0" /></a>
      <?php }else{ ?>
      <a href="javascript:popLogin();"><img src="images/lock.gif" alt="login" width="20" height="20" border="0" /></a>
      <?php } ?></th>
    <th>#</th>
    <th>Alert</th>
    <th>IM</th>
    <th>Time</th>
    <th>Contact</th>
    <th>Text</th>
  </tr>
  </thead><tbody>
  <?php 
function checkFilter($filter,$fields){
	return true;
	# $filter = array("fieldName", "operator", "value");	
	#fields should be 
	$fieldName["currentOperator"] = 0;
	$fieldName["alertId"] = 1;   
	$fieldName["CaseNo"] = 2;    
	$fieldName["OSISeverity"] = 3;
	$fieldName["timeReceived"] = 4;
	$fieldName["_service"] = 5;  
	$fieldName["text"] = 6;      

	if(!is_array($filter)) return true;
	$ret = true;
	foreach($filter as $f){
		eval("\$ret = trim(\$fields[".$fieldName[$f[0]]."]) ".$f[1]." '".$f[2]."' ? true : false;");
		if(!$ret) break;
	}
	return $ret;
}

$aad = getActiveAlerts();
if(!isset($_REQUEST['filter'])){
	# $filter = array("fieldName", "operator", "value");
#	$filter[] = array("_service", "!=", "StoreHelp");
#	$filter[] = array("_service", "==", "CCDgrp");
#	$filter[] = array("_service", "!=", "OprSup");
$filter="";
}
foreach($aad as $fields) {
	if(isset($_REQUEST['filterSS']) && $_REQUEST['filterSS'] == 'on' && preg_match("/STORE SUPPORT - PEM/",$fields['service'])) continue;
	if(isset($_REQUEST['filterAP']) && $_REQUEST['filterAP'] == 'on' && preg_match("/ASSET PROTECTION SUPPORT/",$fields['service'])) continue;
	//$fields[4] = substr($fields[4],0,strrpos($fields[4],":")).substr($fields[4],strlen($fields[4])-2);
	//$fields = split('\|', $alert);
	if(checkFilter($filter,$fields)) {
		if(!isset($fields['sc_incident_id'])) {
			$fields['sc_incident_id'] ='Pending';
		}
		if(isset($fields['ap_incident_created']) && trim($fields['ap_incident_created']) != "NULL" ) {
			if(trim($fields['service']) == "CCDgrp"||trim($fields['service']) == "NetServ") {
				$image="bb/ok.gif";
				$title = "Alert sent to NOC";
			} elseif(trim($fields['aa_ack_time']) != "NULL" && !empty($fields['aa_ack_time'])) {
				$image="bb/ok.gif";
				$title = "Acknowledged at: ".trim($fields['aa_ack_time']);
			} else {
				$image = "ap.gif";
				$title = "NOT ACKNOWLEDGED YET!";
			}
		} else {
			$image = "comments.gif";
			$title = "Not Sent to Alarmpoint!";
		}
		$popMenu = (isset($adminUser) && $adminUser) ? "onClick=\"popMenu(this)\";" : "";
		if(isset($_GET['mode']) && $_GET['mode'] == 'mini') {
			$fulltext=ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\" target=\"_blank\">[link]</a>", trim($fields['text']));
			$text=$fulltext;
			if(strpos($text,"a href") === false) {
				if(strlen($text) > 44) {
					$text = substr($text,0,41)."...";
				}
			} else {
				preg_match_all("/<a href.+<\/a>/",$text,$links);
				$txt = str_replace($links[0],"[link]",$text);
				$fulltext=$txt;
				if(strlen($txt) > 44) {
					if(substr($txt, strlen(trim($txt))-6) == "[link]") {
						$txt = substr($txt,0,34)."... [link]";
					} else {
						$txt = substr($text,0,41)."...";
					}
					foreach($links[0] as $link) {
						$replink[] = "[link]";
					}
					$text = str_replace($replink,$links[0],$txt);
				}
			}
			print "<tr id=\"".$fields['aa_id']."\" class=\"sev".trim($fields['aem_severity'])."\" ".$popMenu."><td width=\"12\"><a href=\"javascript:popComments(".$fields['aa_id'].");\"><img src=\"images/$image\" border=\"0\" width=\"15\" height=\"15\" title=\"$title\"></a></td><td title=\"IM:&nbsp;&nbsp;".trim($fields['sc_incident_id'])."&nbsp;&nbsp;Time:&nbsp;&nbsp;".date("m/d H:i", strtotime(trim($fields['aa_received_time'])))."&nbsp;&nbsp;Contact:&nbsp;&nbsp;".trim($fields['service'])."&nbsp;&nbsp;Contact:&nbsp;&nbsp;".$fulltext."\">".$text."</td></tr>\n";
		} else {
			print "<tr id=\"".$fields['aa_id']."\" class=\"sev".trim($fields['aem_severity'])."\" ".$popMenu."><td><a href=\"javascript:popComments(".$fields['aa_id'].");\"><img src=\"images/$image\" border=\"0\" width=\"20\" height=\"20\" title=\"$title\"></a></td><td align=\"center\">".trim($fields['aa_count'])."</td><td>".trim($fields['aa_id'])."</td><td><a target='_blank' href='https://bip.aptea.com/service_manager/view_incident/".trim($fields['sc_incident_id'])."'>".trim($fields['sc_incident_id'])."</a>&nbsp;&nbsp;</td><td>".date("m/d&\\nb\sp;H:i", strtotime(trim($fields['aa_received_time'])))."&nbsp;&nbsp;</td><td>".trim($fields['service'])."&nbsp;&nbsp;</td><td align=\"left\">".preg_replace('"\b(http[s]*://\S+)"', '<a href="$1" target=\"_blank\">$1</a>', trim($fields['text']))."</td></tr>";
		}//end if mini
	}//end if checkFilter
}

?>
</tbody></table>
<?php bottomOPg(); ?>
