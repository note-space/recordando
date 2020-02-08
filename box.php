<?php
//  Project: note_space (https://github.com/note-space/note_space)
//  Version: 2020-02-02 (last updated)
//  Summary: a program to organize and edit a collection of notes, with a journal and an in-text calculator
//  Copyright (C) 2020, Thomas J Hyde .. residing in Wilmington DE US (tomhyde2@gmail.com)
//  This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
//  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

note_space();

function note_space() {
ini_set('default_charset','UTF-8');
date_default_timezone_set('America/New_York') ;
ini_set('display_errors', 1);
// the .db file has the same name as this .php file .. so, making additional collections is easy
$file = str_replace('.php','.db',$_SERVER['PHP_SELF']) ;
$file = str_replace('/app','',$file) ;
$db_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . $file ;
if ( !file_exists($db_file) ): die("the database file '". $db_file ."' is missing") ;  endif;
$GLOBALS['db'] = new PDO('sqlite:'. $db_file ) ;
$GLOBALS['db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
run_sql("PRAGMA journal_mode = OFF");
$pCode = 'grid_screen' ;
if ( array_key_exists('pCode',$_POST) ): $pCode = $_POST['pCode']; endif;
if ( 'no' == get_note_opt(0,'skip_pw') ): check_password() ; endif;
call_user_func($pCode) ;
}

function check_password() {
if ( !array_key_exists('one',$_POST) ): enter_pw('') ; endif;
if ( trim($_POST['one']) != trim(get_note_opt(0,'pw')) ): enter_pw('pw not a match') ; endif;
}

function enter_pw($mess) {
echo "<html><head><title>hola</title></head><body><form action='". $_SERVER['PHP_SELF'] ."' method='POST' >" ;
echo "<br><textarea cols=10 rows=1 name='one' ></textarea><br><br>" ;
echo "<input type='submit' value='enter' ><br><br>". $mess ."</form></body></html>" ;  
die() ;
}

function grid_screen() {
page_header() ;  
$grid_num = 1 ;
if ( isset($_POST['grid_num']) ): $grid_num = $_POST['grid_num'] ; endif;
echo "<table style='margin-right:auto; margin-left:12px; margin-top:12px' >\n" ; 
$subgrid_cols = get_note_opt($grid_num,'subgrid_cols') ;
if ( intval($subgrid_cols) < 1 ): $subgrid_cols = 3 ; endif;
for ( $this_col=1; $this_col<=$subgrid_cols; $this_col++ ):
 echo "<td valign='top' ><table>" ;
 one_col_stack($grid_num,$this_col) ;
 echo "</table></td>" ;
endfor;
$records = num_rows("t_note WHERE parent_grid = ". $grid_num) ;
if ( $records == 0 ): echo "<td>this array is empty, for now</td>" ; endif;
echo "<td valign='top' >" ;
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $grid_num) ;
if ( $_POST['moves'] == 0 ):
 echo "<div id='new_menu' style='display: none' >" ;
 if ( $grid_num > 1 ): 
  echo button('set_grid('. $row['parent_grid'] .')','go up','js') ;
 else:
  echo "(top)" ;
 endif;
 echo"<br><br>" ; 
 $js = "document.getElementById(\"this_putdown\").value = ". $this_col ." ; set_process(\"stack_add\") ; " ;
 echo button($js,'add card','js') ."<br><br><br>" ;
  echo button('journal_screen','open journal') ."<br><br><br>" ;
  $js  = "document.getElementById(\"search_div\").style.display = \"block\"; " ;
  $js .= "document.getElementById(\"start_search\").style.display = \"none\"; " ; 
  echo "<span id='start_search' >". button($js,'open search','js') ."</span>" ; 
  echo "<div id='search_div' style='display: none ' ><input type='text' name='search_str' size=20 value='". get_note_opt(0,'search') ."' ><br>" ;
 echo button('start_search','search') ."</div><br><br><br>" ; 
 echo button('set_moves(1)','start move','js') ."<br><br><br><br><br><br><br>" ; 
 array_menu($grid_num) ;
 $js  = "document.getElementById(\"new_menu\").style.display = \"none\" ; " ;
 $js .= "document.oncontextmenu = function(evt) { " ;
 $js .= "document.getElementById(\"new_menu\").style.display = \"block\"; document.oncontextmenu = null; return false; } " ;
 echo "<br><br>". button($js,'hide menu','js') ."<br><br><br>" ; 
 echo "</div>" ;
else:
 echo "<br>click on a<br>card to start<br><br>&nbsp;". button('set_moves(0)','stop moving','js') ;  
 echo "<br><br>a yellow border indicates <br>the card has been selected to be moved<br>" ;
 echo "<br>after you select a card,<br>click on one of the 'here' buttons,<br>to move the card to that spot" ;
endif;
echo "</td></tr>\n" ;
echo "</table></form></body></html>" ;
}

function array_menu($grid_num) {
$js  = "document.getElementById(\"portal_div\").style.display = \"block\"; " ;
$js .= "document.getElementById(\"button_div\").style.display = \"none\"; " ;
echo "<div id='button_div' >". button($js,'portal options','js') ."</div>" ;  
echo "<div id='portal_div' style='display: none; text-align: left' ><br>" ;
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $grid_num ) ;
echo "<br><input type='hidden' name='portal_id' value=". $grid_num ." >\n" ;
echo my_word('portal title') .":<br><input type='text' name='this_text' size=16 value='". $row['this_text'] ."' ><br>" ;
$count = num_rows("t_note WHERE parent_grid = ". $grid_num ) ;
if ( $count < 1 ):
 $str  = button('change_to_card','change') ." back to a card<br>" ; 
 $str .= "<input type='hidden' id='confirmDelete' >" ;
 $str .= button("delete_one(". $this_id .",'delete_portal')",'delete','js') ."<span id='deleteWarn' ></span>" . sp(5) ;
else:
 $str  = "<br>this array has ". $count ." card" ;  if ( $count > 1 ): $str .= "s" ; endif;
 $str .= "<br>it cannot change back to a card<br><br>" ;
endif;
if ( $grid_num == 1 ): $str = "" ; endif;
echo $str . my_word('columns') .": " ;
$arr = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20) ;
select_input('subgrid_cols',$arr,$grid_num) ;
echo my_word('portal color') .":<br>" ;
color_select($grid_num) ;
echo "<br>". button('save_portal','save') ."<br><br>" ;
echo "</div>" ;
echo "<br><br>" ;  
if ( $grid_num == 1 ): echo button('options_list_screen','program options') ."<br><br>version 2020-02-02" ; endif;
}

function save_portal() {
$sql = "UPDATE t_note SET this_text = ? WHERE this_id = ". $_POST['grid_num'] ;
$arr = array() ;  $arr[] = db_str($_POST['this_text']) ; 
run_sql($sql,$arr) ;
$subgrid_cols = intval($_POST['subgrid_cols']) ;
if ( $subgrid_cols < 1 ): $subgrid_cols = 3 ; endif;
set_note_opt($_POST['grid_num'],'subgrid_cols',$subgrid_cols) ;
save_color($_POST['grid_num']) ;
grid_screen() ;
}

