// *********************************** Calendar ***********************************
// ���� ����������
//var tasksLanguage = 'en'


// ����� ������� � �.�.
var calendarNamesHash = new Array();
calendarNamesHash.ru = new Array();
calendarNamesHash.en = new Array();
calendarNamesHash.ru.month = ['������', '�������', '����', '������', '���', '����', '����', '������', '��������', '�������', '������', '�������'];
calendarNamesHash.ru.monthShort = ['���', '���', '���', '���', '���', '���', '���', '���', '���', '���', '���', '���'];
calendarNamesHash.ru.weekday = ['��', '��', '��', '��', '��', '��', '��'];
calendarNamesHash.ru.today= '�������';

calendarNamesHash.en.month = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
calendarNamesHash.en.monthShort = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
calendarNamesHash.en.weekday = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
calendarNamesHash.en.today= 'today';
var calendarNames = calendarNamesHash[tasksLanguage=='en'?'en':'ru'];



// ������������ �������
var closeCalendarTimeOut = 0;
var activeCalendarName = '';


// �������� ������� ���� ���������
function getCalendarDate(name) {
    if (!LSCalendars[name].GetDate()) { return (new Date()); }
    else { return LSCalendars[name].GetDate(); }

//    return LSCalendars[name].GetDate();
}


// �������������� ���� ��������� � �� ������
function setDateForCalendar(dayToSet, monthToSet, yearToSet) {


    // ��� �� ��������� ������, �� ������ ���
    if (!LSCalendars[activeCalendarName].GetDate()) { var dateToSet = new Date(); }
    else { var dateToSet = LSCalendars[activeCalendarName].GetDate(); }

    	dateToSet.setFullYear(yearToSet);
    	dateToSet.setMonth(monthToSet,1);
	dateToSet.setDate(dayToSet);

    LSCalendars[activeCalendarName].SetDate(dateToSet);
    setInputByLSCalendar(activeCalendarName);
    
    // ��������� ���������
    hideCalendar();

	// ������ ���� ���� 4 ��� �� ������ ���������
	if(activeCalendarName=="DaF") {
		var tt = new Date(yearToSet, monthToSet, dayToSet+4);
		setCalendarDateByStr("DaT", tt.getDate()+"."+(tt.getMonth()+1)+"."+tt.getFullYear().toString());
	}
}



// �������������� ���� ��������� �� ������
function setCalendarDateByStr(name, strDate) {
   
   if (strDate==null || strDate=="") { // ���� ������ ������
        LSCalendars[name].SetDate(null);
    } 
    else {
        MyCal=new LSCalendar();
        if (!MyCal.Validate(strDate)) {
            alert("�������� ������ ���� - "+strDate);
            return false;
        }
        else {
            LSCalendars[name].SetDate(strDate);
            setInputByLSCalendar(name);
            return true;
        }
    }
    closeCalendarTimeOut = 0; // �����
}


// �������������� ������� �������� ��������� � ����� �����
function setInputByLSCalendar(name) {
    document.getElementsByName(name)[0].value=LSCalendars[name].GetStrDate();
    return true;
}


// ������ ����������� ���� ��� �������� ���������
function setTodayFromCalendar() {
    var date=new Date();
    setDateForCalendar(date.getDate(),date.getMonth(),date.getFullYear());
}



