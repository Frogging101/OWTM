<!--
    Copyright (C) 2013 OHRI 

    This file is part of OWTG.

    OWTG is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OWTG is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OWTG.  If not, see <http://www.gnu.org/licenses/>.
-->

<html>
<head>
    <title>OWTG</title>
    <style type="text/css">
    table{
        border-collapse:collapse;
        border:1px solid black;
    }th,td{
        border: 1px solid black;
        padding: 4px;
    }
    th{
        font-size: 0.8em;
    }
    input.alarms{
        width: 4em;
        margin-right:auto;
        margin-left:auto;
        text-align:center;
        display:block;
    }
    input.alias{
        width: 12em;
    }
    iframe{
        padding:0px;
        border:0px;
        margin:0;
    }
    span.warning{
        color: red;
        font-size: 2em;
    }
    span.bottomtext{
        font-size:0.7em;
        text-align:center;
        display:block;
    }
    </style>
    <script>
    function loadGraphs(address){
        //If the graph is already loaded, clicking again should hide it
        var parent = document.getElementById(address)
        if(parent.getElementsByTagName('iframe')[0] != null){
            parent.removeChild(parent.getElementsByTagName('iframe')[0]);
            parent.removeChild(parent.getElementsByTagName('br')[0]);
            return;
        }
        
        var lineBreak = document.createElement('br');
        var frame=document.createElement('iframe');
        frame.src = 'showgraphs.php?address='+address;
        frame.width = 10;
        frame.height = 10;
        document.getElementById(address).appendChild(lineBreak);
        document.getElementById(address).appendChild(frame);
        setInterval(function(){
            var curHeight = 0;
            if(frame.contentWindow != null){
                curHeight = frame.contentWindow.document.body.scrollHeight;
            
                //Hacky way of determining when the thing is fully loaded; the height will increase
                if(curHeight > 10){
                    //When it is done loading, set the frame's height to cover the graphs
                    frame.height = curHeight;
                    frame.width = frame.contentWindow.document.body.scrollWidth;
                    frame.contentWindow.document.body.style.padding = 0;
                    frame.contentWindow.document.body.style.margin = 0;

                    clearInterval();
                }
            }else{
                clearInterval();
            }
        },5);
    }
    
    function showAll(){
        //TODO
    }
    </script>
    <!--<meta http-equiv="refresh" content="16">-->
</head>
<body>
<h1>OWTG - One-Wire Temperature Grapher</h1>
<h2>All Sensors Megagraph</h2>
<?php
include "settings.php";
if(!file_exists($adbFilename) || !file_exists($gdbFilename)){
    echo "<span class=\"warning\">WARNING: Database file(s) do not exist or cannot be found. \
    Run rrdgen.py in ".$etcDir." to create them.</span>";
}
$_GET['address'] = 'all';
include "showgraphs.php";
?>

<h2>Individual Sensors and Configuration Area</h2>
<?php
include "Sensor.php";
include "/opt/owfs/share/php/OWNet/ownet.php";

$ow = new OWNet($adapter);
#If owdir returns null for the root directory, then owserver is not running. In this
#case, don't bother with anything for the most part.
if(@$ow->dir("/") == null)
    $noOW = True;

function isAddressOnline($address){
	global $ow;
    global $noOW;
    if(!$noOW){
        #Get directory listing, separate into an array
        $directoryArray = explode(",",$ow->dir("/")["data"]);
        foreach ($directoryArray as $currentDir){
            #We don't want to include this directory, 
            #as although it has a "temperature" file, it is not a device
            if($currentDir == "/simultaneous")
                continue;
            if($ow->read($currentDir."/temperature") != NULL){
                if($ow->read($currentDir."/address") == $address)
                    return True;
            }
        }
    }
    return False;
}

