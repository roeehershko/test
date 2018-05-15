var scroller_outer_container;
var scroller_inner_container;
var pauseTime = 9600; // in milliseconds.
var isPaused = 0;
var index = 0;
var feeds;

function scroller_pause() { isPaused = 1; }
function scroller_resume() { isPaused = 0; }

function scroller_initiate(content) {
    feeds = content.split('---');
    if (feeds.length > 0) {
        scroller_outer_container = document.getElementById('scroller_outer_container');
        scroller_inner_container = document.getElementById('scroller_inner_container');
        setInterval('scroller()', 10);
    }
}

function scroller() {
    if (isPaused) return;
    if (!scroller_inner_container.innerHTML || parseInt(scroller_inner_container.offsetHeight) == -parseInt(scroller_inner_container.style.top)) {
        scroller_inner_container.innerHTML = feeds[index];
        index = feeds[index + 1] ? index + 1 : 0;
        scroller_inner_container.style.top = parseInt(scroller_outer_container.style.height);
    }
    scroller_inner_container.style.top = parseInt(scroller_inner_container.style.top) - 1 + 'px';
    if (parseInt(scroller_inner_container.style.top) % parseInt(scroller_outer_container.style.height) == 0) {
        scroller_pause();
        setTimeout('scroller_resume()', pauseTime);
    }
}