// ��������� �������� select � div ��� ����� � ������ ��� ��������
function ChangeElVis(MyVisibility) {
    var calLeer = document.getElementById('candarLeer');
    
    if ((MyVisibility=='hide') || (MyVisibility=='hidden')) { MyVisibility='hidden'; }
    else { MyVisibility='visible'; }
    
    // ��������� ��� ������ ������ ���� ���� ���������.

	var calPosition = new getElementPosition(calLeer);

    CalTop=calPosition.y;
//    CalLeft=Math.round(calPosition.x-calLeer.offsetWidth/3*2);
    CalLeft=calPosition.x;
    CalBottom=CalTop+calLeer.clientHeight;
    CalRight=CalLeft+calLeer.clientWidth;
//    alert(CalTop+"x"+CalLeft+"x"+CalBottom+"x"+CalRight); 


    // select
    for (var i=0; i<document.getElementsByTagName("select").length; i++) {
        //Cu=document.getElementsByTagName("select")(i);
		Cu=document.getElementsByTagName("select")[i];
        if (Cu.name=='calMonth') { continue; } // calMonth - ������ ������ ������ � ���������

        // ���������� ����� �������
        var CuPosition=new getElementPosition(Cu);
        CuTop=CuPosition.y;
        CuLeft=CuPosition.x;
        CuBottom=CuTop+Cu.clientHeight;
        CuRight=CuLeft+Cu.clientWidth;
//        alert(CuTop+"x"+CuLeft+"x"+CuBottom+"x"+CuRight); 

        if ((CuLeft<=CalLeft)&&(CuRight>=CalLeft)||
            (CuLeft>=CalLeft)&&(CuRight<=CalRight)||
            (CuLeft<=CalRight)&&(CuRight>=CalRight)) {

            if ((CuTop<=CalTop)&&(CuBottom>=CalTop)||
                (CuTop>=CalTop)&&(CuBottom<=CalBottom)||
                (CuTop<=CalBottom)&&(CuBottom>=CalBottom)) {
                Cu.style.visibility = MyVisibility;
            }
        }
    }


    // iframe
    for (var i=0; i<document.getElementsByTagName("iframe").length; i++) {
        // Cu=document.getElementsByTagName("iframe")(i);
		Cu=document.getElementsByTagName("iframe")[i];

        // ���������� ����� �������
        var CuPosition=new getElementPosition(Cu);
        CuTop=CuPosition.y;
        CuLeft=CuPosition.x;
        CuBottom=CuTop+Cu.clientHeight;
        CuRight=CuLeft+Cu.clientWidth;
//        alert(CuTop+"x"+CuLeft+"x"+CuBottom+"x"+CuRight); 

        if ((CuLeft<=CalLeft)&&(CuRight>=CalLeft)||
            (CuLeft>=CalLeft)&&(CuRight<=CalRight)||
            (CuLeft<=CalRight)&&(CuRight>=CalRight)) {

            if ((CuTop<=CalTop)&&(CuBottom>=CalTop)||
                (CuTop>=CalTop)&&(CuBottom<=CalBottom)||
                (CuTop<=CalBottom)&&(CuBottom>=CalBottom)) {
                Cu.style.visibility = MyVisibility;
            }
        }
    }

    return true;
}


function showCalendarForElement(elemName, evt, defaultDate, isLeft) {
    var calPtr = document.getElementById(elemName + 'Ptr');
    if (calPtr) { 
        // ���������� ��������� � ���� (������� ����, ���� ����������)
        var calLeer = document.getElementById('candarLeer');
        if (!calLeer) {
            calLeer = document.createElement('div');
            calLeer.id = 'candarLeer';
            document.getElementsByTagName('body')[0].appendChild(calLeer);
        }
        
        // ��������� ������� �� ����, ���� �� - ��������
        if (calLeer.style.visibility=='visible' && activeCalendarName==elemName) {
            calLeer.style.visibility = 'hidden';
            ChangeElVis('visible');
        }
        else {
            activeCalendarName = elemName;
            // �������� ����
            calLeer.style.visibility = 'hidden';
            // ��������� ��� ������ ������ ���� ���� ���������.
            var calPosition = new getElementPosition(calPtr);
            // ��������� ������ �����...
            // ������� ����� ���� ��� ����������
            var currDate = getCalendarDate(elemName);


            // ���� �� ���������, ����������� ���� �� ��������� �������
            CDate=currDate.getDate()+"-"+currDate.getMonth()+"-"+currDate.getFullYear();
            TDate=(new Date()).getDate()+"-"+(new Date()).getMonth()+"-"+(new Date()).getFullYear();
            if (CDate==TDate) {
                if (typeof(defaultDate)=="object" && defaultDate) { currDate=defaultDate; }
            }
            
                        
            // ���������� �������� ���
            calLeer.innerHTML = calendarHTML(currDate.getMonth(), currDate.getFullYear(), currDate);
            // ������ ���� �� �����
            if (isLeft==true) {
                calLeer.style.left = (calPosition.x-calLeer.offsetWidth) + 'px';
                calLeer.style.top = calPosition.y + 'px';
            }
            else {
                //calLeer.style="left:" + calPosition.x + "; top:" + calPosition.y + "";
				calLeer.style.left = calPosition.x + 'px';
//                calLeer.style.left = calPosition.x - calLeer.offsetWidth/3*2;
                calLeer.style.top = calPosition.y + 'px';
            }
            
            
            // ���������� ����������
            calLeer.style.visibility = 'visible';
            ChangeElVis('hidden');

            // �������, ���������� ������� (�����, ���-�� ������ ��� event'�)
            if (evt) { evt.cancelBubble = true; }
            
            // � ������ ���� ���������� �� ���� �� ��������� (����� �� ���������)
            addEvent(calLeer, 'click', calendarClick);
            // � �� mouseout (����� ���������, �� ����� ��������� ����� ;-)
            addEvent(calLeer, 'mouseover', calendarMouseOver);
            addEvent(calLeer, 'mouseout', calendarMouseOut);
        }
    }
}


