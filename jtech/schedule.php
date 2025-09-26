<?
session_start();

// 시공팀장별로 권한을 주기위함
$username = $_SESSION["name"];

include 'ini.php';

include "_request.php";

// print '$choiceitem : ' . $choiceitem;

if( $choiceitem == '1')
	$workmsg = "(시공예정)";
  if( $choiceitem == '2')
		$workmsg = "(시공완료)";

?>

<!DOCTYPE html>
<meta charset="UTF-8">
<head>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<!-- Form Validator -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery.form/3.32/jquery.form.js"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.11.1/jquery.validate.min.js"></script>

<link rel="stylesheet" href="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.css" />
<script src="https://uicdn.toast.com/tui.pagination/latest/tui-pagination.js"></script>
<link rel="stylesheet" href="https://uicdn.toast.com/tui-grid/latest/tui-grid.css"/>
<script src="https://uicdn.toast.com/tui-grid/latest/tui-grid.js"></script>
<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
<!-- 화면에 UI창 알람창 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>


<!-- Slick Slider -->
<script src="plugins/slick-carousel/slick/slick.min.js"></script>
<!--  Magnific Popup-->
<script src="plugins/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

<script src="js/script.js"></script>

<link rel="stylesheet" href="../css/style.css"/>  
<link rel="stylesheet" href="./css/style.css"/>
<!-- navibarsub css -->  
<link rel="stylesheet" href="css/style2.css">

<link href="./css/calendar.css" rel="stylesheet">
<!------ Include the above in your HEAD tag ---------->
<script src="../common.js"></script>
</head>

<html>

<body>
<title> 제이테크(J-TECH) 작업일정표 </title>

<div  class="container-fluid">	
<? include 'navbarsub.php'; ?>

<div class="d-flex p-2 mt-3 mb-1 justify-content-center">	
	<h3>작업스케줄표 <?=$workmsg?> </h3> 
</div>

<div class="d-flex p-2 justify-content-center">	
				
		<?php
			// 선택 시공예정일, 시공완료일
 		 if($choiceitem=='') $choiceitem='1';
		 $aryitem= array();
	     switch ($choiceitem) {
			case   "1"             : $aryitem[0] = "checked" ; break;
			case   "2"             :$aryitem[1] =  "checked" ; break;
			default: break;
		}		
	   ?>					
					
	<form name="board_form" id="board_form"  method="post" action="schedule.php">  				
			   &nbsp;&nbsp;&nbsp;
			   선택구분 : &nbsp; 시공예정일    &nbsp;       
			   <input  type="radio" <?=$aryitem[0]?> name=choiceitem value="1"> &nbsp; &nbsp; 
							   시공완료일   &nbsp;      
			   <input  type="radio"  <?=$aryitem[1]?>  name=choiceitem value="2"> &nbsp; &nbsp; 
					&nbsp; &nbsp; &nbsp; 
				
					
	</form>  
</div>

<div id="holder" class="d-flex p-2 justify-content-center" ></div>
</div>
</div>

