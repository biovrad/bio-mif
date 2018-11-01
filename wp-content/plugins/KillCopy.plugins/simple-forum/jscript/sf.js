/* ---------------------------------
   Simple Forum - Version 2.1
   Base Javascript
------------------------------------ */

/* ----------------------------------*/
/* Open and Close of hidden divs     */
/* ----------------------------------*/

function toggleLayer(whichLayer)
{
	if (document.getElementById)
	{
		// this is the way the standards work
		var style2 = document.getElementById(whichLayer).style;
		style2.display = style2.display? "":"block";
	}
		else if (document.all)
	{
		// this is the way old msie versions work
		var style2 = document.all[whichLayer].style;
		style2.display = style2.display? "":"block";
	}
		else if (document.layers)
	{
		// this is the way nn4 works
		var style2 = document.layers[whichLayer].style;
		style2.display = style2.display? "":"block";
	}
	var obj = document.getElementById(whichLayer);
	if (whichLayer == 'sfentryform')
	{
		obj.scrollIntoView(top);
	}
}

/* ----------------------------------*/
/* Quote Post insertion              */
/* ----------------------------------*/

function quotePost(postid, intro, rte)
{
	toggleLayer('sfentryform');	
	var postcontent = document.getElementById(postid).innerHTML;
	document.addpost.newtopicpost.value = '<blockquote>'+intro+postcontent+'</blockquote><hr />';

	if (rte)
	{
		tinyMCE.getInstanceById('mce_editor_0').getBody().innerHTML = '<blockquote>'+intro+postcontent+'</blockquote><hr /><p><br /></p>';
	}
}

/* ----------------------------------*/
/* Enable Save button on Math entry  */
/* ----------------------------------*/

function setPostButton() 
{
	document.addpost.newpost.disabled=false;
}

function setTopicButton()
{
	document.addtopic.newtopic.disabled=false;
}

/* ----------------------------------*/
/* Trigger redirect on drop down     */
/* ----------------------------------*/

function changeURL(menuObj)
{
	var i = menuObj.selectedIndex;
	
	if(i > 0)
	{
	if(menuObj.options[i].value != '#')
		{
			window.location = menuObj.options[i].value;
		}
	}
}

/* ----------------------------------*/
/* Load up categories for linking    */
/* ----------------------------------*/

function getCategories(imageFile, url)
{
	document.getElementById('sfcats').innerHTML = '<br /><br /><img src="' + imageFile + ' /><br />';
	ahahRequest(url, 'sfcats');
}



/***********************************************
* Drop Down/ Overlapping Content- © Dynamic Drive (www.dynamicdrive.com)
* This notice must stay intact for legal use.
* Visit http://www.dynamicdrive.com/ for full source code
***********************************************/

function sfgetposOffset(sfboxOverlay, offsettype)
{
	var totaloffset=(offsettype=="left")? sfboxOverlay.offsetLeft : sfboxOverlay.offsetTop;
	var parentEl=sfboxOverlay.offsetParent;
	while (parentEl!=null)
	{
		totaloffset=(offsettype=="left")? totaloffset+parentEl.offsetLeft : totaloffset+parentEl.offsetTop;
		parentEl=parentEl.offsetParent;
	}
	return totaloffset;
}

function sfboxOverlay(curobj, subobjstr, opt_position)
{
	if (document.getElementById)
	{
		var subobj=document.getElementById(subobjstr);
		subobj.style.display=(subobj.style.display!="block")? "block" : "none";
		var xpos=sfgetposOffset(curobj, "left")+((typeof opt_position!="undefined" && opt_position.indexOf("right")!=-1)? -(subobj.offsetWidth-curobj.offsetWidth) : 0);
		var ypos=sfgetposOffset(curobj, "top")+((typeof opt_position!="undefined" && opt_position.indexOf("bottom")!=-1)? curobj.offsetHeight : 0);
		subobj.style.left=xpos+"px";
		subobj.style.top=ypos+"px";
		return false;
	}
		else 
	{
		return true;
	}
}

function sfboxOverlayclose(subobj)
{
	document.getElementById(subobj).style.display="none";
}

/* ----------------------------------*/
/* Announce New Post Tag             */
/* ----------------------------------*/

var oInterval;

function sfNewPostCheck(url, target, timer)
{
	sfInterval = window.setInterval("ahahRequest('" + url + "', '" + target + "')", timer);
}

/* ----------------------------------*/
/* AHAH master routines              */
/* ----------------------------------*/

