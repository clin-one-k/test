<?php
/* allow user to change date and dollar amount without 
 * reflash the page
 */
use_helper(
    'evMultiLangText',
    'paManagementComm',
    'paManagementProject',
    'paManagementEvent',
    'paManagementWorktimeList'
);?>

<style>
input[type="text"]{
    width:75px;
}
.targetTable{
    border-color:gray;
    border-spacing:4px;
    border: 1px solid;
    padding:5px 5px 5px 5px;
    }
th{
  font-weight: bold;
  text-align:center;
  border: 1px solid;
}
td{
  border: 1px solid;
}
.targetTime{
    color:#00B7EE;
    cursor:pointer;
    cursor:hand;
}
.right{
    text-align:right;
}
</style>
<script>
/*** base prototype object ***/
function projectUpdateObject(){}

/* flag to control the user's click, if true, onBlur work, if false
   disable onblur */
projectUpdateObject.prototype.clickable = true;

/* save the originalHTML, might need to put the HTML back if user
   input cancel the input or put wrong value */
projectUpdateObject.prototype.originalHTML = "";

/* project id */
projectUpdateObject.prototype.pid = null;

/* <TD> element object, parent of tdText */
projectUpdateObject.prototype.td = null;

/* <TD> element HTML */
projectUpdateObject.prototype.tdText = "";

/* the result of vaild the new value, true or false */
projectUpdateObject.prototype.valueVerifyResult = null;

/* when user remove mouse from input and click on other location */
projectUpdateObject.prototype.input_onblur = function(){
    if(this.clickable==false){
        return;
    }
    this.check_and_submit_new_value();
}

/* after the alert() function, resume the click back */
projectUpdateObject.prototype.resumeClick = function(){
    this.clickable = true;
}

/* roll back HTML, user cancelled or put a wrong value */
projectUpdateObject.prototype.rollBackHTML = function(){
    this.td.innerHTML="";
    this.td.innerHTML=this.originalHTML;
}

/* if user use "ENTER" key to terminate the input, check and send 
   Ajax */
projectUpdateObject.prototype.searchKeyPress = function(e){
    if(this.clickable==false){
        return;
    }
    if (typeof e == 'undefined' && window.event) { e = window.event; }
    if (e.keyCode == 13){
            this.check_and_submit_new_value();
    }
}

/* roll back original HTML when user cancel or put invalid input */
projectUpdateObject.prototype.setOriginalHTML = function(){
    this.originalHTML = this.td.innerHTML;
}

/* set project id */
projectUpdateObject.prototype.setPid = function(pid){
    this.pid = pid;
}

/* when a alert() pops out, need to disable click and return key event
   or it will duplicate the Ajax sending */
projectUpdateObject.prototype.stopClick = function(){
    this.clickable = false;
}

/*** object for target amount update ***/
var amountChange = new projectUpdateObject();
amountChange.idPrefix = "amount";
amountChange.originalAmount = "";
amountChange.newAmount = "",
amountChange.setOriginalAmount = function(amount){
    this.originalAmount = amount;
}
amountChange.clickToChangeAmount = function(pid,amount){
    this.setPid(pid);
    this.setOriginalAmount(amount);
    this.tdText = document.getElementById(this.idPrefix+this.pid);
    this.td = this.tdText.parentNode;
    this.setOriginalHTML();
    this.updateHTML();
}

