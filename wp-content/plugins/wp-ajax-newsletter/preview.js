//
// getPageSize()
// Returns array with page width, height and window width, height
// Core code from - quirksmode.org
// Edit for Firefox by pHaez
//
function getPageSize(){
	var xScroll, yScroll;
	if (window.innerHeight && window.scrollMaxY) {	
		xScroll = document.body.scrollWidth;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}
	var windowWidth, windowHeight;
	if (self.innerHeight) {	// all except Explorer
		windowWidth = self.innerWidth;
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}	
	// for small pages with total height less then height of the viewport
	if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else { 
		pageHeight = yScroll;
	}

	// for small pages with total width less then width of the viewport
	if(xScroll < windowWidth){	
		pageWidth = windowWidth;
	} else {
		pageWidth = xScroll;
	}
	arrayPageSize = new Array(pageWidth,pageHeight,windowWidth,windowHeight) 
}

var timer;
var overlayID;
var headerID;
var footerID;
var contentID;

function bodyOverlayFX(overlay,h,f,c) {
	overlayID = overlay;
	alpha = 0;
	headerID = h;
	footerID = f;
	contentID = c;
	fadeInAction = showPreview;
	fadeOutAction = hidePreview;
	var sizesPage = getPageSize();
	$append($new("div","id="+overlayID,''));
	$(overlayID).style.height = arrayPageSize[1] + 'px';
	
	FadeIn();
}

function showPreview(){
	$append($new("div","id="+overlayID+"_prev",''));
	
	var contentObj = document.getElementById(contentID);
	var contentTxt = contentObj.value;
	
	var headerObj = document.getElementById(headerID);
	var headerTxt = headerObj.value;
	
	var footerObj = document.getElementById(footerID);
	var footerTxt = footerObj.value;
	
	var obj = $(overlayID+"_prev");
	var text = "<div class='title'>";
	text += "<span class='titleText'>Newsletter Preview</span><span class='closeButton' ><a href='javascript:FadeOut();'>X</a></span>";
	text += "</div>";
	
	var template = contentTxt;
	
	template = template.replace(/{TITLE}/gi, "Post Title");	
	template = template.replace(/{UPTITLE}/gi, "POST TITLE");	
	template = template.replace(/{URL}/gi, "http://yourblogurl.com/post1/");
	template = template.replace(/{DATE}/gi, "March 10, 2007");
	template = template.replace(/{TIME}/gi, "15:00");
	template = template.replace(/{AUTHOR}/gi, "John Doe");
	template = template.replace(/{EXCERPT}/gi, "This is the excerpt of a demonstration post.");
	template = template.replace(/{CONTENT}/gi, "This is the content of a demonstration post.\nThis is not a real post in your blog, but a text for demonstration purposes.");
	var body = template;
	
	template = contentTxt;
	template = template.replace(/{TITLE}/gi, "Another Post");	
	template = template.replace(/{UPTITLE}/gi, "ANOTHER POST");	
	template = template.replace(/{URL}/gi, "http://yourblogurl.com/post2/");
	template = template.replace(/{DATE}/gi, "March 1, 2007");
	template = template.replace(/{TIME}/gi, "18:00");
	template = template.replace(/{AUTHOR}/gi, "Jane Doe");
	template = template.replace(/{EXCERPT}/gi, "This is the excerpt of another demonstration post.");
	template = template.replace(/{CONTENT}/gi, "This is the content of another demonstration post.\nKeep in mind that this is not a real post.");
	body += "\n" + template;
	
	var content = headerTxt + "\n" + body + "\n" + footerTxt;
	content = content.replace(/\n/gi, "<br />");
	
	text += "<div class='newsletterExample'>"+ content +"</div>";
	obj.innerHTML = text;
	
}

function hidePreview(){
	var obj = $(overlayID+"_prev");
	if ( obj ) {
		$remove(obj);
	}
	timer = window.setInterval('removeOverlay()',1000);
}