function ahahRequest(url,target) {
    if (window.XMLHttpRequest) {
        req = new XMLHttpRequest();
        req.onreadystatechange = function() {ahahResponse(target);};
        req.open("GET", url, true);
        req.send(null);
    } else if (window.ActiveXObject) {
        req = new ActiveXObject("Microsoft.XMLHTTP");
        if (req) {
            req.onreadystatechange = function() {ahahResponse(target);};
            req.open("GET", url, true);
            req.send();
        }
    }
} 

function ahahResponse(target) {
   // only if req is "loaded"
   if (req.readyState == 4) {
       // only if "OK"
       if (req.status == 200 || req.status == 304) {
           results = req.responseText;
           document.getElementById(target).innerHTML = results;
       } else {
           document.getElementById(target).innerHTML="ahah error:\n" + req.status + ' ' + req.statusText;
       }
   }
}

/* ----------------------------------*/
/* Quicktag Editor                   */
/* ----------------------------------*/

var edButtons = new Array();
var edLinks = new Array();
var edOpenTags = new Array();

function edButton(id, display, tagStart, tagEnd, access, open) {
	this.id = id;				// used to name the toolbar button
	this.display = display;		// label on button
	this.tagStart = tagStart; 	// open tag
	this.tagEnd = tagEnd;		// close tag
	this.access = access;		// access key
	this.open = open;			// set to -1 if tag does not need to be closed
}

function zeroise(number, threshold) {
	// FIXME: or we could use an implementation of printf in js here
	var str = number.toString();
	if (number < 0) { str = str.substr(1, str.length) }
	while (str.length < threshold) { str = "0" + str }
	if (number < 0) { str = '-' + str }
	return str;
}

var now = new Date();
var datetime = now.getUTCFullYear() + '-' + 
zeroise(now.getUTCMonth() + 1, 2) + '-' +
zeroise(now.getUTCDate(), 2) + 'T' + 
zeroise(now.getUTCHours(), 2) + ':' + 
zeroise(now.getUTCMinutes(), 2) + ':' + 
zeroise(now.getUTCSeconds() ,2) +
'+00:00';

edButtons[edButtons.length] = 
new edButton('ed_strong'
,'b'
,'<strong>'
,'</strong>'
,'b'
);

edButtons[edButtons.length] = 
new edButton('ed_em'
,'i'
,'<em>'
,'</em>'
,'i'
);

edButtons[edButtons.length] = 
new edButton('ed_link'
,'link'
,''
,'</a>'
,'a'
); // special case

edButtons[edButtons.length] = 
new edButton('ed_block'
,'quote'
,'\n\n<blockquote>'
,'</blockquote>\n\n'
,'q'
);

edButtons[edButtons.length] = 
new edButton('ed_ul'
,'ul'
,'<ul>\n'
,'</ul>\n\n'
,'u'
);

edButtons[edButtons.length] = 
new edButton('ed_ol'
,'ol'
,'<ol>\n'
,'</ol>\n\n'
,'o'
);

edButtons[edButtons.length] = 
new edButton('ed_li'
,'li'
,'\t<li>'
,'</li>\n'
,'l'
);

edButtons[edButtons.length] = 
new edButton('ed_code'
,'code'
,'<code>'
,'</code>'
,'c'
);

function edLink() {
	this.display = '';
	this.URL = '';
	this.newWin = 0;
}

function edShowButton(button, i) {
	if (button.id == 'ed_img') {
		document.write('<input type="button" class="qtbutton" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertImage(edCanvas);" value="' + button.display + '" />');
	}
	else if (button.id == 'ed_link') {
		document.write('<input type="button" class="qtbutton" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertLink(edCanvas, ' + i + ');" value="' + button.display + '" />');
	}
	else {
		document.write('<input type="button" class="qtbutton" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertTag(edCanvas, ' + i + ');" value="' + button.display + '"  />');
	}
}

function edShowLinks() {
	var tempStr = '<select onchange="edQuickLink(this.options[this.selectedIndex].value, this);"><option value="-1" selected>(Quick Links)</option>';
	for (i = 0; i < edLinks.length; i++) {
		tempStr += '<option value="' + i + '">' + edLinks[i].display + '</option>';
	}
	tempStr += '</select>';
	document.write(tempStr);
}

function edAddTag(button) {
	if (edButtons[button].tagEnd != '') {
		edOpenTags[edOpenTags.length] = button;
		document.getElementById(edButtons[button].id).value = '/' + document.getElementById(edButtons[button].id).value;
	}
}