<script type="text/tmpl" id="tmpl">
  {{ 
  var date = date || new Date(),
      month = date.getMonth(), 
      year = date.getFullYear(), 
      first = new Date(year, month, 1), 
      last = new Date(year, month + 1, 0),
      startingDay = first.getDay(), 
      thedate = new Date(year, month, 1 - startingDay),
      dayclass = lastmonthcss,
      today = new Date(),
      i, j; 
  if (mode === 'week') {
    thedate = new Date(date);
    thedate.setDate(date.getDate() - date.getDay());
    first = new Date(thedate);
    last = new Date(thedate);
    last.setDate(last.getDate()+6);
  } else if (mode === 'day') {
    thedate = new Date(date);
    first = new Date(thedate);
    last = new Date(thedate);
    last.setDate(thedate.getDate() + 1);
  }
  
  }}
  <table class="calendar-table table table-condensed table-tight">
    <thead>
      <tr>
        <td colspan="7" style="text-align: center">
          <table style="white-space: nowrap; width: 100%">
            <tr>
              <td style="text-align: left;">
                <span class="btn-group">
                  <button class="js-cal-prev btn btn-default"> < </button>
                  <button class="js-cal-next btn btn-default"> > </button>
                </span>
                <button class="js-cal-option btn btn-default {{: first.toDateInt() <= today.toDateInt() && today.toDateInt() <= last.toDateInt() ? 'active':'' }}" data-date="{{: today.toISOString()}}" data-mode="month">{{: todayname }}</button>
   
              </td>
              <td>
                <span class="btn-group btn-group-lg">
                  {{ if (mode !== 'day') { }}
                    {{ if (mode === 'month') { }}<button class="js-cal-option btn btn-link" data-mode="year">  </button> {{ } }}
                    {{ if (mode ==='week') { }}
							<button class="js-cal-years btn btn-link"> </button> 
                    {{ } }}
					      <button class="btn btn-link disabled"> {{: year }}년 {{: shortMonths[first.getMonth()] }} {{: first.getDate() }}일 - {{: shortMonths[last.getMonth()] }} {{: last.getDate() }}일</button>
                    
                  {{ } else { }}
                    <button class="btn btn-link disabled"> {{: year }}년 {{: shortMonths[last.getMonth()]}} {{: first.getDate() }}일 </button> 
                  {{ } }}
                </span>
              </td>
              <td style="text-align: right">
                <span class="btn-group">
                  <button class="js-cal-option btn btn-default {{: mode==='year'? 'active':'' }}" data-mode="year">년간</button>
                  <button class="js-cal-option btn btn-default {{: mode==='month'? 'active':'' }}" data-mode="month">월간</button>
                  <button class="js-cal-option btn btn-default {{: mode==='week'? 'active':'' }}" data-mode="week">주간</button>
                  <button class="js-cal-option btn btn-default {{: mode==='day'? 'active':'' }}" data-mode="day">일</button>
                </span>
              </td>
            </tr>
          </table>
          
        </td>
      </tr>
    </thead>
    {{ if (mode ==='year') {
      month = 0;
    }}
    <tbody>
      {{ for (j = 0; j < 3; j++) { }}
      <tr>
        {{ for (i = 0; i < 4; i++) { }}
        <td class="calendar-month month-{{:month}} js-cal-option" data-date="{{: new Date(year, month, 1).toISOString() }}" data-mode="month">
          {{: months[month] }}
          {{ month++;}}
        </td>
        {{ } }}
      </tr>
      {{ } }}
    </tbody>
    {{ } }}
    {{ if (mode ==='month' || mode ==='week') { }}
    <thead>
      <tr class="c-weeks">
        {{ for (i = 0; i < 7; i++) { }}
          <th class="c-name">
            {{: days[i] }}
          </th>
        {{ } }}
      </tr>
    </thead>
    <tbody>
      {{ for (j = 0; j < 6 && (j < 1 || mode === 'month'); j++) { }}
      <tr>
        {{ for (i = 0; i < 7; i++) { }}
        {{ if (thedate > last) { dayclass = nextmonthcss; } else if (thedate >= first) { dayclass = thismonthcss; } }}
        <td class="calendar-day {{: dayclass }} {{: thedate.toDateCssClass() }} {{: date.toDateCssClass() === thedate.toDateCssClass() ? 'selected':'' }} {{: daycss[i] }} js-cal-option" data-date="{{: thedate.toISOString() }}">
          <div class="date">{{: thedate.getDate() }}</div>
          {{ thedate.setDate(thedate.getDate() + 1);}}
        </td>
        {{ } }}
      </tr>
      {{ } }}
    </tbody>
    {{ } }}
    {{ if (mode ==='day') { }}
    <tbody>
      <tr>
        <td colspan="7">
          <table class="table table-striped table-condensed table-tight-vert" >
            <thead>
              <tr>
                <th> </th>
                <th style="text-align: center; width: 100%">{{: days[date.getDay()] }}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th class="timetitle" > 당일  </th>
                <td class="{{: date.toDateCssClass() }}">  </td>
              </tr>
       
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
    {{ } }}
  </table>
 </div> 