function one_col_stack($grid_num,$this_col) {
$count = 0 ; $next = 1 ;  // for the last move block 
$sqlA = "SELECT * FROM t_note WHERE parent_grid = ". $grid_num ." AND " ;
$result = select_sql($sqlA ."card_status = 0 AND this_col = ". $this_col ." ORDER BY this_row") ;
while ( $row = $result->fetch() ):
 if ( $_POST['moves'] == 1 ):
  $str = $next++ .'|'. $this_col .'|0' ; // 0 = active
  stack_gap($this_col,$str,'') ;
 endif;
 echo "<tr>" ;
 note_cell($row) ;
 echo "</tr>" ;
 $count++ ;
endwhile;
if ( $_POST['moves'] == 1 ): // spot at bottom of active cards
 $str = $next++ .'|'. $this_col .'|0' ;   // 0 = in-active
 stack_gap($this_col,$str,'') ;
endif;
echo "<tr><td><hr style='width: 90%; height: 3px; border: none; background-color: SteelBlue ; color: SteelBlue' ></td></tr>\n" ;
$next-- ; // de-increment counter for gap at top of in-active area
$result = select_sql($sqlA ."card_status = 1 AND this_col = ". $this_col ." ORDER BY this_row") ;
while ( $row = $result->fetch() ):
 if ( $_POST['moves'] == 1 ):
  $str = $next++ .'|'. $this_col .'|1' ;  // 1 = in-active
  stack_gap($this_col,$str,'') ;
 endif;  
 echo "<tr>" ;
 note_cell($row,'inactive') ; 
 echo "</tr>" ;
 $count++ ;
endwhile;
if ( $count == 0 ):
 echo "<tr><td style='color: #999999' >empty<br>column</td></tr>" ;
endif;
if ( $_POST['moves'] == 1 ): // spot at bottom of in-active cards
 $str = $next++ .'|'. $this_col .'|1' ;  // 1 = in-active
 stack_gap($this_col,$str,'') ;
endif;
}

function note_cell($row,$opt='') { 
$border = "border-style: solid ; border-width: 1px ; border-color: #999999 " ;
$color = get_note_opt($row['this_id'],'card_color') ; 
if ( strlen($color) < 2 ): $color = '#CCCCCC' ; endif;
if ( 'inactive' == $opt ): $color = "#DDDDDD ; color: #555555; " ; endif; // grey type for inactive cells
$highlight = set_highlight($row) ;
$has_subgrid = 0 ;
if ( get_note_opt($row['this_id'],'has_subgrid') == 1 ): $has_subgrid = 1 ; endif;
if ( $has_subgrid ): $clicks = subgrid_clicks($row['this_id']) ; else: $clicks = notecell_clicks($row['this_id']) ; endif;
echo "<td style='background: ". $color ."; ". $border ."; cursor: pointer; ' ". $clicks ." >" ;
echo "<div". $highlight ."><div class='grid_cell' >" ;
if ( $has_subgrid ): echo "<span style='background-color: #FFFFFF' >&nbsp; <i>portal</i> &nbsp;</span><br>" ; endif;
$str = substr(strip_tags($row['this_text']),0,256) ;
echo str_replace("\n", "<br>", $str) ."</div></div></td>\n" ;
}

function my_word($word) { return $word ; }  // this could allow for other languages, later

function page_header($ajax='') {
echo "<!DOCTYPE PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' >\n" ;   
echo "<html><head>\n<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' >\n" ;
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=yes' >\n" ;
$title = 'note_space' ;  
if ( isset($_POST['grid_num']) ):
 $row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $_POST['grid_num']) ;
 if ( strlen($row['this_text']) > 1 ): $title = strip_tags($row['this_text']) ; endif;
endif;
echo "<title>". $title ."</title>\n" ;
echo_css() ;
echo_javascript($ajax) ;
echo "</head><body><form action='". $_SERVER['PHP_SELF'] ."' method='POST' name='formA' onsubmit='return false' >\n" ;
echo_hidden() ;
}

function echo_css() {
$cell_height = get_note_opt(0,'list_card_height') ; 
$cell_width = get_note_opt(0,'list_card_width') ;  
$color = get_note_opt(0,'text_color') ; 
$back = get_note_opt(0,'background_color') ;
$pt = intval(get_note_opt(0,'text_font_size')) ;  if ( $pt < 8 ): $pt = 8 ; endif;
$lead = $pt + 2 ;
$base = "font-family: ". get_note_opt(0,'text_font') .", sans-serif; font-size: ". $pt ."pt; line-height: ". $lead ."pt; " ; 
$text  = "font-family: ". get_note_opt(0,'textarea_font') .", monospace; " ;
$text .= "font-size: ". get_note_opt(0,'textarea_font_size') ."pt; " ;
$text .= "color: ". get_note_opt(0,'textarea_text_color') ."; background-color: ". get_note_opt(0,'textarea_back_color') ."; " ;
echo "<style type='text/css'>
BODY { ". $base ." margin:0; background:#ebeef1 }
.container { position:absolute; top:30px; right:50px; bottom:40px; left:20px }
TEXTAREA { ". $text ."margin:0; padding:20px; overflow-y:auto; resize:none; width:100%; height:100%; min-height:100%; -webkit-box-sizing:border-box; -moz-box-sizing:border-box; box-sizing: border-box; border:1px #ddd solid; outline:none }
TABLE { ". $base ." color: ". $color ."; background-color: ". $back ."; margin: 0 auto; }
SELECT { font-size: ". ($pt - 1) ."pt }
.grid_cell { overflow: hidden; width: ". $cell_width ."px; height: ". $cell_height ."px; }
</style>\n" ;
}

function echo_hidden() {
echo "<input type='hidden' id='pCode' name='pCode' > " ;
if ( !isset($_POST['this_id']) ): $_POST['this_id'] = 0 ; endif;
echo "<input type='hidden' id='this_id' name='this_id' value=". $_POST['this_id'] ." >\n" ;
if ( !isset($_POST['grid_num']) ): $_POST['grid_num'] = 1 ; endif;
echo "<input type='hidden' id='grid_num' name='grid_num' value=". $_POST['grid_num'] ." > " ;
if ( !isset($_POST['moves']) ): $_POST['moves'] = 0 ; endif;
echo "<input type='hidden' id='moves' name='moves' value=". $_POST['moves'] ." >\n" ;
if ( !isset($_POST['this_pickup']) ): $_POST['this_pickup'] = 0 ; endif;
if ( $_POST['grid_num'] == $_POST['this_pickup'] ): $_POST['this_pickup'] = 0 ; endif; //prevent move cell into own grid
if ( $_POST['moves'] == 0 ): $_POST['this_pickup'] = 0 ; endif; // to clear ..
echo "<input type='hidden' id='this_pickup' name='this_pickup' value=". $_POST['this_pickup'] ." > " ;
if ( !isset($_POST['last_edit']) ): $_POST['last_edit'] = -1 ; endif;
echo "<input type='hidden' name='last_edit' id='last_edit' value=". $_POST['last_edit']." >\n" ;
echo "<input type='hidden' id='this_col' name='this_col' > " ;
echo "<input type='hidden' id='this_row' name='this_row' >\n" ;
echo "<input type='hidden' id='this_putdown' name='this_putdown' > " ;
echo "<input type='hidden' id='confirmDeleteOther' name='confirmDeleteOther' >\n" ;
echo "<input type='hidden' name='one' value='" ;
if ( isset($_POST['one']) ): echo $_POST['one'] ; endif;
echo "' > " ; // holds pw
}

function set_highlight($row) {
$str = '' ;
if ( $_POST['last_edit'] == $row['this_id'] ):
 $str = "border-style: solid ; border-width: 3px ; border-color: green " ;
endif;
if ( $_POST['moves'] == 1 ):
 if ( $_POST['this_pickup'] == $row['this_id'] ):
  $str = "border-style: solid ; border-width: 5px ; border-color: yellow " ;
 endif;
endif;
if ( strlen($str) > 2 ): $str = " style ='". $str ."' " ; endif;
return $str ;
}

function echo_javascript($opt='') {
echo "<script type='text/javascript' >
function set_process(thisOne) {
 document.getElementById('pCode').value = thisOne ; document.formA.submit(); }
function open_edit(this_id) {
 document.getElementById('this_id').value = this_id ; document.getElementById('pCode').value = 'open_edit' ; document.formA.submit(); }
function set_moves(thisOne) {
 document.getElementById('moves').value = thisOne ; document.getElementById('pCode').value = 'grid_screen' ; document.formA.submit(); }
function pick_up(this_id) {
 document.getElementById('this_pickup').value = this_id ; document.getElementById('pCode').value = 'grid_screen' ; document.formA.submit(); }
function put_down(this_row,this_col) {
 document.getElementById('this_row').value = this_row ; document.getElementById('this_col').value = this_col ;
 document.getElementById('pCode').value = 'put_down' ; document.formA.submit(); }
function set_grid(this_id) {
 document.getElementById('last_edit').value = document.getElementById('grid_num').value ;
 document.getElementById('grid_num').value = this_id ; document.getElementById('pCode').value = 'grid_screen' ; document.formA.submit(); }
function calendar_day(this_date) {
 document.getElementById('this_date').value = this_date ; document.getElementById('pCode').value = 'open_calendar_cell' ; document.formA.submit(); } 
function scroll_up(this_many) {
 document.getElementById('calendar_move').value = this_many ; document.getElementById('pCode').value = 'move_calendar_up' ; document.formA.submit(); }
function scroll_down(this_many) {
 document.getElementById('calendar_move').value = this_many ; document.getElementById('pCode').value = 'move_calendar_down' ; document.formA.submit(); }   
function delete_one(this_id,this_funct) {
 if ( document.getElementById('confirmDelete').value == this_id ) {
 document.getElementById('pCode').value = this_funct ; document.formA.submit(); } else {
 document.getElementById('confirmDelete').value = this_id ; 
 document.getElementById('deleteWarn').innerHTML = ' <- ". my_word('again to confirm delete') ."' ; } }
function change_to_portal(this_id) {
 if ( document.getElementById('confirmPortal').value == this_id ) {
 document.getElementById('pCode').value = 'change_to_portal' ; document.formA.submit(); } else {
 document.getElementById('confirmPortal').value = this_id ; 
 document.getElementById('portalWarn').innerHTML = ' <- ". my_word('again to confirm: change card to portal') ."' ; } }
document.oncontextmenu = function(evt) {
 document.getElementById('new_menu').style.display = 'block'; document.oncontextmenu = null; return false; }
" ;  // oncontextmenu event is used to trap right click .. null returns it to normal after first right click ..
if ( 'ajax' == $opt ): // only needed for the text edit screen
echo "function ajax_save() {
 var xhttp = new XMLHttpRequest();  xhttp.onreadystatechange = function() {
 if (xhttp.readyState == 4 && xhttp.status == 200) { document.getElementById('ajax_mess').innerHTML = xhttp.responseText; } } ;
 xhttp.open('POST', '". basename(__FILE__) ."', true);
 xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded') ;
 var str0 = '&txt=' + encodeURIComponent(document.getElementById('this_text').value) ;
 var str1 = 'pCode=ajax_save&id=' + document.getElementById('this_id').value + str0 ; xhttp.send(str1); }
setInterval(ajax_save, 15000) ;\n" ; // saving text every 15 seconds ..
endif;  
echo "</script>\n" ;
}

function delete_note() { 
run_sql("DELETE FROM t_note WHERE this_id = ". intval($_POST['this_id'])) ;
grid_screen() ;
}

function delete_portal() { 
$id = intval($_POST['grid_num']) ;
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $id) ;
$_POST['grid_num'] = $row['parent_grid'] ; 
run_sql("DELETE FROM t_note WHERE this_id = ". $id) ;
grid_screen() ;
}

