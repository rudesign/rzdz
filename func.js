
function OpenImageWin(img, title, width, height) {
	title = (title == '') ? document.title : title;
	
	obj = window.open("", "", "scrollbars=0,dialog=0,minimizable=1,modal=1,resizable=0,width="+width+",height="+height);
	obj.document.write("<html>");
	obj.document.write("<head>");

	obj.document.write("<title>"+title+"</title>");

	obj.document.write("</head>");

	obj.document.write("<body topmargin=0 leftmargin=0 marginwidth=0 marginheight=0>");

	obj.document.write("<img src=\""+img+"\" />");

	obj.document.write("</body>");
	obj.document.write("</html>");
	obj.focus();
}

function OpenSubcrWin(email) {
	if(email != '' && email != document.all.subscr_email.defaultValue) {
		obj = window.open("/subscr/"+escape(email), "subscr",
			 "scrollbars=0,dialog=0,minimizable=1,modal=1,resizable=0,width=200,height=100");
		obj.focus();
	}
}