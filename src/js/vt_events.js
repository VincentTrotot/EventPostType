var global_start, global_end;

window.addEventListener("DOMContentLoaded", () => {
  setGlobalDate();
  
  document.querySelector('#vt_events_startdate').onchange = changeStartDateEventHandler;
  document.querySelector('#vt_events_enddate').onchange = setGlobalDate;
});

function changeStartDateEventHandler()
{
  // split dates 'Y-m-d\TH:i' to array [0 => 'Y-m-d'], [1 => H:i]
  var start = document.querySelector('#vt_events_startdate').value.split('T'); 
  var end = document.querySelector('#vt_events_enddate').value.split('T');
  if(start[0] != "") {
    if(global_start[1] == global_end[1]) {
      console.log(`${global_start[1]} et ${global_end[1]}`)
      document.querySelector('#vt_events_enddate').value = `${start[0]}T${start[1]}`;
    } else {
      document.querySelector('#vt_events_enddate').value = `${start[0]}T${end[1]}`;
    }
  }
  setGlobalDate()
}

function setGlobalDate()
{
  global_start = document.querySelector('#vt_events_startdate').value.split('T'); 
  global_end = document.querySelector('#vt_events_enddate').value.split('T');
}