function put_down() {
$arr = array() ;
$arr[] = intval($_POST['grid_num']) ;  $arr[] = intval($_POST['this_row']) ;
$arr[] = intval($_POST['this_col']) ;  $arr[] = intval($_POST['this_pickup']) ;
run_sql("UPDATE t_note SET parent_grid = ? , this_row = ? , this_col = ? WHERE this_id = ? ",$arr) ;
grid_screen() ;
}

function save_note() {
$now = new DateTime() ;
$sql  = "UPDATE t_note SET this_text = ?, datetime_edited = '". $now->format('Y-m-d H:i:s') ."' " ;
$sql .= "WHERE this_id = ". intval($_POST['this_id']) ;
$arr = array() ;
$arr[] = db_str($_POST['this_text']) ; 
run_sql($sql,$arr) ;
save_color($_POST['this_id']) ;
}

function save_color($note_id) {
$pick = '' ;
if ( isset($_POST['color_select']) ): $pick = $_POST['color_select'] ; endif;
$set = num_rows("t_note_opt WHERE note_id = ". $note_id ." AND this_opt = 'card_color'" ) ;
if ( strlen($pick) < 2 Or '#CCCCCC' == $pick ):
 if ( $set > 0 ): run_sql("DELETE FROM t_note_opt WHERE note_id = ". $note_id ." AND this_opt = 'card_color'") ; endif;
else:
 if ( $set < 1 ): run_sql("INSERT INTO t_note_opt ( note_id, this_opt ) VALUES ( ". $note_id .", 'card_color' )") ; endif;
 set_note_opt($note_id, 'card_color', $pick) ;
endif;
}

function change_to_portal() {
save_note() ;
set_note_opt($_POST['this_id'],'has_subgrid',1) ;
set_note_opt($_POST['this_id'],'subgrid_cols',4) ; 
grid_screen() ;
}

function change_to_card() {
save_note() ;
set_note_opt($_POST['this_id'],'has_subgrid',0) ;
$_POST['grid_num'] = 1 ;
grid_screen() ;
}

function subgrid_clicks($id) {
$this_pickup = $_POST['this_pickup'] ;
$title = my_word('click to open array') ;
$click = "set_grid(". $id .");" ;
if ( $_POST['moves'] == 1 ):
 if ( $this_pickup > 0 ):
  if ( $this_pickup == $id ):
   $title = my_word('this array is selected') ;  $onclick = "" ;
  else: // if there's a pick-up (and it's not this array) .. an array can only be opened
   $title = my_word('click to open array') ;  $click = "set_grid(". $id .");" ;
  endif;
 else:
  $title = my_word('click to select this array') ;  $click = "pick_up(". $id .");" ;
 endif;
endif;
return "title='". $title ."' onclick='". $click ."' " ;
}

function notecell_clicks($id) {
$title = my_word('click to edit') ;  $click = "open_edit(". $id .");" ;
if ( $_POST['moves'] == 1 ):
 if ( $_POST['this_pickup']  == $id ):
  $title = my_word('this card is selected') ;  $click = "" ;
 else:
  $title = my_word('click to select this card') ;  $click = "pick_up(". $id .");" ;
 endif;
endif;
return "title='". $title ."' onclick='". $click ."' " ;
}

function options_list_screen() {
page_header() ;
echo "<div style='padding: 24px'>" ;
echo "<br>". button('save_options','save') ."<br><br><div id='menu2' >\n" ;
one_option_input('list_card_width',4) ; echo "<br>" ;
one_option_input('list_card_height',4) ; echo "<br><br>" ;
one_option_input('pw',12) ; 
echo "&nbsp; &nbsp; ". my_word('skip password ') ;
$arr = array('no','yes');
select_input('skip_pw',$arr) ;
echo "</div>" ;
font_menu() ;  
echo "<br><br><br>number format: " ;
$arr = array('anglo','euro');
select_input('number_format',$arr) ;
echo "<br><br><br>" ; 
echo button('show_code','show PHP code') ;
echo "</div></form></body></html>" ;
}