amountChange.input_onblur = function(){
    if(this.clickable==false){
        return;
    }
    this.check_and_submit_new_value();
}
amountChange.check_and_submit_new_value = function(){
    input = document.getElementById('targetAmount');
        
    this.newAmount = input.value;
    if(this.newAmount == this.originalAmount){
        this.placeNewAmount();
        return;
    }
    this.checkAmount(input.value);
    if(this.amountVerifyResult === false){
        this.stopClick();
        alert(this.newAmount+" is not vaild dollar amount, please try again.");
        this.placeLastAmount();
        this.resumeClick();
    }else{
        this.saveByAjax();
    }
}
amountChange.placeNewAmount = function(){
    this.td.innerHTML = ""; //clear first to avoid Chrom DOM Exception 8 error
    this.td.innerHTML = "<span id=\""+this.idPrefix+this.pid+"\" class=\"targetTime\" onclick=\"amountChange.clickToChangeAmount("+this.pid+",'"+this.newAmount+"')\">$ "+number_format(this.newAmount)+"</span>";
}
amountChange.checkAmount = function(txtAmount){
    if(isNumber(txtAmount)){
        this.valueVerifyResult = true;
    }else{
        this.valueVerifyResult = false;
        
    }
    return;
}

amountChange.updateHTML = function(){
    this.td.innerHTML = "<input id='targetAmount' class='right' type='text' size='12' onblur='amountChange.input_onblur();' onkeypress='amountChange.searchKeyPress(event);' value='"+this.originalAmount+"'/>";
    input = document.getElementById("targetAmount");
    input.focus(); 
}