function calendarClick(e) {
    evt = (e)? e : window.event;
    evt.cancelBubble = true;
}


function calendarMouseOver(e) {
    if (closeCalendarTimeOut) {
        clearTimeout(closeCalendarTimeOut);
        closeCalendarTimeOut = 0;
    }
}


function calendarMouseOut(e) {
    if (closeCalendarTimeOut) {
        clearTimeout(closeCalendarTimeOut);
    }
//    closeCalendarTimeOut = setTimeout('hideCalendar()', 5000);
}


function hideCalendar() {
    var calLeer = document.getElementById('candarLeer');
    if (calLeer) {
        ChangeElVis('show');
        calLeer.style.visibility = 'hidden';
    }
    closeCalendarTimeOut = 0;
}


function switchMonthTo(month, year) {
    var calLeer = document.getElementById('candarLeer');
    if (calLeer) {
        // ��������� ������ �����...
        // ������� ����� ���� ��� ����������
        var currDate = getCalendarDate(activeCalendarName);
        // ���������� �������� ���
        calLeer.innerHTML = calendarHTML(month, year, currDate);
    }
}


function getElementPosition(elemPtr) {
    var posX = elemPtr.offsetLeft;
    var posY = elemPtr.offsetTop;

    while (elemPtr.offsetParent != null) {
        elemPtr = elemPtr.offsetParent;
        posX += elemPtr.offsetLeft;
        posY += elemPtr.offsetTop;
    }
    this.x = posX;
    this.y = posY;
    //alert(elemPtr.id + " " +posX+" " + posY);
    return this;
}



function addEvent(Obj, eventType, eventFunc) {
    if (Obj.addEventListener) { Obj.addEventListener(eventType, eventFunc, false); }
    else if (Obj.attachEvent) { Obj.attachEvent('on'+eventType, eventFunc); }
    else {
        // ��� ������ ���� �� �� �� ������ �� ��������������
    }
}

addEvent(document, 'click', hideCalendar);
addEvent(window, 'resize', hideCalendar);
// *********************************** Calendar ***********************************


// *********************************** LSCalendar ***********************************
/*
����������� ������

MyCal=new LSCalendar();
MyCal.SetDate((new Date));

MyCal2=new LSCalendar();
MyCal2.SetDate("05-10-2003");

alert(MyCal.GetStrDate());
alert(MyCal2.GetStrDate());
*/
function LSCalendar() {
    // �����
    this.date=null;
    this.format='dd-mm-yyyy';
    
    // ������
    this.SetDate=_SetDate;
    this.GetStrDate=_GetStrDate;
    this.GetDate=_GetDate;
    this.Str2Date=_Str2Date;
    this.zeroFill=_zeroFill;
    this.Validate=_Validate;
    this.SetFormat=_SetFormat;
	
	this.ChangPrice = 0;
    
    return true;
}



// Date - ����� ��� ������ Date
function _SetDate(Date) {
    if (Date==null || Date=="") { this.date=null; }
    else if (typeof(Date)=="object") { this.date=Date; }
    else { this.date=this.Str2Date(Date)?this.Str2Date(Date):(new Date()); }

    return true;
}



// �������� ������� ����, ������ Date
function _GetDate() {
    return this.date;
}


/*
*/
function _zeroFill(value) {
    return (value<10?'0':'')+value;
}



// �������� ���� � ��������� �������
function _GetStrDate() {
    if (!this.date) {
        return "";
    }
    else {
        var Day=this.zeroFill(this.date.getDate());
        var Month=this.zeroFill(this.date.getMonth()+1);
        var Year=this.date.getFullYear();
        var shortYear=this.date.getYear();

		if (shortYear>=2000) { shortYear-=2000; } // �������� �� ������
        else if (shortYear>=100) { shortYear-=100; } // �������� �� ������

        shortYear=this.zeroFill(shortYear);
    
        if (this.format=='dd-mm-yy') { return Day+'-'+Month+'-'+shortYear; }
        else if (this.format=='dd.mm.yy') { return Day+'.'+Month+'.'+shortYear; }
        else if (this.format=='yyyy-mm-dd') { return Year+'-'+Month+'-'+Day; }
        else if (this.format=='yyyy.mm.dd') { return Year+'.'+Month+'.'+Day; }
        else if (this.format=='dd.mm.yyyy') { return Day+'.'+Month+'.'+Year; }
        else if (this.format=='mm/dd/yyyy') { return Month+'/'+Day+'/'+Year; }
        else { return Day+'-'+Month+'-'+Year; }
    }
}