function bodyOverlay(overlay) {
	overlayID = overlay;
	var sizesPage = getPageSize();
	$append($new("div","id="+overlayID,''));
	
	$(overlayID).style.height = arrayPageSize[1] + 'px';
		 
	timer = window.setInterval('removeOverlay()',5000);
}

function removeOverlay() {
	clearInterval(timer);
	if ( $(overlayID) ) {
		$remove($(overlayID));
	}
}

var alpha;
var alphaMax = 0.4;
var alphaMin = 0;
var fadeInAction;
var fadeOutAction;

function FadeOut(){
  if(alpha < alphaMin){
    alpha = alphaMin;
    if(typeof(fadeOutAction)!='undefined'){
    	fadeOutAction();
		}
  }else{
  	alpha -= 0.1;
    obj = document.getElementById(overlayID);
    setOpacity(obj, alpha);
    setTimeout("FadeOut()", 100);
  }
}

function FadeIn(){
  if(alpha > alphaMax){
  	alpha = alphaMax;
    if(typeof(fadeInAction)!='undefined'){
    	fadeInAction();
		}
  }else{
  	alpha += 0.1;
    obj = document.getElementById(overlayID);
    setOpacity(obj, alpha);
    setTimeout("FadeIn()", 100);
  }
}

/**
Method:       setOpacity(HTMLElement, Int)
Description:  Sets the opacity of an element
Parameters:
     HTMLElement aElm  - The HTML element to set the opacity for
     Float aOpac       - The value for the element's opacity. 0.0 - 1.0
                         Where 0.0 is invisible and 1.0 is completely 
                         visible
*/
function setOpacity(aElm,aOpac) {
    var object = aElm.style; 
    object.opacity = (aOpac ); 
    object.MozOpacity = (aOpac ); 
    object.KhtmlOpacity = (aOpac ); 
    object.filter = "alpha(opacity=" + aOpac*100 + ")"; 

}


/*****/


/*
*	@name: $(strId[, strId2, strId3, ...]) 
*	@version: 1.0
*	@author: Andre Metzen
*	@param: strId => String, Array of String
*	@return: Node Object, Array of Node Objects
*	@description: Retorna o(s) elemento(s) cujo id �igual a "strId"
*/
function $(strId)
{
	var i, arrReturn,arrStrId;
	if(arguments.length > 1)
	{
		arrStrId = new Array();
		for(i=0; i<arguments.length; i++)
			arrStrId.push(arguments[i]);
	}
	
	if(strId instanceof Array)
	{
		arrStrId = strId;
	}
	
	if(arrStrId instanceof Array)
	{
		arrReturn = new Array();
		for(i=0; i<arrStrId.length; i++)
			arrReturn[i] = document.getElementById(arrStrId[i]);
	}
	else
	{
		arrReturn = document.getElementById(strId);
	}
	
	return arrReturn;
}

/*
*	@name: $before(objNew,objRefer)
*	@version: 1.0
*	@author: Andre Metzen
*	@param: objNew => Node Object
*	@param: objRefer => Node Object
*	@return: Node Object
*	@description: Insere o objeto "objNew" logo acima na arvore de n� do objeto "objRefer"
*/
function $before(objNew,objRefer)
{ 
	return objRefer.parentNode.insertBefore(objNew,objRefer);
}

/*
*	@name: $after(objNew,objRefer)
*	@version: 1.0
*	@author: Leandro Vieira
*	@param: objNew => Node Object
*	@param: objRefer => Node Object
*	@return: Node Object
*	@description: Insere o objeto "objNew" logo abaixo na arvore de n� do objeto "objRefer"
*/
function $after(objNew,objRefer)
{ 
	return objRefer.parentNode.insertBefore(objNew,objRefer.nextSibling);
}

/*
*	@name: $replace(objNew,objOld)
*	@version: 1.0
*	@author: Andre Metzen
*	@param: objNew => Node Object
*	@param: objOld => Node Object
*	@return: Node Object, false
*	@description: Substitue o objeto "objOld" pelo objeto "objNew"
*/
function $replace(objNew,objOld)
{
	if(objOld.parentNode)
	{
		return objOld.parentNode.replaceChild(objNew,objOld);
	}
	else
	{
		return false;
	}
}

