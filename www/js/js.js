
/*function lud(oSourceObj,oTargetObj,shutAble,oOpenTip,oShutTip)
{
	var sourceObj = typeof oSourceObj == "string" ? document.getElementById(oSourceObj) : oSourceObj;
	var targetObj = typeof oTargetObj == "string" ? document.getElementById(oTargetObj) : oTargetObj;
	var openTip = oOpenTip || "";
	var shutTip = oShutTip || "";

	if(targetObj.style.display!="none"){
		if(shutAble) return;
		targetObj.style.display="none";
		document.getElementById(oTargetObj+"_li").className="tb5";
		if(openTip && shutTip) {
			sourceObj.innerHTML = shutTip;
		}
	} else {
		targetObj.style.display="block";
		document.getElementById(oTargetObj+"_li").className="tb4";
		if(openTip && shutTip) {
			sourceObj.innerHTML = openTip;
	   }
	}
}*/

function lud(oSourceObj,oTargetObj,shutAble,oOpenTip,oShutTip)
{
	var sourceObj = typeof oSourceObj == "string" ? document.getElementById(oSourceObj) : oSourceObj;
	var targetObj = typeof oTargetObj == "string" ? document.getElementById(oTargetObj) : oTargetObj;
	var openTip = oOpenTip || "";
	var shutTip = oShutTip || "";

	if(targetObj.style.display!="none"){
		if(shutAble) return;
		targetObj.style.display="none";
		document.getElementById(oTargetObj+"_li").className="tb_fold";
		if(openTip && shutTip) {
			sourceObj.innerHTML = shutTip;
		}
	} else {
		targetObj.style.display="block";
		document.getElementById(oTargetObj+"_li").className="tb_unfold";
		if(openTip && shutTip) {
			sourceObj.innerHTML = openTip;
	   }
	}
}

function switch_over(num,nav_lists_count){
	for(var id = 0; id < nav_lists_count ; id++)
	{
		if(id==num)
		{
			document.getElementById("qh_con"+id).style.display="block";
			document.getElementById("mynav"+id).className="nav_on";
		} else {
			document.getElementById("qh_con"+id).style.display="none";
			document.getElementById("mynav"+id).className="";
		}
	}
}

/* unicode 转换为 utf-8 */
function toUtf8(str) {    
    var out, i, len, c;    
    out = "";    
    len = str.length;    
    for(i = 0; i < len; i++) {    
        c = str.charCodeAt(i);    
        if ((c >= 0x0001) && (c <= 0x007F)) {    
            out += str.charAt(i);    
        } else if (c > 0x07FF) {    
            out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F));    
            out += String.fromCharCode(0x80 | ((c >>  6) & 0x3F));    
            out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));    
        } else {    
            out += String.fromCharCode(0xC0 | ((c >>  6) & 0x1F));    
            out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));    
        }    
    }    
    return out;    
}
/****
*　　　　　封装右键菜单函数：
*　　　　elementID   要自定义右键菜单的 元素的id
*　　　　menuID　　　 要显示的右键菜单DIv的 id
*/
function rightmenu(elementID,menuID){
　　var menu=document.getElementById(menuID);      //获取菜单对象
　　var element=document.getElementById(elementID);//获取点击拥有自定义右键的 元素
　　element.onmousedown=function(aevent){         //设置该元素的 按下鼠标右键右键的 处理函数
　　　　if(window.event)aevent=window.event;      //解决兼容性
　　　　if(aevent.button==2){                   //当事件属性button的值为2时，表用户按下了右键
　　　　　　document.oncontextmenu=function(aevent){
   　　　　if(window.event){
       　　　　aevent=window.event;
　　　　　　　　aevent.returnValue=false;         //对IE 中断 默认点击右键事件处理函数
　　　　　　}else{
　　　　　　　　aevent.preventDefault();          //对标准DOM 中断 默认点击右键事件处理函数
　　　　　　};
　　　　};
　　　　menu.style.cssText='display:block;top:'+aevent.clientY+'px;'+'left:'+aevent.clientX+'px;'
　　　　//将菜单相对 鼠标定位
　　　　}
　　}
　　menu.onmouseout=function(){                  //设置 鼠标移出菜单时 隐藏菜单
　　　　setTimeout(function(){menu.style.display="none";},400);
　　}
}