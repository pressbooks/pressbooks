/* global Ed11y */
( function () {
	const POLL_INTERVAL = 200;
	const FULL_CHECK_INTERVAL = 12000;
	const RECHECK_DELAY = 600;
	const initializedEditors = new WeakSet();
	const EXPORT_SHIM = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y;}}catch(e){}';

	/**
	 * Run callback when DOM is ready.
	 *
	 * @param {Function} cb
	 */
	function whenDomReady( cb ) {
		if ( document.readyState !== 'loading' ) {
			cb();
		} else {
			document.addEventListener( 'DOMContentLoaded', cb );
		}
	}

	/**
	 * Wait until TinyMCE editors are present.
	 *
	 * @param {Function} cb
	 */
	function waitForTinyMceEditors( cb ) {
		if ( window.tinymce && window.tinymce.editors.length ) {
			cb();
			return;
		}
		setTimeout( function () {
			waitForTinyMceEditors( cb );
		}, POLL_INTERVAL );
	}

	/**
	 * Debounce a function.
	 *
	 * @param {Function} fn
	 * @param {number} ms
	 * @returns {Function}
	 */
	function debounce( fn, ms ) {
		let timer = null;
		return function debounced( ...innerArgs ) {
			clearTimeout( timer );
			timer = setTimeout( function () {
				fn.apply( null, innerArgs );
			}, ms );
		};
	}

	/**
	 * Build Ed11y options tailored for TinyMCE iframe.
	 *
	 * @returns {object}
	 */
	function buildEd11yOptions() {
		const base = ( window.ed11yVars && window.ed11yVars.options ) ? JSON.parse( JSON.stringify( window.ed11yVars.options ) ) : {};
		base.checkRoots = 'body#tinymce';
		base.ignoreElements = ( base.ignoreElements || '' ) + ', #wpadminbar *';
		base.liveCheck = base.liveCheck || 'all';
		base.showResults = true;
		base.buttonZIndex = 99999;
		base.inlineAlerts = false;
		base.customTests = base.customTests || 0;
		base.preventCheckingIfPresent = '';
		return base;
	}

	/**
	 * Ensure Ed11y script is loaded inside iframe and constructor exported.
	 *
	 * @param {Window} iframeWin
	 * @param {Function} done
	 */
	function ensureEd11yInIframe( iframeWin, done ) {
		if ( iframeWin.Ed11y ) {
			return done();
		}
		const parentScript = document.querySelector( 'script[src*="editoria11y.min.js"]' );
		if ( ! parentScript ) {
			return done();
		}
		if ( iframeWin.document.querySelector( 'script[src*="editoria11y.min.js"]' ) ) {
			let loops = 0;
			( function waitCtor() {
				try {
					if ( ! iframeWin.Ed11y && ! iframeWin.__pbEd11yExportTried ) {
						iframeWin.__pbEd11yExportTried = true;
						const exp = iframeWin.document.createElement( 'script' );
						exp.text = EXPORT_SHIM;
						iframeWin.document.head.appendChild( exp );
					} else if ( ! iframeWin.Ed11y && iframeWin.__pbEd11yExportTried ) {
						const exp2 = iframeWin.document.createElement( 'script' );
						exp2.text = EXPORT_SHIM;
						iframeWin.document.head.appendChild( exp2 );
					}
				} catch ( e ) { /* capture constructor polling error */ }
				if ( iframeWin.Ed11y ) {
					return done();
				}
				if ( window.Ed11y && ! iframeWin.Ed11y ) {
					iframeWin.Ed11y = window.Ed11y;
					return done();
				}
				if ( loops++ > 40 ) {
					return done();
				}
				setTimeout( waitCtor, POLL_INTERVAL );
			} )();
			return;
		}
		const s = iframeWin.document.createElement( 'script' );
		s.src = parentScript.src;
		/**
		 *
		 */
		s.onload = function () {
			let loops2 = 0;
			( function confirmCtor() {
				try {
					if ( ! iframeWin.Ed11y ) {
						const exp3 = iframeWin.document.createElement( 'script' );
						exp3.text = EXPORT_SHIM;
						iframeWin.document.head.appendChild( exp3 );
					}
				} catch ( e2 ) { /* ignore post-load export error */ }
				if ( iframeWin.Ed11y ) {
					return done();
				}
				if ( window.Ed11y && ! iframeWin.Ed11y ) {
					iframeWin.Ed11y = window.Ed11y;
					return done();
				}
				if ( loops2++ > 25 ) {
					return done();
				}
				setTimeout( confirmCtor, 120 );
			} )();
		};
		/**
		 *
		 */
		s.onerror = function () {
			done();
		};
		iframeWin.document.head.appendChild( s );
	}

	/**
	 * Ensure stylesheet is present in iframe.
	 *
	 * @param {Document} iframeDoc
	 */
	function ensureIframeStyles( iframeDoc ) {
		const parentCss = document.querySelector( 'link[href*="editoria11y"][href$=".css"], link[href*="editoria11y.min.css"]' );
		if ( parentCss && ! iframeDoc.querySelector( 'link[href="' + parentCss.href + '"]' ) ) {
			const l = iframeDoc.createElement( 'link' );
			l.rel = 'stylesheet';
			l.href = parentCss.href;
			iframeDoc.head.appendChild( l );
		}
	}

	/**
	 * Create Ed11y instance for an editor iframe.
	 *
	 * @param {object} editor
	 */
	function initEd11yInstance( editor ) {
		const iframe = editor.iframeElement || document.getElementById( editor.id + '_ifr' );
		if ( ! iframe || ! iframe.contentWindow || ! iframe.contentDocument ) {
			return;
		}
		const w = iframe.contentWindow;
		const d = w.document;
		if ( ! d.body ) {
			return setTimeout( function () {
				initEd11yInstance( editor );
			}, POLL_INTERVAL );
		}
		if ( ! /\btinymce\b/.test( d.body.id ) ) {
			d.body.id = 'tinymce';
		}
		ensureIframeStyles( d );
		ensureEd11yInIframe( w, function () {
			if ( ! w.Ed11y ) {
				return;
			}
			if ( w.__pbEd11yInstance ) {
				return;
			}
			const opts = buildEd11yOptions();
			try {
				w.__pbEd11yInstance = new w.Ed11y( opts );
				w.__pbEd11yLastFull = Date.now();
				w.__pbEd11yVisible = true;
			} catch ( e ) { /* ignore instantiation error */ }
		} );
	}

	/**
	 * Run full or incremental recheck.
	 *
	 * @param {object} editor
	 * @param {boolean} forceFull
	 */
	function runEd11yRecheck( editor, forceFull ) {
		const iframe = editor.iframeElement || document.getElementById( editor.id + '_ifr' );
		if ( ! iframe || ! iframe.contentWindow ) {
			return;
		}
		const w = iframe.contentWindow; const inst = w.__pbEd11yInstance; const C = w.Ed11y;
		if ( ! inst || ! C ) {
			return;
		}
		try {
			if ( forceFull || Date.now() - ( w.__pbEd11yLastFull || 0 ) > FULL_CHECK_INTERVAL ) {
				if ( ! C.running && typeof C.checkAll === 'function' ) {
					C.checkAll(); w.__pbEd11yLastFull = Date.now();
				}
			} else if ( ! C.running && typeof C.incrementalCheck === 'function' ) {
				C.incrementalCheck();
			} else if ( ! C.running && typeof C.checkAll === 'function' ) {
				C.checkAll(); w.__pbEd11yLastFull = Date.now();
			}
		} catch ( e ) {
			/* ignore recheck error */
		}
	}

	/**
	 * Hook lifecycle for a TinyMCE editor.
	 *
	 * @param {object} editor
	 */
	function attachEditorLifecycle( editor ) {
		if ( initializedEditors.has( editor ) ) {
			return;
		}
		initializedEditors.add( editor );
		editor.on( 'init', function () {
			initEd11yInstance( editor );
			const debounced = debounce( function () {
				runEd11yRecheck( editor, false );
			}, RECHECK_DELAY );
			[ 'Change', 'SetContent', 'KeyUp', 'Paste', 'Undo', 'Redo', 'NodeChange' ].forEach( function ( ev ) {
				editor.on( ev, debounced );
			} );
		} );
	}

	whenDomReady( function () {
		waitForTinyMceEditors( function () {
			window.tinymce.editors.forEach( attachEditorLifecycle );
			new MutationObserver( function () {
				window.tinymce.editors.forEach( attachEditorLifecycle );
			} ).observe( document.body, {
				childList: true,
				subtree: true,
			} );
			window.tinymce.editors.forEach( function ( ed ) {
				if ( ed.initialized && ! ed.__pbEd11ySetupDone ) {
					ed.__pbEd11ySetupDone = true;
					initEd11yInstance( ed );
				}
			} );
		} );
	} );

	( function earlySetup() {
		if ( ! window.tinyMCEPreInit || ! window.tinyMCEPreInit.mceInit || ! window.tinyMCEPreInit.mceInit.content ) {
			return setTimeout( earlySetup, 50 );
		}
		const cfg = window.tinyMCEPreInit.mceInit.content;
		const orig = cfg.setup;
		/**
		 *
		 * @param editor
		 */
		cfg.setup = function ( editor ) {
			if ( typeof orig === 'function' ) {
				try {
					orig( editor );
				} catch ( e ) { /* ignore original setup error */ }
			}
			attachEditorLifecycle( editor );
		};
	} )();
} )();

try {
	if ( typeof Ed11y !== 'undefined' && ! window.Ed11y ) {
		window.Ed11y = Ed11y;
	}
} catch ( e ) { /* ignore final export attempt error */ }