/*
*	@name: $new(strTagName, strParams, strConteudo)
*	@version: 1.0
*	@author: Andre Metzen
*	@param: strTagName => String
*	@param: strParams => String, Array of strings; ex: "href=#" or ["href=#","target=_blank"]
*	@param: strConteudo => String, Array of objects or strings
*	@return: Node Object
*	@description: Cria um novo elemento do tipo "strTagName". O parametro "strParams" s� as propriedades que ser� aplicadas ao objeto.
*				  "strParams" pode ser uma string, caso seja apenas uma propriedade, ou um vetor, para n propriedades.
*				  O parametro "strConteudo" �o conteudo. Pode ser passado como um vetor ou diretamente. Aceita-se como
*				  valor objetos ou string.
*/
function $new(strTagName, strParams, strConteudo)
{
	var i, newElement, arrParameters;
	if(typeof strConteudo == "undefined")
	{
		strConteudo = strParams;
		strParams = null;
	}
	
	newElement=document.createElement(strTagName);
	
	if(strParams instanceof Array)
	{
		for(i=0; i<strParams.length && (arrParameters = strParams[i].split("=")); i++)
		{	
			newElement[arrParameters[0]] = arrParameters[1];
		}
	}
	else
	{
		if(typeof strParams == "string" && ( arrParameters = strParams.split("=")) )
		{
			newElement[arrParameters[0]] = (arrParameters.length==2) ? arrParameters[1] : "";
		}
	}

	if(strConteudo instanceof Array)
	{
		for(i=0; i<strConteudo.length; i++)
		{
			(typeof(strConteudo[i]) =="string") ? $append($newTN(strConteudo[i]), newElement) : $append(strConteudo[i], newElement);
		}
	}
	else
	{
		$append(strConteudo, newElement);
	}
	
	return newElement;
}

/*
*	@name: $append(objNode, objParentNode)
*	@version: 1.0
*	@author: Andre Metzen
*	@param: objNode => Node Object, String, Array of Node Objects or Strings
*	@param: objParentNode => Node Object
*	@return: Integer
*	@description: Adiciona o "objNode" como ultimo n�filho de "objParentNode". Se "objNode" for uma string
*				  �criada um Text Node e adicionado como ultimo n�  Caso "objParentNode" n� seja definido �tomado
*				  como padr� "document.body"
*/
function $append(objNode, objParentNode)
{
	var i;
	if(typeof(objParentNode) == "undefined")
	{
		objParentNode = document.body;
	}
	
	if(objNode=="" || objNode == null)
	{
		return true;
	}
	if(objNode instanceof Array)
	{
		for(i=0; i<objNode.length; i++)
		{
			$append(objNode[i], objParentNode);
		}
	}
	else
	{
		if(typeof(objNode) == "string")
		{
			objParentNode.appendChild($newTN(objNode))
		}
		else
		{
			objParentNode.appendChild(objNode);
		}
	}
	
	return objParentNode.childNodes.length;
}

/*
*	@name: $remove(objNode)
*	@version: 1.0
*	@author: Andre Metzen
*	@param: objNode => Node Object
*	@return: void
*	@description: Remove o elemento "objNode"
*/
function $remove(objNode)
{
	if(objNode && objNode.parentNode)
	{
			objNode.parentNode.removeChild(objNode);
	}
}

/*
*	@name: $newTN(strConteudo) 
*	@version: 1.0
*	@author: Andre Metzen
*	@param: strConteudo => String
*	@return: Text Node Object, false
*	@description: Cria e retorna um text node com o conteudo passado em "strConteudo"
*/
function $newTN(strConteudo)
{
	if(typeof strConteudo == "string")
	{
		return document.createTextNode(strConteudo);
	}
	else
	{
		return false;
	}
}



