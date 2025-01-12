function addFileInput() {
	var d=document.createElement("div");
	var file=document.createElement("input");
	file.setAttribute("type","file");
	file.setAttribute("class","input");
	file.setAttribute("name","files_to_upload[]");
	d.appendChild(file);
	document.getElementById("moreUploads").appendChild(d);
}

function checkDelete(v){
	var _1=confirm("Do you really want to delete the "+v+"?");
	if(_1==true) { return true; }
	else { return false; }
}

function basename(path) {
	return path.replace(/.*\//,"");
}

function toggle(id) {
	var s=document.getElementById(id).style;
	if (s.display=="none"){ xdivshow(id); }
	else { xdivhide(id); }
}

function toggle_filter(f) {
	var l = document.getElementById('plus'+f);
	if (l.innerHTML == '[+]') { l.innerHTML = '[&minus;]'; }
	else { l.innerHTML = '[+]'; }
}

function xdivshow(id) {
	var sl=document.getElementById(id).style;
	sl.display="block";
}

function xdivshowil(id) {
	var sl=document.getElementById(id).style;
	sl.display="inline";
}

function xdivhide(id) {
	var sl=document.getElementById(id).style;
	sl.display="none";
}

function selectFile(id) {
	document.getElementById('row_'+id).className="selectedrow";
	countselected++;
	document.getElementById('sfNumber').innerHTML='&nbsp;(Selected files/directories: '+countselected+')&nbsp;';
	xdivshowil('selection');
}

function removeSelected(id) {
	document.getElementById('row_'+id).className="row";
	if(--countselected==0) {
		document.getElementById('sfNumber').innerHTML='';
		xdivhide('selection');
		xdivhide('actions');
	} else { document.getElementById('sfNumber').innerHTML='&nbsp;(Selected files/directories: '+countselected+')&nbsp;'; }
}

function xd_selectAll() {
	for (i=0;i<allfiles.length;i++) {
		var f = document.getElementById('cb_'+allfiles[i]);
		if (f.checked == false) {
			selectFile(allfiles[i]);
			f.checked = true ;
		}
	}
}

function xd_deselectAll() {
	for (i=0;i<allfiles.length;i++) {
		var f = document.getElementById('cb_'+allfiles[i]);
		if (f.checked == true) {
			removeSelected(allfiles[i]);
			f.checked = false ;
		}
	}
}

function ahah(url,target) {
	document.getElementById(target).innerHTML='<img src="data:image/gif;base64,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" alt="Loading..." />';
	if(window.XMLHttpRequest) { req=new XMLHttpRequest(); }
	else if(window.ActiveXObject) { req=new ActiveXObject("Microsoft.XMLHTTP"); }
	if(req!=undefined) {
		req.onreadystatechange=function() { ahahDone(url,target); };
		req.open("GET",url,true);req.send("");
	}
}

function ahahDone(url,target) {
	if(req.readyState==4) {
		if(req.status==200) { document.getElementById(target).innerHTML=req.responseText; }
		else { document.getElementById(target).innerHTML=" Error:\n"+req.status+"\n"+req.statusText; }
	}
}

function load(name,div) {
	ahah(name,div);
	return false;
}

function xdoverlay(w){
	document.getElementById('overlay').style.visibility='visible';
	document.getElementById('overoverlay').style.visibility='visible';
	document.getElementById('action').style.width=w+'px';
	document.getElementById('oclose').style.width=w+'px';
}

function xdunderlay(){
	document.getElementById('overlay').style.visibility='hidden';
	document.getElementById('overoverlay').style.visibility='hidden';
	document.getElementById('action').innerHTML='';
}