function getSensors(){
    global $sensorsFile;
    global $ow;
    global $noOW;
    
    $sensorArray = array();
    $fileArray = file($sensorsFile,FILE_IGNORE_NEW_LINES); #Put sensors file into array
    foreach($fileArray as $line){
        if($line[0] == '#') #If the line has # as the first character, ignore it
            continue;
        $discoveredArray = explode(":",$line); #Separate the parameters into an array
        $newSensor = new Sensor(); #New sensor object
        #The following assigns array indices to their respective values
        #based on the predetermined format of the sensors file
        $newSensor->alias = $discoveredArray[0];
        $newSensor->address = $discoveredArray[1];
        $newSensor->timestamp = intval($discoveredArray[2]);
        if($discoveredArray[3] == 'y')
            $newSensor->graph = True;
        else
            $newSensor->graph = False;
        $newSensor->minAlarm = floatval($discoveredArray[4]);
        $newSensor->maxAlarm = floatval($discoveredArray[5]);
        if(!$noOW)
            $newSensor->temperature = $ow->read("/".$newSensor->address."/temperature");
        else
            $newSensor->temperature = 'N/A';
        $newSensor->online = isAddressOnline($newSensor->address);
        
        $sensorArray[] = $newSensor;
    }
    return $sensorArray;
}

#The section below constructs a table with a row for each sensor. Understanding it is
#easier if you look at the HTML output.
echo "<table>\n";
$i=1;
echo "<th>Device Address</th><th>Alias</th><th>Discovery<br>Date</th><th>Temp.</th><th>Online</th>";
echo "<th>Min.<br>Alarm</th><th>Max.<br>Alarm</th><th>Graph?</th><th>Modify</th>\n";
foreach(getSensors() as $curSensor){
    $online = 'No'; #String to describe online status
    $checked = ''; #If this is set to "checked", then the checkbox will be checked
    if($curSensor->online == True)
        $online = 'Yes';
    if($curSensor->graph == True)
        $checked = ' checked';
    if($i%2 == 0)
        echo "<tr style=\"background-color:lightgrey;\">\n";
    else
        echo "<tr>\n";
    echo "<td id=\"".$curSensor->address."\" width=490px><a href=\"javascript:loadGraphs('"
        .$curSensor->address."');\">".$curSensor->address."</a></td>\n";
    echo "<form name=\"form".$i."\" action=\"update_sensor.php\" method=\"get\">\n";
    echo "<td><input name=\"alias\" type=\"text\" value=\"".$curSensor->alias."\" class=\"alias\"></td>\n";
    echo "<td>".date("d M Y H:i:s T",$curSensor->timestamp)."</td>\n";
    echo "<td>".$curSensor->temperature."</td>\n";
    echo "<td>".$online."</td>\n";
    echo "<input type=\"hidden\" name=\"address\" value=\"".$curSensor->address."\" />\n";
    echo "<td><input name=\"minAlarm\" type=\"number\" value=\"".$curSensor->minAlarm."\" class=\"alarms\"></td>";
    echo "<td><input name=\"maxAlarm\" type=\"number\" value=\"".$curSensor->maxAlarm."\" class=\"alarms\"></td>";
    echo "<td><input name=\"graph\" type=\"checkbox\" value=\"graph\" ".$checked."></td>\n";
    echo "<td><input type=\"submit\" value=\"Modify\"></td></form>\n"; 
    echo "</tr>\n";
    $i++;
}
echo "</table>\n";
unset($ow);

$outputArray = array();
#Get the currently set email address from datGet in the owtg python module
exec("cd ".$etcDir."../bin;python -c \"import owtg;print owtg.datGet('email')\"",$outputArray);
echo "<br>\n";
echo "<form name=\"alertemail\" action=\"update_email.php\" method=\"get\">";
echo "<b>Alert email address: </b><input name=\"email\" type=\"email\" value=\"".$outputArray[0]."\">\n";
echo "<input type=\"submit\" value=\"Modify\">";
echo "</form>";

?>
<br>
<span class="bottomtext"><a href="https://github.com/Frogging101/OWTG">OWTG on GitHub</a><br>
&#169; 2013 OHRI | Created by John Brooks<br><br>

<a href=http://www.gnu.org/licenses/gpl.html><img src="images/gplv3-88x31.png" alt="Licensed under GPLv3"></a></span>
</span>
</body>
</html>