amountChange.saveByAjax = function(){
    var xmlhttp;
    if(window.XMLHttpRequest){
        xmlhttp=new XMLHttpRequest();
    }else{
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function(){
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
            var jsonObj=JSON.parse(xmlhttp.getResponseHeader("X-JSON"));
            amountChange.stopClick();
            if(jsonObj.result == "false"){
                amountChange.ajaxResult=false;
                alert("<?php echo ev_text("FailSaving");?>");
                amountChange.rollBackHTML();
            }else{
                amountChange.ajaxResult=true;
                alert("<?php echo ev_text("SettingSaved");?>");
                amountChange.placeNewAmount();
                
            }
            amountChange.resumeClick();
        }

    }
    var params=
        "pid="+this.pid+
        "&amount="+this.newAmount;
    xmlhttp.open("POST","projChk/updateTargetAmountAjax",true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send(params);
}

/*** function for target date update ***/
var dateChange = new projectUpdateObject();

dateChange.date = "";
dateChange.newDate = "";
dateChange.originalDate = "";
dateChange.day = "";
dateChange.month = "";
dateChange.year = "";
dateChange.clickToChangeDate = function(pid,date){
    this.setPid(pid);
    this.setOriginalDate(date);
    this.tdText=document.getElementById("td"+this.pid);
    this.td=this.tdText.parentNode;
    this.setOriginalHTML();
    this.updateHTML();
}

dateChange.updateHTML = function(){
    this.td.innerHTML="<input class='right' id='targetDate' type='text' size='12' onblur='dateChange.input_onblur();' onkeypress='dateChange.searchKeyPress(event);' value='"+this.originalDate+"'/>";
    input=document.getElementById("targetDate");
    input.focus();
    
}
dateChange.setOriginalDate = function(date){
    this.originalDate=date;
}

dateChange.check_and_submit_new_value = function(){
    input = document.getElementById('targetDate');
    this.newDate = input.value;
    if(this.newDate == this.originalDate){
        this.placeNewDate();
        return;
    }
    this.checkDate(input.value);
    if(this.valueVerifyResult === false){
        this.stopClick();
        alert(this.newDate+" is not vaild date, please try again.");
        this.placeLastDate();
        this.resumeClick();
    }else{
        this.saveByAjax();
    }
}
dateChange.saveByAjax = function(){
    var xmlhttp;
    if(window.XMLHttpRequest){
        xmlhttp = new XMLHttpRequest();
    }else{
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function(){
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
            var jsonObj = JSON.parse(xmlhttp.getResponseHeader("X-JSON"));
            dateChange.stopClick();
            if(jsonObj.result == "false"){
                dateChange.ajaxResult=false;
                alert("<?php echo ev_text("FailSaving");?>");
                dateChange.rollBackHTML();
            }else{
                dateChange.ajaxResult=true;
                alert("<?php echo ev_text("SettingSaved");?>");
                dateChange.placeNewDate();
                
            }
            dateChange.resumeClick();
        }

    }
    var params=
        "pid="+this.pid+
        "&day="+this.day+
        "&month="+this.month+
        "&year="+this.year;
    xmlhttp.open("POST","projChk/updateTargetDateAjax",true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send(params);
}
dateChange.placeNewDate = function(){
    this.td.innerHTML = ""; //clear first to avoid Chrom DOM Exception 8 error
    this.td.innerHTML = "<span id=\"td"+this.pid+"\" class=\"targetTime\" onclick=\"dateChange.clickToChangeDate("+this.pid+",'"+this.newDate+"')\">"+this.newDate+"</span>";
}

dateChange.generateNewDateString = function(){
    this.newDate=
        this.year+'/'+
        ('0' + this.month).slice(-2)+'/'+
        ('0' + this.day).slice(-2);
}
dateChange.checkDate = function(txtDate) {
    var aoDate,           // needed for creating array and object
        ms,               // date in milliseconds
        month, day, year; // (integer) month, day and year
    // if separator is not defined then set '/'
    separator = '/';

    // split input date to month, day and year
    aoDate = txtDate.split(separator);
    // array length should be exactly 3 (no more no less)
    if (aoDate.length !== 3) {
        this.dateVerifyResult=false;
        return;
    }
    // define month, day and year from array (expected format is yyyy/m/d)
    // subtraction will cast variables to integer implicitly
    month = aoDate[1] - 1; // because months in JS start from 0
    day = aoDate[2] - 0;
    year = aoDate[0] - 0;
    // test year range
    if (year < 1000 || year > 3000) {
        return false;
    }
    // convert input date to milliseconds
    ms = (new Date(year, month, day)).getTime();
    // initialize Date() object from milliseconds (reuse aoDate variable)
    aoDate = new Date();
    aoDate.setTime(ms);
    // compare input date and parts from Date() object
    // if difference exists then input date is not valid
    if (aoDate.getFullYear() !== year ||
        aoDate.getMonth() !== month ||
        aoDate.getDate() !== day) {
        this.valueVerifyResult=false;
        return;
    }
    this.tempDateObj = aoDate;
    this.day = day;
    this.year = year;
    this.month = (month+1);
    this.generateNewDateString();
    this.valueVerifyResult=true;
    
    // date is OK, return true
    return;
}

</script>
<div class="container" style="margin-top:10px;">
<div class="projects">
<?php //pageHeader(ev_text('project_management'), $login_user_name); ?>
</div>
<h1><?php echo ev_text("Sales Project Target Expiration Check");?></h1>
<form action="<?php echo url_for('projChk/index')?>" method="GET">
    <select name="range">
        <option value="lastmonth" <? if ($rangeType=="lastmonth"):?>SELECTED<?php endif;?>><?php echo ev_text("last_month")?></option>
        <option value="last3month" <? if ($rangeType=="last3month"):?>SELECTED<?php endif;?>><?php echo ev_text("last_3_month")?></option>
        <option value="last12month" <? if ($rangeType=="last12month"):?>SELECTED<?php endif;?>><?php echo ev_text("last12month");?></option>
    </select>
    <input type="submit" class="btn btn-primary" value="<?php echo ev_text("search")?>"/>
</form>
<?php echo ev_text("range");?>: <b><?php echo $startDate?></b> to <b><?php echo $endDate?></b>
<table class="targetTable">
    <tr style="background: #EEEEEE;">
        <th><?php echo ev_text("id")?></th>
        <th><?php echo ev_text("proj_code");?></th>
        <th><?Php echo ev_text("name");?></th>
        <th><?Php echo ev_text("description");?></th>
        <th><?Php echo ev_text("cust_name");?></th>
        <th><?Php echo ev_text("partner_name");?></th>
        <th><?Php echo ev_text("category");?></th>
        <th><?Php echo ev_text("type");?></th>
        <th><?Php echo ev_text("department");?></th>
        <th><?Php echo ev_text("sales_manager");?></th>
        <th><?Php echo ev_text("proj_manager");?></th>
        <th style="width:90px"><?Php echo ev_text("target");?></th>
        <th style="width:90px"><?Php echo ev_text("budget");?></th>
    </tr>
<?php if(is_array($r)):?>
<? foreach($r AS $row):?>
<tr>
<td><?php echo $row['id']?></td>
<td><?php echo $row['projcode']?></td>
<td><?php echo $row['name']?></td>
<td><?php echo $row['description']?></td>
<td><?php echo $row['cust_name']?></td>
<td><?php echo $row['partner_name']?></td>
<td><?php echo $row['category_name']?></td>
<td><?php echo $row['type_name']?></td>
<td><?php echo $row['dept_name']?></td>
<td><?php echo $row['sales_manager']?></td>
<td><?php echo $row['proj_manager']?></td>
<td class="right"><span id="td<?php echo $row['id']?>" class="targetTime" onclick="dateChange.clickToChangeDate('<?php echo $row['id']?>','<?php echo $row['target_date'];?>')"><?php echo $row['target_date'];?></span></td>
<td class="right"><span value="<?php echo $row['proj_target'];?>" id="amount<?php echo $row['id']?>" class="targetTime" onclick="amountChange.clickToChangeAmount('<?php echo $row['id']?>','<?php echo $row['proj_target'];?>')">$ <?php echo number_format($row['proj_target']);?></span></td>
</tr>
<?php endforeach;?>
<?php endif;?>
</table>
<script>
function number_format (number, decimals, dec_point, thousands_sep) {
  // http://kevin.vanzonneveld.net
  // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +     bugfix by: Michael White (http://getsprink.com)
  // +     bugfix by: Benjamin Lupton
  // +     bugfix by: Allan Jensen (http://www.winternet.no)
  // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
  // +     bugfix by: Howard Yeend
  // +    revised by: Luke Smith (http://lucassmith.name)
  // +     bugfix by: Diogo Resende
  // +     bugfix by: Rival
  // +      input by: Kheang Hok Chin (http://www.distantia.ca/)
  // +   improved by: davook
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +      input by: Jay Klehr
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +      input by: Amir Habibi (http://www.residence-mixte.com/)
  // +     bugfix by: Brett Zamir (http://brett-zamir.me)
  // +   improved by: Theriault
  // +      input by: Amirouche
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // *     example 1: number_format(1234.56);
  // *     returns 1: '1,235'
  // *     example 2: number_format(1234.56, 2, ',', ' ');
  // *     returns 2: '1 234,56'
  // *     example 3: number_format(1234.5678, 2, '.', '');
  // *     returns 3: '1234.57'
  // *     example 4: number_format(67, 2, ',', '.');
  // *     returns 4: '67,00'
  // *     example 5: number_format(1000);
  // *     returns 5: '1,000'
  // *     example 6: number_format(67.311, 2);
  // *     returns 6: '67.31'
  // *     example 7: number_format(1000.55, 1);
  // *     returns 7: '1,000.6'
  // *     example 8: number_format(67000, 5, ',', '.');
  // *     returns 8: '67.000,00000'
  // *     example 9: number_format(0.9, 0);
  // *     returns 9: '1'
  // *    example 10: number_format('1.20', 2);
  // *    returns 10: '1.20'
  // *    example 11: number_format('1.20', 4);
  // *    returns 11: '1.2000'
  // *    example 12: number_format('1.2000', 3);
  // *    returns 12: '1.200'
  // *    example 13: number_format('1 000,50', 2, '.', ' ');
  // *    returns 13: '100 050.00'
  // Strip all characters but numerical ones.
  number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function (n, prec) {
      var k = Math.pow(10, prec);
      return '' + Math.round(n * k) / k;
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }
  return s.join(dec);
}
function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}
</script>