function one_option_input($opt,$size=24) {
echo $opt .": <input type='text' name='". $opt ."' size=". $size ." value='". get_note_opt(0,$opt) ."' >\n" ;
}

function font_menu() {
echo "<br>" ;
one_option_input('text_font') ; echo "<br>" ;
one_option_input('text_font_size',4) ; echo "<br><br>" ;
one_option_input('text_color') ; echo "<br>" ;
one_option_input('background_color') ; echo "<br><br>" ;
one_option_input('textarea_font') ;  echo "<br>" ;
one_option_input('textarea_font_size',4) ; echo "<br><br>" ;
one_option_input('textarea_text_color') ; echo "<br>" ;
one_option_input('textarea_back_color') ; echo "<br><br>" ;
}

function save_options() {
if ( isset($_POST['text_font']) ): set_note_opt(0,'text_font',$_POST['text_font']) ; endif;
if ( isset($_POST['text_font_size']) ): set_note_opt(0,'text_font_size',$_POST['text_font_size']) ; endif;
if ( isset($_POST['background_color']) ): set_note_opt(0,'background_color',$_POST['background_color']) ; endif;
if ( isset($_POST['textarea_font']) ): set_note_opt(0,'textarea_font',$_POST['textarea_font']) ; endif;
if ( isset($_POST['textarea_font_size']) ): set_note_opt(0,'textarea_font_size',$_POST['textarea_font_size']) ; endif;
if ( isset($_POST['textarea_text_color']) ): set_note_opt(0,'textarea_text_color',$_POST['textarea_text_color']) ; endif;
if ( isset($_POST['textarea_back_color']) ): set_note_opt(0,'textarea_back_color',$_POST['textarea_back_color']) ; endif;
if ( isset($_POST['list_card_height']) ): set_note_opt(0,'list_card_height',$_POST['list_card_height']) ; endif;
if ( isset($_POST['list_card_width']) ): set_note_opt(0,'list_card_width',$_POST['list_card_width']) ; endif;
if ( isset($_POST['pw']) ): set_note_opt(0,'pw',$_POST['pw']) ; endif;
if ( isset($_POST['skip_pw']) ): set_note_opt(0,'skip_pw',$_POST['skip_pw']) ; endif;
if ( isset($_POST['number_format']) ): set_note_opt(0,'number_format',$_POST['number_format']) ; endif;
grid_screen() ;	
}

function open_edit() { edit_note('') ; }

function edit_note($opt='',$date='') {
$this_id = intval($_POST['this_id']) ;
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $this_id ) ;
$_POST['last_edit'] = $this_id ;
page_header('ajax') ;
echo "<div class='container' >" ;
echo "<div id='new_menu' style='display: none' >" ;
note_menu($this_id,$opt,$date) ;
echo "</div>" ;
echo "<textarea id='this_text' name='this_text' >". input_str($row['this_text']) ;
echo "</textarea><br><span id='ajax_mess'></span></div></form></body></html>" ;
}

function note_menu($this_id,$opt='',$date='') {
$back = button('save_note_and_close','close card') ; 
if ( 'calendar' == $opt ): 
 echo $date . sp(5) ;
 $back = button('save_note_and_calendar','close day') ; 
endif;
if ( isset($_POST['searching']) ): $back = button('save_and_search','close card') ; endif;
echo $back . sp(5) ;
echo button('save_note_and_calc','calc') . sp(5) ;
$js  = "document.getElementById(\"new_menu\").style.display = \"none\" ; " ;
$js .= "document.oncontextmenu = function(evt) { " ;
$js .= "document.getElementById(\"new_menu\").style.display = \"block\"; document.oncontextmenu = null; return false; } " ;
echo button($js,'hide menu','js') . sp(5) ;
echo "<input type='hidden' id='confirmDelete' >" ;
echo button("delete_one(". $this_id .",'delete_note')",'delete','js') ."<span id='deleteWarn' ></span>" . sp(5) ;
echo button('print_note','print') . sp(5) ;
if ( 'calendar' != $opt ): 
 echo button('change_to_portal('. $this_id .')','change to portal','js') ;
 echo "<span id='portalWarn' > </span><input type='hidden' id='confirmPortal' >". sp(5) ."card color: " ;
 color_select($this_id) ;
endif;
echo "<br><br>\n" ;
}

function color_select($this_id) {
$this_color = get_note_opt($this_id,'card_color') ; 
$arr = array() ; $arr[] = 'lightgray' ;  $arr[] = 'lightsalmon' ;  $arr[] = 'lavender' ;  
$arr[] = 'lightyellow' ;  $arr[] = 'paleturquoise' ; $arr[] = 'palegreen' ;  $arr[] = 'ivory' ;
echo "<select name='color_select' >\n" ;;
foreach ( $arr as $color ):
 echo "<option style='background-color: ". $color ."' " ;
 if ( $color == $this_color ): echo "selected " ; endif;  
 echo " value='". $color ."' >". $color ."</option>\n" ;
endforeach;
echo "</select>\n" ;
}

function save_note_and_close() { save_note() ; grid_screen() ; }
function save_note_and_calendar() { save_note() ; journal_screen() ; }
function save_and_search() { save_note() ; search_screen() ; }
function save_note_and_calc() { run_save_note_and_calc() ; }

function stack_gap($this_col,$str,$opt='') {
if ( 'bottom' == $opt ):
 $sql  = "SELECT MAX(this_row) AS bottom FROM t_note WHERE parent_grid = ". $_POST['grid_num'] ;
 $sql .= " AND this_col = ". $this_col ." AND card_status = 0" ;
 $row = select_one_row($sql) ;
 $str = ( $row['bottom'] + 1 ) .'|'. $this_col .'|1' ;
endif;
$js = "document.getElementById(\"this_putdown\").value = \"". $str ."\" ; set_process(\"stack_move\") ; " ;
echo "<tr><td>" . sp(6) . button($js,'here','js') ."</td></tr>\n" ;
}

function stack_move() {
$grid_num = intval($_POST['grid_num']) ;
if ( isset($_POST['this_pickup']) ):
 $this_id = intval($_POST['this_pickup']) ;
 list($this_row,$this_col,$inactive) = explode('|', $_POST['this_putdown']) ;
 $new_row_num = $this_row ;
 $new_row_num++ ;
 $sql  = "SELECT * FROM t_note WHERE parent_grid = ". $grid_num ." AND this_row >= ". $this_row ;
 $sql .= " AND this_col = ". $this_col ." ORDER BY card_status, this_row" ;
 $result = select_sql($sql) ;
 while ( $row = $result->fetch() ):
  run_sql("UPDATE t_note SET this_row = ". $new_row_num++ ." WHERE this_id = ". intval($row['this_id'])) ;
 endwhile;
 $sql  = "UPDATE t_note SET this_row = ". $this_row .", this_col = ". $this_col .", parent_grid = ". $grid_num ;
 $sql .= ", card_status = ". $inactive ." WHERE this_id = ". $this_id ; // set parent, in case move is from another array
 run_sql($sql) ;
 stack_renumber_all($grid_num) ;
endif;
grid_screen() ;
}

function stack_renumber_all($grid_num) {  // go back and re-number all the columns, to close up the gap, wherever it is
$subgrid_cols = get_note_opt($grid_num,'subgrid_cols') ;
for ( $this_col=1; $this_col<=$subgrid_cols; $this_col++ ):
 $new_row_num = 1 ;
 $sql = "SELECT * FROM t_note WHERE parent_grid = ". $_POST['grid_num'] ." AND this_col = ". $this_col ." ORDER BY this_row" ;
 $result = select_sql($sql) ;
 while ( $row = $result->fetch() ):
  run_sql("UPDATE t_note SET this_row = ". $new_row_num++ ." WHERE this_id = ". intval($row['this_id'])) ;
 endwhile;
endfor;
}