</script>



<script>
    var $currentPopover = null;
  $(document).on('shown.bs.popover', function (ev) {
    var $target = $(ev.target);
    if ($currentPopover && ($currentPopover.get(0) != $target.get(0))) {
      $currentPopover.popover('toggle');
    }
    $currentPopover = $target;
  }).on('hidden.bs.popover', function (ev) {
    var $target = $(ev.target);
    if ($currentPopover && ($currentPopover.get(0) == $target.get(0))) {
      $currentPopover = null;
    }
  });


//quicktmpl is a simple template language I threw together a while ago; it is not remotely secure to xss and probably has plenty of bugs that I haven't considered, but it basically works
//the design is a function I read in a blog post by John Resig (http://ejohn.org/blog/javascript-micro-templating/) and it is intended to be loosely translateable to a more comprehensive template language like mustache easily
$.extend({
    quicktmpl: function (template) {return new Function("obj","var p=[],print=function(){p.push.apply(p,arguments);};with(obj){p.push('"+template.replace(/[\r\t\n]/g," ").split("{{").join("\t").replace(/((^|\}\})[^\t]*)'/g,"$1\r").replace(/\t:(.*?)\}\}/g,"',$1,'").split("\t").join("');").split("}}").join("p.push('").split("\r").join("\\'")+"');}return p.join('');")}
});

$.extend(Date.prototype, {
  //provides a string that is _year_month_day, intended to be widely usable as a css class
  toDateCssClass:  function () { 
    return '_' + this.getFullYear() + '_' + (this.getMonth() + 1) + '_' + this.getDate(); 
  },
  //this generates a number useful for comparing two dates; 
  toDateInt: function () { 
    return ((this.getFullYear()*12) + this.getMonth())*32 + this.getDate(); 
  },
  toTimeString: function() {
    var hours = this.getHours(),
        minutes = this.getMinutes(),
        hour = (hours > 12) ? (hours - 12) : hours,
        ampm = (hours >= 12) ? ' pm' : ' am';
    if (hours === 0 && minutes===0) { return ''; }
    if (minutes > 0) {
      return hour + ':' + minutes + ampm;
    }
    return hour + ampm;
  }
});


