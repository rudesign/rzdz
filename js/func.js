var hasFlashPlugin = false;

$(document).ready(function(){

    $(this).keydown(function (e) {
        if (e.ctrlKey && e.keyCode == 13) {
            showSubmitSelection();
        }
    });

    detectFlash();

    $('.scrollToTheTopBtn').click(function(){
        //$('html').scrollTop(0);
        $('html,body').animate({scrollTop:0}, 400);
    });

    $(window).scroll(function(){
        scrollToTheTopBtnApearance();
    });
});

function scrollToTheTopBtnApearance(){
    var y = 0;

    y = $(window).scrollTop();

    if(y > 50) {
        $('.scrollToTheTopBtn').slideDown('fast').css('display', 'block');
    }else{
        $('.scrollToTheTopBtn').slideUp('fast');
    }
}

function add_favorite(a) {
  title=document.title;
  url=document.location;
  try {
    // Internet Explorer
    window.external.AddFavorite(url, title);
  }
  catch (e) {
    try {
      // Mozilla
      window.sidebar.addPanel(title, url, "");
    }
    catch (e) {
      // Opera
      if (typeof(opera)=="object") {
        a.rel="sidebar";
        a.title=title;
        a.url=url;
        a.href=url;
        return true;
      }
      else {
        // Unknown
        alert('Нажмите Ctrl+D для добавления в избранное');
      }
    }
  }
  return false;
}


function getXmlHttp(){
  var xmlhttp;
  try {
    xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
  } catch (e) {
    try {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    } catch (E) {
      xmlhttp = false;
    }
  }
  if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
    xmlhttp = new XMLHttpRequest();
  }
  return xmlhttp;
}
// ??????? ????
function ImgView(photo_id) {
	var req = getXmlHttp()  
       
	req.onreadystatechange = function() {  
        // onreadystatechange ???????????? ??? ????????? ?????? ???????
		if (req.readyState == 4) { 
            // ???? ?????? ???????? ???????????
			if(req.status == 200) { 
				// document.getElementById('basketlink').style.display = '';
				 //alert("????? ???????: "+req.responseText);
			}
		}
	}
       // (3) ?????? ????? ???????????
	req.open('GET', '/ajax/?photo_id='+photo_id, true);
	req.send(null);  // ???????? ??????
}
// ??????? ????
function ImgRating(photo_id, vote) {
	var req = getXmlHttp()  
       
	req.onreadystatechange = function() {  
        // onreadystatechange ???????????? ??? ????????? ?????? ???????
		if (req.readyState == 4) { 
            // ???? ?????? ???????? ???????????
			if(req.status == 200) { 
				 if(req.responseText == '1') alert('?? ??? ??????????!');
				 else if(req.responseText == '2') 
				 {
					 //alert('??? ????? ?????!');
					 document.getElementById('rating'+photo_id).innerHTML = 
						parseInt(document.getElementById('rating'+photo_id).innerHTML) + vote;
					 parent.document.getElementById('rating'+photo_id).innerHTML = 
						parseInt(document.getElementById('rating'+photo_id).innerHTML) + vote;
				 }
			}
		}
	}
       // (3) ?????? ????? ???????????
	req.open('GET', '/ajax/?photo_id='+photo_id+'&vote='+vote, true);
	req.send(null);  // ???????? ??????
}

function printImage (addr) {
    var win = window.open();
    win.document.write('<img src="'+addr+'">');
    win.print();
    win.close();
}

function showSubmitSelection(){
    var selection = $.selection();
    var container = $('.submit-selection');
    if(selection != ''){
        container.slideDown('fast');
        container.find('form .text .placeholder').text(selection);
    }
}

function submitSelection(){
    var container = $('.submit-selection');
    var selection = container.find('form .text .placeholder').text();
    var body = container.find('form textarea').val();
    if(selection != ''){
        showAltBtnState(container.find('.submit-btn a'));

        $.post('/lib/submit_selection.php', {
            selection: selection,
            body: body
        }, function(response){
            if(response.message){
                alert(response.message);
            }else{
                collapseExtraGallery('submit-selection');
            }

            showAltBtnState(container.find('.submit-btn a'));
        }, 'json');
    }
}

function showAltBtnState(buttons){
    var state, altState;

    buttons.each(function(){
        state = $(this).text();
        altState = $(this).attr('opt');

        $(this).attr('opt', state);
        $(this).text(altState);
    });
}

function detectFlash(){
    if(FlashDetect.installed) {
        //console.log('Has Flash Plugin');
        hasFlashPlugin = true;
    }else{
        $('.installFlash.notification').slideDown('fast');
    }
}


function ShowCureList(curestr_id)
{
	
	if(document.getElementById('u'+curestr_id)!=null )
	document.getElementById('u'+curestr_id).style.display = document.getElementById('u'+curestr_id).style.display=='none' ? '' : 'none';

	if(document.getElementById('l'+curestr_id)!=null )
	document.getElementById('l'+curestr_id).style.display = document.getElementById('l'+curestr_id).style.display=='none' ? '' : 'none';
	
}

function ShowAllCureList(mode)
{
	document.getElementById('raskr').style.display = mode==1 ? 'none' : '';
	document.getElementById('skr').style.display = mode==1 ? '' : 'none';
	for(var i=0;i<CureList.length;i++)
	{
		if(document.getElementById('u'+CureList[i])!=null) 
			document.getElementById('u'+CureList[i]).style.display = mode==1 ? '' : 'none';
		if(document.getElementById('l'+CureList[i])!=null) 
			document.getElementById('l'+CureList[i]).style.display = mode==1 ? '' : 'none';
	}
}

function GetPos(elem)
{
var offTrial=document.getElementById(elem);
var offL=0;
var offT=0;

while(offTrial)
{
offL+=offTrial.offsetLeft;
offT+=offTrial.offsetTop;
offTrial=offTrial.offsetParent;
}

if (navigator.userAgent.indexOf("Mac")!=-1 && typeof document.body.leftMargin!="undefined")
{
offL+=document.body.leftMargin;
offT+=document.body.topMargin;
}

return {left:offL , top:offT}
}

function popupMessage(){
    $('.root_popup_message_lnk').click();
}