function edRemoveTag(button) {
	for (i = 0; i < edOpenTags.length; i++) {
		if (edOpenTags[i] == button) {
			edOpenTags.splice(i, 1);
			document.getElementById(edButtons[button].id).value = document.getElementById(edButtons[button].id).value.replace('/', '');
		}
	}
}

function edCheckOpenTags(button) {
	var tag = 0;
	for (i = 0; i < edOpenTags.length; i++) {
		if (edOpenTags[i] == button) {
			tag++;
		}
	}
	if (tag > 0) {
		return true; // tag found
	}
	else {
		return false; // tag not found
	}
}

function edCloseAllTags() {
	var count = edOpenTags.length;
	for (o = 0; o < count; o++) {
		edInsertTag(edCanvas, edOpenTags[edOpenTags.length - 1]);
	}
}

function edQuickLink(i, thisSelect) {
	if (i > -1) {
		var newWin = '';
		if (edLinks[i].newWin == 1) {
			newWin = ' target="_blank"';
		}
		var tempStr = '<a href="' + edLinks[i].URL + '"' + newWin + '>' 
		            + edLinks[i].display
		            + '</a>';
		thisSelect.selectedIndex = 0;
		edInsertContent(edCanvas, tempStr);
	}
	else {
		thisSelect.selectedIndex = 0;
	}
}

function edToolbar() {
	//document.write('<div id="ed_toolbar">');
	document.write('<div class="quicktags">');
	for (i = 0; i < edButtons.length; i++) {
		edShowButton(edButtons[i], i);
	}
	document.write('<input type="button" class="qtbutton" id="ed_close" class="ed_button" onclick="edCloseAllTags();" title="Close all open tags" value="Close Tags" />');
	document.write('</div>');
}

// insertion code

function edInsertTag(myField, i) {
	//IE support
	if (document.selection) {
		myField.focus();
	    sel = document.selection.createRange();
		if (sel.text.length > 0) {
			sel.text = edButtons[i].tagStart + sel.text + edButtons[i].tagEnd;
		}
		else {
			if (!edCheckOpenTags(i) || edButtons[i].tagEnd == '') {
				sel.text = edButtons[i].tagStart;
				edAddTag(i);
			}
			else {
				sel.text = edButtons[i].tagEnd;
				edRemoveTag(i);
			}
		}
		myField.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var cursorPos = endPos;
		var scrollTop = myField.scrollTop;

		if (startPos != endPos) {
			myField.value = myField.value.substring(0, startPos)
			              + edButtons[i].tagStart
			              + myField.value.substring(startPos, endPos) 
			              + edButtons[i].tagEnd
			              + myField.value.substring(endPos, myField.value.length);
			cursorPos += edButtons[i].tagStart.length + edButtons[i].tagEnd.length;
		}
		else {
			if (!edCheckOpenTags(i) || edButtons[i].tagEnd == '') {
				myField.value = myField.value.substring(0, startPos) 
				              + edButtons[i].tagStart
				              + myField.value.substring(endPos, myField.value.length);
				edAddTag(i);
				cursorPos = startPos + edButtons[i].tagStart.length;
			}
			else {
				myField.value = myField.value.substring(0, startPos) 
				              + edButtons[i].tagEnd
				              + myField.value.substring(endPos, myField.value.length);
				edRemoveTag(i);
				cursorPos = startPos + edButtons[i].tagEnd.length;
			}
		}
		myField.focus();
		myField.selectionStart = cursorPos;
		myField.selectionEnd = cursorPos;
		myField.scrollTop = scrollTop;
	}
	else {
		if (!edCheckOpenTags(i) || edButtons[i].tagEnd == '') {
			myField.value += edButtons[i].tagStart;
			edAddTag(i);
		}
		else {
			myField.value += edButtons[i].tagEnd;
			edRemoveTag(i);
		}
		myField.focus();
	}
}

function edInsertContent(myField, myValue) {
	//IE support
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
		myField.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		myField.value = myField.value.substring(0, startPos)
		              + myValue 
                      + myField.value.substring(endPos, myField.value.length);
		myField.focus();
		myField.selectionStart = startPos + myValue.length;
		myField.selectionEnd = startPos + myValue.length;
	} else {
		myField.value += myValue;
		myField.focus();
	}
}

function edInsertLink(myField, i, defaultValue) {
	if (!defaultValue) {
		defaultValue = 'http://';
	}
	if (!edCheckOpenTags(i)) {
		var URL = prompt('Enter the URL' ,defaultValue);
		if (URL) {
			edButtons[i].tagStart = '<a href="' + URL + '">';
			edInsertTag(myField, i);
		}
	}
	else {
		edInsertTag(myField, i);
	}
}