(function ($) {
	

// 쿠키 불러옴
let getCal = getCookie("calendar");

	if(getCal!=null)
	{	
		 console.log('자료있음');
			
		Objectdate = JSON.parse(getCal);

		let Cartcount = Object.keys(Objectdate).length;

		console.log('date : ' + Cartcount);
}
		

  //t here is a function which gets passed an options object and returns a string of html. I am using quicktmpl to create it based on the template located over in the html block
  var t = $.quicktmpl($('#tmpl').get(0).innerHTML);
  
  function calendar($el, options) {
    //actions aren't currently in the template, but could be added easily...
    $el.on('click', '.js-cal-prev', function () {
      switch(options.mode) {
      case 'year': options.date.setFullYear(options.date.getFullYear() - 1); break;
      case 'month': options.date.setMonth(options.date.getMonth() - 1); break;
      case 'week': options.date.setDate(options.date.getDate() - 7); break;
      case 'day':  options.date.setDate(options.date.getDate() - 1); break;
      }
      draw();
    }).on('click', '.js-cal-next', function () {
      switch(options.mode) {
      case 'year': options.date.setFullYear(options.date.getFullYear() + 1); break;
      case 'month': options.date.setMonth(options.date.getMonth() + 1); break;
      case 'week': options.date.setDate(options.date.getDate() + 7); break;
      case 'day':  options.date.setDate(options.date.getDate() + 1); break;
      }
      draw();
    }).on('click', '.js-cal-option', function () {
      var $t = $(this), o = $t.data();
      if (o.date) { o.date = new Date(o.date); }
      $.extend(options, o);
      draw();
    }).on('click', '.js-cal-years', function () {
      var $t = $(this), 
          haspop = $t.data('popover'),
          s = '', 
          y = options.date.getFullYear() - 2, 
          l = y + 5;
      if (haspop) { return true; }
      for (; y < l; y++) {
        s += '<button type="button" class="btn btn-default btn-lg btn-block js-cal-option" data-date="' + (new Date(y, 1, 1)).toISOString() + '" data-mode="year">'+y + '</button>';
      }
       // $t.popover({content: s, html: true, placement: 'auto top'}).popover('toggle');
	   
      return false;
  
	  
    }).on('click', '.event', function () {
		// 클릭했을때 이벤트
      var $t = $(this), 
          index = +($t.attr('data-index')), 
          haspop = $t.data('popover'),
          data, time;
	  
      if (haspop || isNaN(index)) { return true; }
      data = options.data[index];
      time = data.start.toTimeString();
      if (time && data.end) { time = time + ' - ' + data.end.toTimeString(); }
      $t.data('popover',true);
      // $t.popover({content: '<p><strong>' + time + '</strong></p>'+data.text, html: true, placement: 'auto left'}).popover('toggle');
	  
	  console.log(data.id); // id 추출해서
	  popupCenter('write_form.php?navibar=1&num=' + data.id   , '발주서', 1000, 900);		   
	  
      return false;
	  

	  
    });
    function dayAddEvent(index, event) {
      if (!!event.allDay) {
        monthAddEvent(index, event);
        return;
      }
	  // 일자별 화면에 버튼형식으로 만들어주는 부분
	  // 선택을 시공예정일과 시공완료일에 따른 배경색등 지정할때 유용한 것
      var $event = $('<div/>', {'class': 'event', text: event.title, title: event.title, 'data-index': index}),
          start = event.start,
          end = event.end || start,
          time = event.start.toTimeString(),
          hour = start.getHours(),
          timeclass = '.time-22-0',
          startint = start.toDateInt(),
          dateint = options.date.toDateInt(),
          endint = end.toDateInt();
		  
		  
		  
      if (startint > dateint || endint < dateint) { return; }
      
      if (!!time) {
        $event.html('<strong>' + time + '</strong> ' + $event.html());
      }
      $event.toggleClass('begin', startint === dateint);
      $event.toggleClass('end', endint === dateint);
      if (hour < 6) {
        timeclass = '.time-0-0';
      }
      if (hour < 22) {
        timeclass = '.time-' + hour + '-' + (start.getMinutes() < 30 ? '0' : '30');
      }
      $(timeclass).append($event);
    }
    
    function monthAddEvent(index, event) {
      var $event = $('<div/>', {'class': 'event', text: event.title, title: event.title, 'data-index': index}),
          e = new Date(event.start),
          dateclass = e.toDateCssClass(),
          day = $('.' + e.toDateCssClass()),
          empty = $('<div/>', {'class':'clear event', html:' '}), 
          numbevents = 0, 
          time = event.start.toTimeString(),
          endday = event.end && $('.' + event.end.toDateCssClass()).length > 0,
          checkanyway = new Date(e.getFullYear(), e.getMonth(), e.getDate()+40),
          existing,
          i;
      $event.toggleClass('all-day', !!event.allDay);
      if (!!time) {
        $event.html('<strong>' + time + '</strong> ' + $event.html());
      }
      if (!event.end) {
        $event.addClass('begin end');
        $('.' + event.start.toDateCssClass()).append($event);
        return;
      }
            
      while (e <= event.end && (day.length || endday || options.date < checkanyway)) {
        if(day.length) { 
          existing = day.find('.event').length;
          numbevents = Math.max(numbevents, existing);
          for(i = 0; i < numbevents - existing; i++) {
            day.append(empty.clone());
          }
          day.append(
            $event.
            toggleClass('begin', dateclass === event.start.toDateCssClass()).
            toggleClass('end', dateclass === event.end.toDateCssClass())
          );
          $event = $event.clone();
          $event.html(' ');
        }
        e.setDate(e.getDate() + 1);
        dateclass = e.toDateCssClass();
        day = $('.' + dateclass);
      }
    }
    
	function yearAddEvents(events, year) {
      var counts = [0,0,0,0,0,0,0,0,0,0,0,0];
      $.each(events, function (i, v) {
        if (v.start.getFullYear() === year) {
            counts[v.start.getMonth()]++;
        }
      });
      $.each(counts, function (i, v) {
        if (v!==0) {
            $('.month-'+i).append('<span class="badge">'+v+'</span>');
        }
      });
    }
    
    function draw() {
	  // 최종 엘리먼트를 그려준다.	
      $el.html(t(options));
      //potential optimization (untested), this object could be keyed into a dictionary on the dateclass string; the object would need to be reset and the first entry would have to be made here
      $('.' + (new Date()).toDateCssClass()).addClass('today');
      if (options.data && options.data.length) {
        if (options.mode === 'year') {
            yearAddEvents(options.data, options.date.getFullYear());
        } else if (options.mode === 'month' || options.mode === 'week') {
            $.each(options.data, monthAddEvent);
        } else {
            $.each(options.data, dayAddEvent); //day
        }
      }
	  
	  	  
	// 시공완료인 경우는 글자배경색 변경 회색으로	
	changeTextcolor();	
	  
    }
    
    draw();    
  }
  
  (function (defaults, $, window, document) {
    $.extend({
      calendar: function (options) {
        return $.extend(defaults, options);
      }
    }).fn.extend({
      calendar: function (options) {
        options = $.extend({}, defaults, options);
        return $(this).each(function () {
          var $this = $(this);
          calendar($this, options);
        });
      }
    });
  })({
    days: ["일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일"],
    months: ["1월", "2월", "3월", "4월", "5월", "6월", "7월", "8월", "9월", "10월", "11월", "12월"],
    shortMonths: ["1월", "2월", "3월", "4월", "5월", "6월", "7월", "8월", "9월", "10월", "11월", "12월"],
    date: (new Date()),
        daycss: ["c-sunday", "", "", "", "", "", "c-saturday"],
        todayname: "Today",
        thismonthcss: "current",
        lastmonthcss: "outside",
        nextmonthcss: "outside",
    mode: "month",
    data: []
  }, jQuery, window, document);
    
})(jQuery);

