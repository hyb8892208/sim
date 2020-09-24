<!DOCTYPE html>
<html>
  <head>
    <title>ÎÄ¼þÊ¾Àý</title>
    <meta name="name" content="content" charset="utf-8">
  </head>
  <body>
      <input type="file" id="file" />
      <input type="button" onclick="readText()" value="File Button">
      <div id="tt">
      </div>
  </body>
</html>
<script charset="utf-8">
window.onload=function () {
  if(typeof(FileReader)=="undefined")
  {
    alert("Not support");
    document.write("");
  }else
  {
    alert("Support");
  }
}

function readText() {
  var file=document.getElementById("file").files[0];
  var reader=new FileReader();
  reader.readAsText(file);
  reader.onload=function(data)
  {
	var tt=document.getElementById("tt")
	tt.innerHTML=this.result;
  }
}
  
</script>