function stack_add() {
$this_col = 1 ; // always col 1 , for now 
$new_row_num = 2 ;
// re-order the column, new note at top, others pushed down
$sql  = "SELECT * FROM t_note WHERE parent_grid = ". $_POST['grid_num'] ." AND this_col = ". $this_col ;
$sql .= " ORDER BY card_status, this_row" ;
$result = select_sql($sql) ;
while ( $row = $result->fetch() ):
 run_sql("UPDATE t_note SET this_row = ". $new_row_num++ ." WHERE this_id = ". intval($row['this_id'])) ;
endwhile;
$now = new DateTime() ;
$arr = array() ;
$arr[] = intval($_POST['grid_num']) ;
$arr[] = $this_col ;
$arr[] = $now->format('Y-m-d H:i:s') ;
$sql = "INSERT INTO t_note ( parent_grid, this_col, this_row, this_text, datetime_entered, card_status ) VALUES ( ? , ? , 1, '', ?, 0 )";
run_sql($sql,$arr) ;
$latest = select_one_row("SELECT MAX(this_id) AS latest FROM t_note") ;
$_POST['this_id'] = $latest['latest'] ;
edit_note() ;
}

function button($process,$label,$opt='') {	
if ( '' == $opt ): $js = "set_process(\"". $process ."\")" ; else: $js = $process ; endif;
return "<input type='button' onclick='". $js ."' value='" . $label ."' >" ;
}

function sp($num) { return(str_repeat('&nbsp;',$num)); }

function ajax_save() {
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $_POST['id']);
$txt = preg_replace( '/\n/', "\r\n", $_POST['txt'] ) ;
if ( $txt != $row['this_text'] ):
 $now = new DateTime() ;
 $sql = "UPDATE t_note SET this_text = ? , datetime_edited = '". $now->format('Y-m-d H:i:s') ."' WHERE this_id = ". intval($_POST['id']) ;
 $arr = array() ;  $arr[] = db_str($txt) ;
 run_sql($sql,$arr) ;
 echo strlen($txt) .' chars saved, '. $now->format('Y-m-d H:i:s') ;  
endif;
}

function input_str($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8') ; } // going into HTML 
function db_str($str) { return htmlspecialchars_decode($str, ENT_QUOTES) ; }  // going to database 

function select_sql($sql) {
try { $result = $GLOBALS['db']->query($sql) ; }
catch (PDOException $error) { die( "error in:<br>". $sql ."<br>". $error->getMessage() ) ; }
return $result ;
}

function select_one_row($sql) {
try { $result = $GLOBALS['db']->query($sql) ; }
catch (PDOException $error) { die( "error in:<br>". $sql ."<br>". $error->getMessage() ) ; }
$row = $result->fetch() ;
return $row ;
}

function run_sql($sql,$arr=array()) {
$statement = $GLOBALS['db']->prepare($sql) ;
try { $statement->execute($arr) ; }
catch (PDOException $error) { die( $sql ."<br>". $error->getMessage() ) ; }
}

function num_rows($where) {
return $GLOBALS['db']->query("SELECT COUNT(*) FROM ". $where)->fetchColumn() ;
}

function get_note_opt($note_id,$this_opt) {  
$str = '' ;
$count = num_rows("t_note_opt WHERE note_id = ". $note_id ." AND this_opt = '". $this_opt ."'" ) ;
if ( $count > 0 ):
 $row = select_one_row("SELECT * FROM t_note_opt WHERE note_id = ". $note_id ." AND this_opt = '". $this_opt ."'" ) ;
 $str = $row['this_value'] ;
else:
 if ( $note_id == 0 ):
  $str = lookup_default($this_opt) ;
  run_sql("INSERT INTO t_note_opt ( note_id, this_opt, this_value ) VALUES (". $note_id .", '". $this_opt ."', '". $str ."' )" ) ;
 endif;	
endif;
return $str ;
}

function set_note_opt($note_id,$this_opt,$this_value) { 
$count = num_rows("t_note_opt WHERE note_id = ". $note_id ." AND this_opt = '". $this_opt ."'" ) ;
if ( $count < 1 ):
 run_sql("INSERT INTO t_note_opt ( note_id, this_opt ) VALUES (". $note_id .", '". $this_opt ."' )" ) ;
endif;
$arr = array() ;
$arr[] = db_str($this_value) ;
$sql = "UPDATE t_note_opt SET this_value = ? WHERE note_id = ". $note_id ." AND this_opt = '". $this_opt ."'" ;
run_sql($sql,$arr) ;
}

function lookup_default($opt) {
$str = '' ;
switch( $opt ):
 case 'textarea_font' : $str = 'monospace' ; break;  
 case 'text_font' : $str = 'sans-serif' ; break;
 case 'text_font_size' : $str = '10' ; break;
 case 'text_color' : $str = 'black' ; break; 
 case 'background_color' : $str = 'Thistle' ; break; 
 case 'button_font' : $str = 'sans-serif' ; break;
 case 'textarea_text_color' : $str = 'PaleGoldenrod' ; break;
 case 'textarea_back_color' : $str = 'DarkSlateGray' ; break;
 case 'textarea_font_size' : $str = '14' ; break;
 case 'number_format' : $str = 'anglo' ; break;
 case 'list_card_height' : $str = '130' ; break;
 case 'list_card_width' : $str = '190' ; break;
 case 'pw' : $str = '4321' ; break;
 case 'skip_pw' : $str = 'yes' ; break; 
 case 'calendar_num_weeks' : $str = '6' ; break; 
 case 'calendar_first_weekday' : $str = '0' ; break; 
 case 'calendar_top_date' : $str = '2020-02-02' ; break; 
 case 'calendar_lines' : $str = '2' ; break; 
 case 'calendar_chars' : $str = '16' ; break; 
 case 'calendar_move' : $str = '2' ; break;   
 case 'day_before_month' : $str = 'no' ; break;   
endswitch;  
return $str ;
}

function start_search() {
set_note_opt(0,'search',$_POST['search_str']) ;
search_screen() ;
}

function search_screen() {
page_header() ;  
$search_str = get_note_opt(0,'search') ;
echo "<input type='hidden' name='searching' value='on' >" ;
$grid_num = 1 ;
if ( isset($_POST['grid_num']) ): $grid_num = $_POST['grid_num'] ; endif;
echo "<table style='margin-right:auto; margin-left:6px' >\n" ; 
echo "<td valign='top' ><table>" ;
$match = array() ;
$result = select_sql("SELECT * FROM t_note") ; // go through all records ..
while ( $row = $result->fetch() ):
 if ( stripos( $row['this_text'], strval($search_str) ) !== false ): $match[] = $row['this_id'] ; endif;
endwhile;
foreach ( $match as $one ):
 $row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $one) ;
 echo "<tr>" ;
 note_cell($row) ;
 echo "</tr>" ;
endforeach;
echo "</table></td><td>&nbsp; &nbsp; &nbsp; &nbsp;</td><td valign='top' >" ;
echo button('set_grid(1)','up','js') ."<br><br>" ;
echo "<br><input type='text' name='search_str' size=20 value='". get_note_opt(0,'search') ."' ><br><br>" ;
echo button('start_search','search') ."<br><br></td>\n" ;
echo "</table></form></body></html>" ;
}

