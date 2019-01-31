// Mouseover/ Click sound effect- by JavaScript Kit (www.javascriptkit.com)
// Visit JavaScript Kit at http://www.javascriptkit.com/ for full source code
//** Usage: Instantiate script by calling: var uniquevar=createsoundbite("soundfile1", "fallbackfile2", "fallebacksound3", etc)
//** Call: uniquevar.playclip() to play sound
var html5_audiotypes={ //define list of audio file extensions and their associated audio types. Add to it if your specified audio file isn't on this list:
	"mp3": "audio/mpeg",
	"mp4": "audio/mp4",
	"ogg": "audio/ogg",
	"wav": "audio/wav"
}
function createsoundbite(sound){
	var html5audio=document.createElement('audio')
	if (html5audio.canPlayType){ //check support for HTML5 audio
		for (var i=0; i<arguments.length; i++){
			var sourceel=document.createElement('source')
			sourceel.setAttribute('src', arguments[i])
			if (arguments[i].match(/\.(\w+)$/i))
				sourceel.setAttribute('type', html5_audiotypes[RegExp.$1])
			html5audio.appendChild(sourceel)
		}
		html5audio.load()
		html5audio.playclip=function(){
			html5audio.pause()
			html5audio.currentTime=0
			html5audio.play()
		}
		return html5audio
	}
	else{
		return {playclip:function(){throw new Error("Your browser doesn't support HTML5 audio unfortunately")}}
	}
}
//Initialize two sound clips with 1 fallback file each:
notify_sound_done = 0;
notify2_sound_done = 0;
ios = 0;
var notify=createsoundbite("notify.ogg", "notify.mp3");
var notify2=createsoundbite("notify2.ogg", "notify2.mp3");

if((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i)) || (navigator.userAgent.match(/iPad/i))) {
	ios = 1;
    document.write('<a id="init1" ontouchstart="javascript:notifyInit();">Init sending sound on IOS5 (press to enable sound)</a><br /><br />');
    document.write('<a id="init2" ontouchstart="javascript:notify2Init();">Init receiving sound on IOS5 (press to enable sound)</a>');
    function notifyInit(){//alert('notify sound value:'+notify_sound_done);
	    notify.play();
	    notify.pause();
	    //document.getElementById('init1').style.display = 'none';
	    notify_sound_done = 1;
    }
    function notify2Init(){//alert('notify sound value:'+notify2_sound_done);
	    notify2.play();
	    notify2.pause();
	    //document.getElementById('init2').style.display = 'none';
	    notify2_sound_done = 1;
    }
}