var dataset = [];

// 임시로 데이터 생성해서 넣는 구문임
  // for(i = 0; i < 2500; i++) {
    // j = Math.max(i % 15 - 10, 0);
    // //c and c1 jump around to provide an illusion of random data
    // c = (c * 1063) % 1061; 
    // c1 = (c1 * 3329) % 3331;
    // d = (d1 + c + c1) % 839 - 440;
    // h = i % 36;
    // m = (i % 4) * 15;
    // if (h < 18) { h = 0; m = 0; } else { h = Math.max(h - 24, 0) + 8; }
    // end = !j ? null : new Date(y, m, d + j, h + 2, m);
    // data.push({ title: names[c1 % names.length], start: new Date(y, m, d, h, m), end: end, allDay: !(i % 6), text: slipsum[c % slipsum.length ]  });
  // }
  
  // 화면에 표시할 데이터를 형성한다.
  // 현장명 (발주처)_ 작업팀 이런식으로 구성한다.
    let user_name = '<?php echo $user_name; ?>';

	$.ajax({
		
			url: "deadlinedata.php?worker=" + user_name ,
    	  	type: "post",		
   			data: '',
   			dataType:"json",
		}).done(function(data){
             // console.log(Object.values(data)[0].length);		// 12 데이터 숫자 나옴 반복								
			 //	console.log(Object.values(data)[0][0]['address']);		// 참조하려면 좌측과 같이 3번의 첨자가 필요함 주의		
			var titlename ='';
			for(i = 0; i < Object.values(data)[0].length ; i++) {
				// 부모 아이디가 0인것은 현장명 아닌 것은 품목명 추출
				// 시공팀에 따라 조회가 다름
				
				if(Object.values(data)[0][i]['parent_id'] == '0' )
				  {
					titlename = Object.values(data)[0][i]['workplacename'];
					titlename += '(' ;

                  if(Object.values(data)[0][i]['firstord'] !='')
							titlename +=   Object.values(data)[0][i]['firstord'] ;
                  if(Object.values(data)[0][i]['secondord'] !='')
						titlename +=  ", " + Object.values(data)[0][i]['secondord'] + ')' ;
					  else
						titlename +=  ')' ;
					
					if(Object.values(data)[0][i]['worker']!=='')
						titlename += '_' + Object.values(data)[0][i]['worker']  ;
				  }
				  else				
				  {
						titlename = Object.values(data)[0][i]['workplacename'];
						titlename += '(' ;

					  if(Object.values(data)[0][i]['firstord'] !='')
						titlename +=   Object.values(data)[0][i]['firstord'] ;
					  if(Object.values(data)[0][i]['secondord'] !='')
							titlename +=  ", " + Object.values(data)[0][i]['secondord'] + ')' ;
						  else
							titlename +=  ')' ;
						
						if(Object.values(data)[0][i]['worker']!=='')
							titlename += '_' + Object.values(data)[0][i]['worker']  ;
				  }

				var choiceitem = "<?php echo $choiceitem; ?>"; 
                if(choiceitem=='1' )  // 시공예정일 기준 자료
				{
							var date = new Date(Object.values(data)[0][i]['workday']);
							 d = date.getDate();				
							 m = date.getMonth();
							 y = date.getFullYear();				
				}
					else
							{
										var date = new Date(Object.values(data)[0][i]['doneday']);
										 d = date.getDate();				
										 m = date.getMonth();
										 y = date.getFullYear();				
							}
				
				dataset.push({ title: titlename , start: new Date(y, m, d),  end: null , allDay: true , text: titlename , id :Object.values(data)[0][i]['num']  });		
			}
			
  
			  
			  dataset.sort(function(a,b) { return (+a.start) - (+b.start); });
			  
			//data must be sorted by start date

			//Actually do everything
			
			//현재 설정 mode 쿠키에 저장함
			ObjectCal = new Array();
			var data = new Object();					  
			data.mode = '2022-01-01';						
			ObjectCal.push(data);
            console.log('ordercart 쿠키' + JSON.stringify(ObjectCal));
				
		    setCookie ('calendar', JSON.stringify(ObjectCal), 3600);   // 쿠키에 저장함
			
			
			$('#holder').calendar({
			  data: dataset
			});
		
			
		});
			
