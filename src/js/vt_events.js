
document.addEventListener('DOMContentLoaded', function () {
  document.querySelector('#vt_events_startdate').onchange = changeDateEventHandler;
}, false);

function changeDateEventHandler(event) {
  // split dates 'Y-m-d\TH:i' to array [0 => 'Y-m-d'], [1 => H:i]
  var start = document.querySelector('#vt_events_startdate').value.split('T'); 
  var end = document.querySelector('#vt_events_enddate').value.split('T');
  if(start[0] != "") document.querySelector('#vt_events_enddate').value = `${start[0]}T${end[1]}`;
}