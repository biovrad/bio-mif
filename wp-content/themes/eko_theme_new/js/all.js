function initPage()
{
	var nav = document.getElementById("menu");
	if (nav)
	{
		var nodes = nav.getElementsByTagName("li");
		for (var i = 0; i < nodes.length; i++)
		{
			if (nodes[i].parentNode.id == "menu")
			{
				nodes[i].onmouseover = function () 
				{
					this.className += " hover";
				}
				nodes[i].onmouseout = function ()
				{
					this.className = this.className.replace("hover", "");
				}
			}
		}
	}

	var leftNav = document.getElementById("menu_left");
	if (leftNav)
	{
		var nodes = leftNav.getElementsByTagName("a");
		for (var i = 0; i < nodes.length; i++)
		{
			if (nodes[i].parentNode.parentNode.id == "menu_left")
			{
				nodes[i].onclick = function () 
				{
					if (this.parentNode.className != 'active')
					{
						for (var j = 0; j < nodes.length; j++)
						{
							nodes[j].parentNode.className = '';
						}
						this.parentNode.className = 'active';
					}
					else
					{
						this.parentNode.className = '';
					}
					return false;
				}
			}
		}
	}
}

if (window.addEventListener){
	window.addEventListener("load", initPage, false);
}
else if (window.attachEvent){
	window.attachEvent("onload", initPage);
}