function print_note() { 
save_note() ; 
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". $_POST['this_id'] ) ;
echo "<html><title>". substr(strip_tags($row['this_text']),0,20) ."</title>\n" ;
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=yes' >\n" ;
echo "<style type='text/css'>\n" ;
echo "BODY { font-family: Tahoma, sans-serif; font-size: 100%; color: #000000; background-color: #FFFFFF } 
TABLE { font-family: Tahoma, sans-serif; font-size: 100%; color: #000000; background-color: #FFFFFF }
@media print { div#top_buttons { display: none} }\n" ; 
echo "</style></head><body><div id='top_buttons' >". sp(12) . button('history.back()','back','js');
echo sp(12) . button('window.print()','print','js') ."<br><br></div>" ;
echo preg_replace( '/\r\n|\r|\n/', '<br>', $row['this_text'] ) ;  // do not strip tags here ..
}

function show_code() { 
$file = dirname(__FILE__) ."/box.php" ;
$line_nums = implode(range(1, count(file($file))), '<br>'); 
$php_code = highlight_file($file, true); 
echo '<style type="text/css">  .num { float: left; color: gray; font-size: 13px; font-family: monospace;
 text-align: right; margin-right: 6pt; padding-right: 6pt; border-right: 1px solid gray;} 
 body { margin: 0px; margin-left: 5px; } td { vertical-align: top; } code { white-space: nowrap; } </style>' ; 
echo "<table><tr><td class='num'>". $line_nums ."</td><td>". $php_code ."</td></tr></table>"; 
} 

function select_input($name,$arr,$id=0) {
$pick = get_note_opt($id,$name) ;
echo "<select size=1 name='". $name ."' >\n" ;
foreach ( $arr as $one ):
 echo "<option value=". $one ;  if ( $pick == $one ): echo " selected"; endif;  echo " >". $one ."</option>" ;
endforeach;
echo "</select><br>\n" ;
}

// start of calendar functions

function months_array() {
$arr = array() ;
$arr[1] = my_word('Jan');  $arr[2] = my_word('Feb');  $arr[3] = my_word('Mar');  $arr[4] = my_word('Apr');
$arr[5] = my_word('May');  $arr[6] = my_word('Jun');  $arr[7] = my_word('Jul');  $arr[8] = my_word('Aug');
$arr[9] = my_word('Sep'); $arr[10] = my_word('Oct'); $arr[11] = my_word('Nov'); $arr[12] = my_word('Dec');
return $arr ;
}

function days_array() {
$arr = array() ;
$arr[0] = my_word('Sun'); $arr[1] = my_word('Mon'); $arr[2] = my_word('Tue');
$arr[3] = my_word('Wed'); $arr[4] = my_word('Thu'); $arr[5] = my_word('Fri'); $arr[6] = my_word('Sat');
return $arr ;
}

function find_start_of_week($first_day,$this_date) {
for ( $i=1; $i<=6; $i++ ):
 $this_weekday = $this_date->format('w') ;
 if ( $first_day == $this_weekday ): break; endif;
 $this_date->modify('-1 day');
endfor;
return $this_date->format('Y-m-d') ;
}

function calendar_color($month) {
static $arr = array() ;  $arr[] = '' ;
$arr[] = '#D8DEE9' ;  $arr[] = '#8FBCBB' ;  $arr[] = '#E5E9F0' ;  $arr[] = '#88C0D0' ;
$arr[] = '#ECEFF4' ;  $arr[] = '#5E81AC' ;  $arr[] = '#D8DEE9' ;  $arr[] = '#8FBCBB' ;
$arr[] = '#E5E9F0' ;  $arr[] = '#88C0D0' ;  $arr[] = '#ECEFF4' ;  $arr[] = '#5E81AC' ;
return $arr[$month] ;
}

function change_calendar() {
set_note_opt(0,'calendar_num_weeks',$_POST['calendar_num_weeks']) ;
set_note_opt(0,'calendar_first_weekday',$_POST['calendar_first_weekday']) ;
set_note_opt(0,'day_before_month', $_POST['day_before_month']);
set_note_opt(0,'calendar_lines',$_POST['calendar_lines']) ;
set_note_opt(0,'calendar_chars',$_POST['calendar_chars']) ;
journal_screen() ;
}

function first_day_select() {
$arr1 = days_array() ;
$pick = get_note_opt(0,'calendar_first_weekday') ;
echo "<select size=1 name='calendar_first_weekday' >\n" ;
for ( $i=0; $i<=6; $i++ ):
 echo "<option value=". $i ;
 if ( $pick == $i ): echo " selected";  endif;
 echo " >". $arr1[$i] ."</option>" ;
endfor;
echo "</select>" ;
}

function journal_screen() {
calendar_top() ;
$arr1 = days_array() ;  $arr2 = months_array() ;
$today = new DateTime() ;  $today_str = $today->format('Y-m-d') ;
$num_wks = get_note_opt(0,'calendar_num_weeks') ;
$first_day = get_note_opt(0,'calendar_first_weekday') ;
$saved_start = get_note_opt(0,'calendar_top_date') ;
if ( $saved_start < '0' ): $saved_start = $today_str ; endif;
$this_date = new DateTime($saved_start) ;
$start_date = find_start_of_week($first_day,$this_date) ;
$day = new DateTime($start_date) ;
echo "<table style='border-spacing: 12px'>\n" ;
$chars = get_note_opt(0,'calendar_chars') ;
$lines = get_note_opt(0,'calendar_lines') ;
for ( $i=1; $i<=$num_wks; $i++ ):
 $new_year = '' ;
 echo "<tr valign='top' >\n" ;
 for ( $j=1; $j<=7; $j++ ):
  if ( $day->format('j') == 1 ): $new_year = $day->format('Y') ;  endif;
  $day_str = $day->format('Y-m-d') ;
  $day_label = $arr1[$day->format('w')] .' '. $arr2[$day->format('n')] .' '. $day->format('j') ;
  if ( 'yes' == get_note_opt(0,'day_before_month') ):
   $day_label = $arr1[$day->format('w')] .' '. $day->format('j') .' '. $arr2[$day->format('n')] ;
  endif;
  $color = calendar_color($day->format('n')) ;
  if ( $day_str == $today_str ): $color = 'lightyellow' ; endif;
  echo "<td style='background-color: ". $color ."; cursor: pointer; " ;
  if ( isset($_POST['last_date']) ):
   if ( $day_str == $_POST['last_date'] ): echo "border: 3px solid green" ; endif;
  endif;
  echo "' onclick='calendar_day(\"". $day_str ."\")' ><span style='color: #222222' >". $day_label ."</span><br>" ;
  $row = select_one_row("SELECT * FROM t_note WHERE calendar_date = '". $day_str ."'") ;
  $txt = strip_tags($row['this_text']) ;
  $move = 0 ;
  for ( $k=1; $k<=$lines; $k++ ):
   echo substr($txt,$move,$chars) ."<br>" ;  $move += $chars ;
  endfor;
  echo "</td>\n" ;
  $day = $day->modify('+1 day') ;
 endfor;
 echo "<td style='color: #222222' >". $new_year ."<br><br></td></tr>\n" ; // show once for each month
endfor;
echo "<tr style='color: #DDDDDD' >". str_repeat("<td>". str_repeat("_",$chars+1) ."</td>",7) ."</tr></table>" ;
echo "</td></tr></table>" ;
}

function calendar_top() {
$GLOBALS['this_title'] = my_word('Journal') ;
page_header() ;
echo "<input type='hidden' id='this_date' name='this_date' >" ;
echo "<input type='hidden' id='calendar_move' name='calendar_move' >" ;
echo "<table style='margin-right:auto; margin-left:1px; margin-top: 9px' >" ;
echo "<tr><td valign='top' align='center' ><div id='new_menu' style='display: none' >" ;
echo "<br>". button("set_grid(1)",'home','js') ."<br><br>" ;
echo my_word('backward') ."<br>" ;
echo button("scroll_up(6)",'6','js') ."<br>". button("scroll_up(4)",'4','js') ."<br>" ;
echo button("scroll_up(2)",'2','js') ."<br>". button("scroll_up(1)",'1','js') ."<br>" ;
echo "<br>". my_word('forward') ."<br>" ;
echo button("scroll_down(1)",'1','js') ."<br>". button("scroll_down(2)",'2','js') ."<br>" ;
echo button("scroll_down(4)",'4','js') ."<br>". button("scroll_down(6)",'6','js') ."<br>" ;
$js  = "document.getElementById(\"new_menu\").style.display = \"none\" ; " ;
$js .= "document.oncontextmenu = function(evt) { " ;
$js .= "document.getElementById(\"new_menu\").style.display = \"block\"; document.oncontextmenu = null; return false; } " ;
echo "<br>". button($js,'hide','js') ."<br><br>" ;
$js  = "document.getElementById(\"menu_div\").style.display = \"block\"; " ;
$js .= "document.getElementById(\"options_div\").style.display = \"none\"; " ;
echo "<div id='options_div' >". button($js,'options','js') ."</div>" ;
calendar_options() ;
echo "</div></td><td valign='top' >" ;
}

function move_calendar_up()   { move_calendar('-'); }
function move_calendar_down() { move_calendar('+'); }

function move_calendar($opt) {
$wks = $_POST['calendar_move'] ;
set_note_opt(0,'calendar_move',$_POST['calendar_move']) ;
$saved_start = get_note_opt(0,'calendar_top_date') ;
if ( $saved_start < '0' ):
 $today = new DateTime() ;
 $today_str = $today->format('Y-m-d') ;
 $saved_start = $today_str ;
endif;
$day = new DateTime($saved_start) ;
$day = $day->modify($opt . $wks .' weeks') ;
$day_str = $day->format('Y-m-d') ;
set_note_opt(0,'calendar_top_date',$day_str) ;
journal_screen() ;
}

function calendar_options() {
echo "<div id='menu_div' style='display: none; text-align: left' ><br><br>" ;
echo my_word('first day of week ') ;
first_day_select() ;
echo "<br>" ;
$arr1 = array(2,4,6,8,10,12) ;
echo my_word('weeks displayed ') ;
select_input('calendar_num_weeks',$arr1) ;
$arr2 = array('no','yes');
echo my_word('day number before month ') ;
select_input('day_before_month',$arr2) ;
$arr3 = array(2,3,4,5) ;
echo my_word('lines for each day ') ;
select_input('calendar_lines',$arr3) ;
$arr4 = array(12,14,16,18,20,22,24,26,28,30) ;
echo my_word('chars for each line ') ;
select_input('calendar_chars',$arr4) ;
echo button('change_calendar','save') ;
echo "<br><span style='color: #DDDDDD' >___________________________</span></div>" ;
}

function open_calendar_cell() {
$prev = num_rows("t_note WHERE calendar_date = '". $_POST['this_date'] ."'");
if ( $prev < 1 ):
 $now = new DateTime() ;  $arr = array() ;
 $arr[] = $_POST['grid_num'] ;
 $arr[] = $now->format('Y-m-d H:i:s') ;
 $arr[] = $_POST['this_date'] ;
 $sql = "INSERT INTO t_note ( parent_grid, datetime_entered, calendar_date ) VALUES ( ?, ? , ? )" ;
 run_sql($sql,$arr) ;
 $latest = select_one_row("SELECT MAX(this_id) AS latest FROM t_note") ;
 $_POST['this_id'] = $latest['latest'] ;
else:
 $row = select_one_row("SELECT * FROM t_note WHERE calendar_date = '". $_POST['this_date'] ."'");
 $_POST['this_id'] = $row['this_id'] ;
endif;
edit_note('calendar',$_POST['this_date']) ;
}

// the rest of this file is for the in-text calculator code ..

function run_save_note_and_calc() {
save_note() ;
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". intval($_POST['this_id']) ) ;
$GLOBALS['decimal_places'] = 2 ;
$GLOBALS['thousands'] = 'anglo' ;
if ( 'euro' == get_note_opt(0,'number_format') ): $GLOBALS['thousands']  = 'euro' ; endif;
parse_calc_str($row['this_text']) ;
calc_parsed() ;
update_calc_str() ;
$row = select_one_row("SELECT * FROM t_note WHERE this_id = ". intval($_POST['this_id']) ) ;
edit_note($row) ;
}

function parse_calc_str($txt) {
$str = '' ;  $type = 'txt' ;  $start = 'n' ;
run_sql("CREATE TEMPORARY TABLE t_this (this_id INTEGER PRIMARY KEY, this_txt, txt_type, calc_name, calc_comment, calc_result, calc_err DEFAULT '')");
$strlen = strlen($txt);
if ( $strlen < 1 ): return; endif;
for( $i = 0; $i <= $strlen; $i++ ):
 if ( !isset($txt[$i]) ): continue; endif;
 if ( '{' == $txt[$i] ):
  if ( strlen($str) > 0 ): write_chunk($str,$type) ; endif;  // save any previous string
  $str = '' ;  $type = 'txt' ;  $start = 'y' ;
 endif;
 if ( '}' == $txt[$i] ):
  if ( 'y' == $start ): $type = 'calc' ;  $start = 'n' ; endif;
  $str .= $txt[$i] ; write_chunk($str,$type) ;
  $type = 'txt' ;  $str = '' ;
  continue;
 endif;
 $str .= $txt[$i] ;
endfor;
if ( strlen($str) > 0 ): write_chunk($str,'txt') ; endif; // write last part ..
}

function write_chunk($str,$type) {
$arr = array() ;  $arr[] = $str ;  $arr[] = $type ;
run_sql("INSERT INTO t_this ( this_txt, txt_type ) VALUES ( ? , ? )",$arr) ;
}

function calc_parsed() {
$result = select_sql("SELECT * FROM t_this WHERE txt_type = 'calc' ORDER BY this_id") ;
while ( $row = $result->fetch() ):
 $arr = explode('=',$row['this_txt']) ;
 $str = $arr[0] ;
 $str = str_replace('{','',$str) ;
 $str = str_replace('}','',$str) ;
 $name = '' ;
 if ( ctype_alpha(substr($str,0,1)) ): $name = substr($str,0,1) ; $str = substr($str,1,strlen($str)) ; endif;
 run_sql("UPDATE t_this SET calc_name = '". $name ."', this_txt = '". $str ."' WHERE this_id = ". $row['this_id']) ;
endwhile;
$result = select_sql("SELECT * FROM t_this WHERE txt_type = 'calc' AND calc_err = '' ORDER BY this_id") ;
while ( $row = $result->fetch() ):
 $ans = calc_row($row['this_txt'],$row['this_id']) ;
 run_sql("UPDATE t_this SET calc_result = '". $ans ."' WHERE this_id = ". $row['this_id']) ;
endwhile;
}

function set_decimal($str,$id) {
if ( ctype_digit(substr($str,1,1)) ):
 $GLOBALS['decimal_places'] = substr($str,1,1) ;
 run_sql("UPDATE t_this SET calc_comment = 'decimal places set to ". substr($str,1,1) ."' WHERE this_id = ". $id) ;
else:
 run_sql("UPDATE t_this SET calc_comment = '". substr($str,1,1) ." not valid for decimal places' WHERE this_id = ". $id) ;
endif;
}

function set_thousands($str,$id) {
$sql = "UPDATE t_this SET calc_comment = 'not a valid option to style marks; should be 0 or 1' WHERE this_id = ". $id ;
if ( '0' == substr($str,1,1) ):
 $GLOBALS['thousands'] = 'anglo' ;
 $sql = "UPDATE t_this SET calc_comment = 'set to anglo style marks' WHERE this_id = ". $id ;
endif;
if ( '1' == substr($str,1,1) ):
 $GLOBALS['thousands'] = 'euro' ;
 $sql = "UPDATE t_this SET calc_comment = 'set to euro style marks' WHERE this_id = ". $id ;
endif;
run_sql($sql) ;
}

function calc_row($str,$id) { // padding operators with wordspaces, to be sure we can pick up the prev cell refs
$str = str_replace('+',' + ',$str) ; $str = str_replace('*',' * ',$str) ; $str = str_replace('-',' - ',$str) ;
$str = str_replace('/',' / ',$str) ; $str = str_replace('(',' ( ',$str) ; $str = str_replace(')',' ) ',$str) ;
if ( '~' == substr($str,0,1) ): set_decimal($str,$id); return; endif;
if ( '!' == substr($str,0,1) ): set_thousands($str,$id); return; endif;
$err_check = check_eq_str($str,$id) ; // maybe we don't need to check here ..
if ( $err_check > '' ):
 run_sql("UPDATE t_this SET calc_err = '". $err_check ."' WHERE this_id = ". $id) ;
 return ;
endif;
$line = explode(' ',$str) ;
$str = '' ;
foreach ( $line as $part ):
 if ( ctype_alpha($part) ): $str .= get_value($part,$id) ; else: $str .= clean_num($part) .' ' ; endif;
endforeach;
$err_check = check_eq_str($str,$id) ;  // check again, now that references are replaced by numbers
if ( $err_check > '' ):
 run_sql("UPDATE t_this SET calc_err = '". $err_check ."' WHERE this_id = ". $id) ;
 return ;
endif;
return calc_process($str) ;
}

function clean_num($num) {
if ( 'euro' == $GLOBALS['thousands'] ):
 $str = str_replace('.','',$num) ; $str = str_replace(',','.',$str) ;
else:
 $str = str_replace(',','',$num) ;
endif;
return $str ;
}

function check_eq_str($str,$id) {
$err = '' ;
if ( strlen(trim($str)) < 1 ): $err = 'empty' ; endif;
$line = explode(' ',$str) ;
$count = 0 ; $type = '' ; $prev_type = '' ; $prev_part = '' ;
if ( false === hasMatchedParentheses($str) ): $err = 'mis-matched parens' ;  endif;
foreach ( $line as $part ):
 if ( ' ' == $part Or '' == $part ): continue; endif;
 if ( $count == 0 And ctype_alpha($part) ): $count++ ; continue; endif; // skip id of this equation
 if ( substr_count($part,'.') > 1 ): $err = 'multiple decimal points' ; endif;
 $type = 'other char' ;
 if ( ctype_alpha($part) ): $type = 'reference' ; endif;
 if ( is_numeric($part) ): $type = 'number' ; endif;
 if ( 'number' == $type ):
  if ( '0' == substr($part,0,1) And '.' != substr($part,1,1) ): $err = 'invalid leading zero' ; endif;
 endif;
 if ( '(' == $part Or ')' == $part  ): $type = 'paren' ; endif;
 if ( '+' == $part Or '*' == $part Or '/' == $part Or '-' == $part ): $type = 'operator' ; endif;
 if ( '(' == $prev_part And ')' == $part ): $err = 'empty parens' ;  endif;
 if ( $type == $prev_type And $type != 'paren' ): $err = 'consecutive '. $type .'s not allowed: "'. $prev_part .'" "'. $part .'"' ;  endif;
 if ( $type != 'reference' ): if ( '/' == $prev_part And intval($part) == 0 ): $err = 'error: division by zero' ; endif; endif;
 $prev_type = $type ; $prev_part = $part ;
 $count++ ;
endforeach;
if ( 'operator' == $type ): $err = 'error: ended with an operator' ;  endif;
if ( $count == 1 And 'number' == $type ): run_sql("UPDATE t_this SET calc_comment = 'just a number' WHERE this_id = ". $id) ; endif;
return $err ;
}

function hasMatchedParentheses($str) {
// credit to Nick Ohrn, stackoverflow.com/questions/562606/regex-for-checking-if-a-string-has-mismatched-parentheses
$counter = 0; $length = strlen($str);
for( $i = 0; $i < $length; $i++ ):
 $char = $str[$i];
 if( $char == '(' ): $counter++ ; elseif( $char == ')' ): $counter-- ; endif;
 if( $counter < 0 ): return false; endif;
endfor;
return $counter == 0;
}

function get_value($name,$id) {
$num = 0 ; // will return zero if there's an error
$num_rows = num_rows("t_this WHERE calc_name = '". $name ."'") ;
if ( $num_rows == 1 ):
 $row = select_one_row("SELECT * FROM t_this WHERE calc_name = '". $name ."'") ;
 if ( isset($row['calc_result']) ):
  if ( $row['calc_result'] != '' ): $num = $row['calc_result'] ; endif;
 else:
  $err = 'there is no result entered for equation '. $name ;
  run_sql("UPDATE t_this SET calc_err = '". $err ."' WHERE this_id = ". $id) ;
 endif;
 if ( $row['calc_err'] > '' ):
  $err = 'equation '. $name .' has an error' ;
  run_sql("UPDATE t_this SET calc_err = '". $err ."' WHERE this_id = ". $id) ;
 endif;
else:
 if ( $num_rows == 0 ): $err = 'there is no equation with id '. $name ; endif;
 if ( $num_rows > 1 ): $err = 'there is more than one equation with id '. $name ; endif;
 run_sql("UPDATE t_this SET calc_err = '". $err ."' WHERE this_id = ". $id) ;
endif;
return $num ;
}

function calc_process($equat) {
$equat = preg_replace('/[^ .\/\-\*\+()0-9]/','',$equat) ;  // remove everything except spaces, dots, ops, parens & nums
if ( strlen($equat) < 1 ): return '' ; endif;
$ans = eval("return (". $equat .");");
if ( is_numeric($ans) ):  $result = $ans ;  else:  $result = '' ;  endif;
return $result;
}

function update_calc_str() {
$str = '' ;
$result = select_sql("SELECT * FROM t_this ORDER BY this_id") ;
while ( $row = $result->fetch() ):
 if ( 'calc' == $row['txt_type'] ):
  $str .= '{' ;
  if ( strlen($row['calc_name']) > 0 ): $str .= $row['calc_name'] ; endif;
  $str .= rtrim($row['this_txt']) ;
  if ( strlen($row['calc_comment']) > 2 ):
   if ( 'just a number' == $row['calc_comment'] ): $str .= ' ' ; else: $str .= ' = ['. $row['calc_comment'] .']' ; endif;
  else:
   $ans = $row['calc_result'] ;
   if ( 'euro' == $GLOBALS['thousands'] ): $dec = ',' ; $tho = '.' ; else: $dec = '.' ; $tho = ',' ;  endif;
   if (is_numeric($row['calc_result']) ): $ans = number_format($row['calc_result'],$GLOBALS['decimal_places'],$dec,$tho) ; endif;
   $str .= " = ". $ans ;
   if ( strlen($row['calc_err']) > 2 ): $str .= ' ['. $row['calc_err'] .']' ; endif;
  endif;
  $str .= ' }' ;
 else:
  $str .= $row['this_txt'] ;
 endif;
endwhile;
$arr = array() ;
$arr[] = db_str($str) ;
run_sql("UPDATE t_note SET this_text = ? WHERE this_id = ". intval($_POST['this_id']), $arr ) ;
}