// console.log(dataset);			

// 강제로 데이터를 써 넣는 구분		
$('#insertBtn').on('click', function() {
	var $t = $(this), o = $t.data();
	// var date = new Date('2022-12-16');
	var date = new Date(o.date);
	 d = date.getDate();				
	 m = date.getMonth();
	 y = date.getFullYear();				

     console.log(date);
     var titlename = '김영무';
	 dataset.push({ title: titlename , start: new Date(y, m, d),  end: null , allDay: true , text: titlename , id :'10'  });
  
			  
	//Actually do everything
	$('#holder').calendar({
	  data: dataset
	});
		   
});  


</script>


<script>

$(document).ready(function(){
	
  $("input:radio[name=choiceitem]").click(function(){		
		document.getElementById('board_form').submit();
	});
	
					
setTimeout(function() { 
// 시공완료인 경우는 글자배경색 변경 회색으로	
changeTextcolor();
}, 1000);


});

function changeTextcolor() {
		var choiceitem = "<?php echo $choiceitem; ?>"; 
		if(choiceitem=='2' )  // 시공예정일 기준 자료
		{
			// 시공완료인 경우는 글자배경색 변경 회색으로	
			$('.event').css('background-image', 'linear-gradient(90deg, rgba(63,94,251,1) 67%, rgba(70,252,204,1) 100%)');			
			$('.event').css('color', 'white');						
		}
}

</script>


	
</body>

</html>