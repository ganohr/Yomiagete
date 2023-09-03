/*!
 * Ganohrs Yomiagete Ver 0.0.1 - 2023
 * Programmed by https://ganohr.net/
 * You can use for Free. Licensed as GPLv3.
 */
window.ganohrsSpeakIt = {};
(function() {
	let synth = undefined;
	let elems = undefined;

	let nowDataId = undefined;
	let threadId  = undefined;
	let status    = undefined;

	const controller = document.getElementById("yomiagete-controller");

	const readData = (types, option) => {
		return document.getElementById('ganohrs-yomiagete-' + types + '-' + option).getAttribute("value");
	};

	ganohrsSpeakIt.stop = () => {
		if (undefined === status
			|| null === status
			|| ("playing" !== status && "speaking" !== status)
		) {
			status = "stopped";
			return;
		}
		synth.cancel();
		clearInterval(threadId);
		nowDataId = 0;
		status = "stopped";
	};

	ganohrsSpeakIt.pause = () => {
		status = "pausing";
		synth.cancel();
		status = "paused";
	};

	ganohrsSpeakIt.resume = () => {
		status = "playing";
	};

	ganohrsSpeakIt.play = () => {
		if (undefined === status
			|| null === status
			|| (
				"starting" !== status
				&& "restarting" !== status
				&& "playing" !== status
				&& "speaking" !== status
				&& "stopped" !== status
			)
			|| null === synth
			|| undefined === synth
			|| null === synth.targetSpeaker
			|| undefined === synth.targetSpeaker
			|| null === elems
			|| undefined === elems
		) {
			alert("playing error");
			return;
		}
		status = "playing";
		threadId = setInterval(() => {
			updatePlayButton();

			if ("playing" === status) {
				status = "speaking";
				if (elems.length <= nowDataId) {
					ganohrsSpeakIt.stop();
					return;
				}

				const elem = elems[nowDataId];
				elem.scrollIntoView({
					behavior : 'smooth',
					block    : 'center',
					inline   : 'center'
				});
				elem.classList.add("yomiage-now-speaking");

				const text = elem.innerText;
				const utter = new SpeechSynthesisUtterance(text);
				utter.voice = synth.targetSpeaker;
				utter.rate = synth.targetRate;
				utter.pitch = synth.targetPitch;
				utter.volume = synth.targetVolume;
				synth.speak(utter);
			} else if("speaking" === status) {
				if (synth.speaking) {
					return;
				}

				const elem = elems[nowDataId];
				elem.classList.remove("yomiage-now-speaking");

				nowDataId++;
				updatePlayButton();

				status = "playing";
				if (nowDataId >= elems.length) {
					status = "ended";
				}
			}
		}, 100)
	}

	ganohrsSpeakIt.start = () => {
		if (undefined === status
			|| null === status
			|| "initializing" === status
			|| "error" === status
			|| null === synth
			|| undefined === synth
		) {
			alert("initializing error");
			return;
		}
		if (status === "initialized") {
			status = "starting";
		} else {
			status = "restarting";
		}
		nowDataId = 0;
		ganohrsSpeakIt.play();
	}

	speakerSet = (language, speaker, rate, pitch, volume) => {
		const speakers = synth.getVoices();
		if (undefined === speakers
			|| null === speakers
			|| speakers.length === 0
		) {
			return false;
		}

		const lang_arr = (language + '').split(',');
		const spkr_arr  = (speaker  + '').split(',');

		let speaker_found = false;
		let targetSpeaker = undefined;
		for (const speaker of speakers) {
			if (speaker.default) {
				targetSpeaker = speaker;
			}
			for (const l of lang_arr) {
				if (speaker.lang.includes(l)) {
					for (const s of spkr_arr) {
						if (speaker.name.includes(s)) {
							speaker_found = true;
							break;
						}
					}
					if (speaker_found) {
						targetSpeaker = speaker;
						break;
					}
				}
				if (speaker_found) {
					break;
				}
				targetSpeaker = speaker;
			}
			if (speaker_found) {
				break;
			}
		}
		if (undefined !== targetSpeaker) {
			synth.targetSpeaker = targetSpeaker;
			synth.targetRate = rate;
			synth.targetPitch = pitch;
			synth.targetVolume = volume;
		}

		return true;
	};

	ganohrsSpeakIt.init = () => {
		status = "initializing";

		synth = window.speechSynthesis;
		if (undefined === synth
			|| null === synth) {
			synth = window.webkitSpeechSynthesis;
		}
		if (undefined === synth
			|| null === synth) {
			status = "error";
			return;
		}
		elems = document.querySelectorAll("*[data-yomiage-id]");

		const language = readData('options', 'language');
		const speaker  = readData('options', 'speaker');
		const rate     = readData('options', 'rate');
		const pitch    = readData('options', 'pitch');
		const volume   = readData('options', 'volume');

		initializeThread = setInterval(()=>{
			if (speakerSet(language, speaker, rate, pitch, volume)) {
				status = "initialized";
				clearInterval(initializeThread);
			}
		}, 100);

	}

	const playButton = document.getElementById("yomiagete-controller-play");
	const playLabel = document.getElementById("yomiagete-controller-label");

	updatePlayButton = () => {
		const read      = readData('labels', 'read');
		const readAloud = readData('labels', 'read-aloud');
		const pause     = readData('labels', 'pause');
		const resume    = readData('labels', 'resume');

		if ("playing" === status
			|| "speaking" === status
		) {
			let mom = elems.length;
			if (mom === 0) {
				mom = 1;
			}
			val = Math.round((nowDataId / mom) * 100) + "%";
			if (val < 2) {
				val = 2;
			} else if(val > 100) {
				val = 100;
			}
			playButton.innterText = val;
			playButton.style.color = "#d5525f";
			playButton.style.background = "radial-gradient(#f2f2f2 60%, transparent 65%), conic-gradient(#d5525f " + val + " 0%, #d9d9d9 " + val + " 100%)";
			playButton.innerText = pause;
		} else if("pausing" === status
			|| "paused" === status
		) {
			playButton.innerText = resume;
		} else if("ended" === status) {
			playButton.innerText = read;
			playButton.style.background = "radial-gradient(#f2f2f2 60%, transparent 65%), conic-gradient(#d5525f 100% 0%, #d9d9d9 100% 100%)";
		} else {
			playButton.innerText = read;
		}
	}

	const controllerReLayout = () => {
		const pcAdBar = document.querySelector(".adsbygoogle-noablate[data-anchor-status]");
		if (pcAdBar === null || pcAdBar === undefined) {
			controller.style.bottom = 0;
		} else {
			if (pcAdBar.offsetTop < window.innerHeight * 0.5) {
				return;
			}
			const func = () => {
				controller.style.top = (pcAdBar.offsetTop - 70) + "px";
			};
			func();
			const ob = new MutationObserver(func);
			ob.observe(pcAdBar, {
				attributes: true,
				attributeFilter: ['style'],
			});
		}
	};

	const uiControll = ()=> {
		if ("playing" === status
			|| "speaking" === status
		) {
			ganohrsSpeakIt.pause();
		} else if("paused" === status) {
			ganohrsSpeakIt.resume();
		} else if("stopped" === status
			|| "initialized" === status
			|| "ended" === status
		) {
			controller.style.position = "fixed";
			controllerReLayout();

			ganohrsSpeakIt.start();
		}
		updatePlayButton();
	};

	playButton.addEventListener('click', uiControll);
	playLabel.addEventListener('click', uiControll);

	// 初期化処理を呼び出す
	ganohrsSpeakIt.init();
})();
