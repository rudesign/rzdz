// ���� � ���������

var imagesFolder = '/js/calendar/';





// *********************************** Calendar ***********************************

function calendarHTML(month, year, currDate) {

    // ������� ���� �� ����� ����������

    var isThisMonth = (currDate)? (currDate.getMonth() == month && currDate.getFullYear() == year) : false;



    // ���������� html-��� ��� ���������� ������

    // ������������� �����, ������� ����� ��������

    var drawMonth = new Date();

    drawMonth.setMonth(month, 1);

    drawMonth.setYear(year);

    drawMonth.setDate(1);

    

    // ���������� ��� ������ ��������� �� �������/�����

    var thisMonth = drawMonth.getMonth();

    var nextMonth = (thisMonth == 11)? 0 : thisMonth + 1;

    var prevMonth = (thisMonth == 0)? 11 : thisMonth - 1;

    

    var thisYear = drawMonth.getFullYear();

    var nextYear = thisYear + 1;

    var prevYear = thisYear - 1;

    var nextMonthYear = (thisMonth == 11)? thisYear + 1 : thisYear;

    var prevMonthYear = (thisMonth == 0)? thisYear - 1 : thisYear;

    

    

    // ���������� � ������ ���� ��� - ��������� �������...

    var calendarCode = '<table width="150" border="0" cellspacing="0" cellpadding="0" style="border: 1px solid #D7D7D7;">';

    calendarCode += '<tr><td class="purpleCell"><table cellpadding="0" cellspacing="3" border="0" width="150">';

/*    

    // ����� ��������� ���� �� ������� ���

    calendarCode += '<tr><td><img src="' + imagesFolder + 'arr-prev.gif" width="10" height="10" border="0" onClick="switchMonthTo(' + thisMonth + ', ' + prevYear + ')" style="cursor: pointer; cursor: hand;" /><\/td>';

    // ������� (������������) ���

    calendarCode += '<td align="center" class="purpleCell">' + thisYear + '<\/td>';

    // ���� �� ��������� ���

    calendarCode += '<td align="right"><img src="' + imagesFolder + 'arr-next.gif" width="10" height="10" border="0" onClick="switchMonthTo(' + thisMonth + ', ' + nextYear + ')" style="cursor: pointer; cursor: hand;" /><\/td><\/tr>';

*/    

    // ���� �� ���������� �����

    calendarCode += '<tr><td><img src="' + imagesFolder + 'arr-prev.gif" width="10" height="10" border="0" onClick="switchMonthTo(' + prevMonth + ', ' + prevMonthYear + '); return false;" style="cursor: pointer; cursor: hand;" /><\/td>';

    // ������� �����

    calendarCode += '<td align="center" class="purpleCell">' + calendarNames.month[thisMonth] + ', ' + thisYear + '<\/td>';

    // ���� �� ��������� �����

    calendarCode += '<td align="right"><img src="' + imagesFolder + 'arr-next.gif" width="10" height="10" border="0" onClick="switchMonthTo(' + nextMonth + ', ' + nextMonthYear + '); return false;" style="cursor: pointer; cursor: hand;" /><\/td><\/tr>';

    calendarCode += '<\/table><\/td><\/tr>';







    // �������� ������� ������ ������

    calendarCode += '<tr><td style="border-top: 1px solid #D7D7D7;" bgcolor="#ffffff"><table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-bottom: 1px solid #D7D7D7;"><tr>';

    calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td>';

    for (var i = 0; i < calendarNames.weekday.length; i++) {

        var styleClass = (i < calendarNames.weekday.length - 1)? 'whiteCell' : 'sundayCell';

        calendarCode += '<td class="weekDay" align="right">' + calendarNames.weekday[i] + '<\/td>';

    }

    calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td><\/tr>';



    // ��� �����

    calendarCode += '<tr><td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td>';

    // ������ ������ ������ ���� �����...

    var daysToStart = tasksLanguage=='en' ? drawMonth.getDay() : ((drawMonth.getDay() == 0)? 7 : drawMonth.getDay());

	var whiteDays = tasksLanguage=='en' ? daysToStart : daysToStart - 1;
    for (var i = 0; i < whiteDays; i++) calendarCode += '<td class="whiteCell"><br /><\/td>';



    // ���������� �������

    for (var i = 1; i < 33; i++) {

        drawMonth.setDate(i);

        if (isThisMonth && i == currDate.getDate()) {

            calendarCode += '<td class="blackCell" align="right" onClick="setDateForCalendar(' + i + ', ' + month + ', ' + year + '); return false;" style="cursor: pointer; cursor: hand;">' + i + '<\/td>'

        }

        else {

            if (drawMonth.getMonth() == thisMonth) {

                var styleClass = ((drawMonth.getDay()==0) || (drawMonth.getDay()==6))? 'sundayCell' : 'whiteCell'

                calendarCode += '<td class="' + styleClass + '" align="right" onMouseOver="this.className = \'overCell\';" onMouseOut="this.className = \'' + styleClass + '\';" onClick="setDateForCalendar(' + i + ', ' + month + ', ' + year + '); return false;" style="cursor: pointer; cursor: hand;">' + i + '<\/td>';

            }

            else {

                break;

            }

        }

        var endweek = tasksLanguage=='en' ? 6 : 0;
		if (drawMonth.getDay() == endweek) calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td><\/tr><tr><td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td>';

    }



    // ����� ������ ������ ������

    if (drawMonth.getDay() != 1) {

        var daysToEnd = 8 - ((drawMonth.getDay() == 0)? 7 : drawMonth.getDay());

        for (var i = 0; i < daysToEnd; i++) calendarCode += '<td class="whiteCell"><br /><\/td>';

    }

    calendarCode += '<td class="whiteCell"><br /><\/td><td class="whiteCell"><br /><\/td><\/tr><\/table><\/td><\/tr>';

    // ������ �� �������

    calendarCode += '<tr><td bgcolor="#ffffff" class="whiteCell" onMouseOver="this.className = \'overCell\';" onMouseOut="this.className = \'whiteCell\';" style="padding: 6px; cursor: pointer; cursor: hand;" align="center" onClick="setTodayFromCalendar(); return false;">'+calendarNames.today+'<\/td><\/tr>';

    // �����

    calendarCode += '<\/table>';





    return calendarCode;

}