// ��������� ������ �������, �������� ������������ ������� ����
function _Validate(Str) {
    return this.Str2Date(Str)?true:false;
}


// ������������� ������ � ������ Date
function _Str2Date(Str) {
    if (Str) {
        var RegYMDHIS = /^(\d{4})[-|.|\/](\d+)[-|.|\/](\d+)\s+(\d+):(\d+):(\d+)$/i; // yyyy-mm-dd hh:ii:ss
        var RegDMYHIS = /^(\d+)[-|.|\/](\d+)[-|.|\/](\d{4})\s+(\d+):(\d+):(\d+)$/i; // dd-mm-yyyy hh:ii:ss
        var RegYMD = /^(\d{4})[-|.|\/](\d+)[-|.|\/](\d+)$/i; // yyyy-mm-dd
        var RegDMY = /^(\d+)[-|.|\/](\d+)[-|.|\/](\d{4})$/i; // dd-mm-yyyy
        var RegDMY2 = /^(\d+)[-|.|\/](\d+)[-|.|\/](\d{2})$/i; // dd-mm-yy

        var date = RegYMDHIS.exec(Str);
        if (date) { return (new Date(date[1],date[2]-1,date[3],date[4],date[5],date[6])); }

        var date = RegDMYHIS.exec(Str);
        if (date) { return (new Date(date[3],date[2]-1,date[1],date[4],date[5],date[6])); }

        var date = RegYMD.exec(Str);
        if (date) { return (new Date(date[1],date[2]-1,date[3])); }

        var date = RegDMY.exec(Str);
        if (date) { 
			if(tasksLanguage=='en') {return (new Date(date[3],date[1]-1,date[2]));} 
			else return (new Date(date[3],date[2]-1,date[1])); 
		}

        var date = RegDMY2.exec(Str);
        if (date) {
            Year=Number(date[3]);
            if (Year<40) { Year+=2000; }
            else { Year+=1900; }
            return (new Date(Year, (date[2]-1), date[1]));
        }
    }
    
    return null;
}



function _SetFormat(Str) {
    if (Str=='dd-mm-yy') { this.format='dd-mm-yy'; }
    else if (Str=='dd.mm.yy') { this.format='dd.mm.yy'; }
    else if (Str=='yyyy-mm-dd') { this.format='yyyy-mm-dd'; }
    else if (Str=='yyyy.mm.dd') { this.format='yyyy.mm.dd'; }
    else if (Str=='dd.mm.yyyy') { this.format='dd.mm.yyyy'; }
    else if (Str=='mm/dd/yyyy') { this.format='mm/dd/yyyy'; }
    else { this.format='dd-mm-yyyy'; }

    return true;
}

// *********************************** LSCalendar ***********************************







// ������� �������������
var pixelSpacer = '<div style="width: 1px; height: 1px;"><spacer type="block" width="1" height="1" /><\/div>';

// ������ �������� LSCalendar ��� ������� ���������
var LSCalendars=new Array();

function calendar(name, Date) {
    LSCalendars[name]=new LSCalendar();
    LSCalendars[name].SetDate(Date);
    

    // ��������� HTML-��� � ������������ ������...
    document.write('<table cellpadding="0" cellspacing="0" border="0"><tr valign="bottom">');
    document.write('<td><input type="text" name="' + name + '" size="12" value="' + LSCalendars[name].GetStrDate() + '" onBlur="setCalendarDateByStr(this.name, this.value);"><\/td>');
    document.write('<td>' + pixelSpacer + '<\/td>');
    document.write('<td valign="middle"><input type="button" class="calBtn" style="width: 40px; font-size: 70%; background: url(' + imagesFolder + 'dayselect.gif) no-repeat center;" onClick="showCalendarForElement(\'' + name + '\', event); return false;"></td>');
    document.write('<td>' + pixelSpacer + '<\/td>');
    

    document.write('<\/tr><tr><td colspan="2">' + pixelSpacer + '<\/td>');
    document.write('<td><div id="' + name + 'Ptr" style="width: 1px; height: 1px;"><spacer type="block" width="1" height="1" /><\/div><\/td><\/tr>');
    document.write('<\/table>');
}
