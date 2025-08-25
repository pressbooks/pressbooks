/* global Ed11y */
( function () {
	const POLL_INTERVAL_MS = 200;
	const initializedEditorsSet = new WeakSet();
	const ED11Y_EXPORT_SHIM = 'try{if(typeof Ed11y!=="undefined" && !window.Ed11y){window.Ed11y=Ed11y;}}catch(e){}';

	/**
	 * Run callback when DOM is ready.
	 *
	 * @param {Function} callback
	 */
	function onDomReady( callback ) {
		if ( document.readyState !== 'loading' ) {
			callback();
		} else {
			document.addEventListener( 'DOMContentLoaded', callback );
		}
	}

	/**
	 * Wait until TinyMCE editors are present.
	 *
	 * @param {Function} callback
	 */
	function waitForTinyMceEditorsReady( callback ) {
		if ( window.tinymce && window.tinymce.editors.length ) {
			callback();
			return;
		}
		setTimeout( function () {
			waitForTinyMceEditorsReady( callback );
		}, POLL_INTERVAL_MS );
	}

	/**
	 * Build Ed11y options tailored for TinyMCE iframe.
	 *
	 * @returns {object}
	 */
	function buildTinymceEd11yOptions() {
		const base = JSON.parse( JSON.stringify( window.ed11yVars.options ) );
		base.checkRoots = 'body#tinymce';
		base.editableContent = 'body#tinymce';
		base.autoDetectShadowComponents = false;
		base.inlineAlerts = false;
		base.alertMode = 'active';
		base.watchForChanges = true;

		base.editorHeadingLevel = [
			{
				selector: 'body#tinymce',
				previousHeading: 1,
			},
			{
				selector: '*',
				previousHeading: 0,
			},
		];

		base.showResults = true;
		base.buttonZIndex = 99999;
		base.preventCheckingIfPresent = '';

		return base;
	}

	/**
	 * Ensure Ed11y script is loaded inside iframe and constructor exported.
	 *
	 * @param {Window} iframeWindow
	 * @param {Function} onAvailable
	 */
	function ensureEd11yLoadedInIframe( iframeWindow, onAvailable ) {
		if ( iframeWindow.Ed11y ) {
			return onAvailable();
		}
		const parentScript = document.querySelector( 'script[src*="editoria11y.min.js"]' );
		if ( ! parentScript ) {
			return onAvailable();
		}
		if ( iframeWindow.document.querySelector( 'script[src*="editoria11y.min.js"]' ) ) {
			let pollCount = 0;
			( function waitCtor() {
				try {
					if ( ! iframeWindow.Ed11y && ! iframeWindow.__pbEd11yExportTried ) {
						iframeWindow.__pbEd11yExportTried = true;
						const shimScriptEl = iframeWindow.document.createElement( 'script' );
						shimScriptEl.text = ED11Y_EXPORT_SHIM;
						iframeWindow.document.head.appendChild( shimScriptEl );
					} else if ( ! iframeWindow.Ed11y && iframeWindow.__pbEd11yExportTried ) {
						const shimScriptEl2 = iframeWindow.document.createElement( 'script' );
						shimScriptEl2.text = ED11Y_EXPORT_SHIM;
						iframeWindow.document.head.appendChild( shimScriptEl2 );
					}
				} catch ( e ) { /* capture constructor polling error */ }
				if ( iframeWindow.Ed11y ) {
					return onAvailable();
				}
				if ( window.Ed11y && ! iframeWindow.Ed11y ) {
					iframeWindow.Ed11y = window.Ed11y;
					return onAvailable();
				}
				if ( pollCount++ > 40 ) {
					return onAvailable();
				}
				setTimeout( waitCtor, POLL_INTERVAL_MS );
			} )();
			return;
		}
		const scriptElement = iframeWindow.document.createElement( 'script' );
		scriptElement.src = parentScript.src;
		/**
		 *
		 */
		scriptElement.onload = function () {
			let postLoadPollCount = 0;
			( function confirmCtor() {
				try {
					if ( ! iframeWindow.Ed11y ) {
						const shimScriptEl3 = iframeWindow.document.createElement( 'script' );
						shimScriptEl3.text = ED11Y_EXPORT_SHIM;
						iframeWindow.document.head.appendChild( shimScriptEl3 );
					}
				} catch ( e2 ) { /* ignore post-load export error */ }
				if ( iframeWindow.Ed11y ) {
					return onAvailable();
				}
				if ( window.Ed11y && ! iframeWindow.Ed11y ) {
					iframeWindow.Ed11y = window.Ed11y;
					return onAvailable();
				}
				if ( postLoadPollCount++ > 25 ) {
					return onAvailable();
				}
				setTimeout( confirmCtor, 120 );
			} )();
		};
		/**
		 *
		 */
		scriptElement.onerror = function () {
			onAvailable();
		};
		iframeWindow.document.head.appendChild( scriptElement );
	}

	/**
	 * Create Ed11y instance for an editor iframe.
	 *
	 * @param {object} editor
	 */
	function initEd11yInstanceForEditor( editor ) {
		const iframe = editor.iframeElement || document.getElementById( editor.id + '_ifr' );
		if ( ! iframe || ! iframe.contentWindow || ! iframe.contentDocument ) {
			return;
		}
		const win = iframe.contentWindow;
		const doc = win.document;
		if ( ! doc.body ) {
			return setTimeout( function () {
				initEd11yInstanceForEditor( editor );
			}, POLL_INTERVAL_MS );
		}
		if ( ! /\btinymce\b/.test( doc.body.id ) ) {
			doc.body.id = 'tinymce';
		}

		ensureEd11yLoadedInIframe( win, function () {
			if ( ! win.Ed11y ) {
				return;
			}
			if ( win.__pbEd11yInstance ) {
				return;
			}
			const opts = buildTinymceEd11yOptions();
			try {
				win.__pbEd11yOptions = opts;
				win.__pbEd11yInstance = new win.Ed11y( opts );
			} catch ( e ) { /* ignore instantiation error */ }
		} );
	}

	/**
	 * Hook lifecycle for a TinyMCE editor.
	 *
	 * @param {object} editor
	 */
	function attachEd11yLifecycleToEditor( editor ) {
		if ( initializedEditorsSet.has( editor ) ) {
			return;
		}
		initializedEditorsSet.add( editor );
		editor.on( 'init', function () {
			initEd11yInstanceForEditor( editor );
		} );
	}

	onDomReady( function () {
		waitForTinyMceEditorsReady( function () {
			window.tinymce.editors.forEach( attachEd11yLifecycleToEditor );
			window.tinymce.editors.forEach( function ( ed ) {
				if ( ed.initialized && ! ed.__pbEd11ySetupDone ) {
					ed.__pbEd11ySetupDone = true;
					initEd11yInstanceForEditor( ed );
				}
			} );
		} );
	} );

	( function hookTinyMcePreInit() {
		if ( ! window.tinyMCEPreInit || ! window.tinyMCEPreInit.mceInit || ! window.tinyMCEPreInit.mceInit.content ) {
			return setTimeout( hookTinyMcePreInit, 50 );
		}
		const mceConfig = window.tinyMCEPreInit.mceInit.content;
		const originalSetup = mceConfig.setup;
		/**
		 *
		 * @param editor
		 */
		mceConfig.setup = function ( editor ) {
			if ( typeof originalSetup === 'function' ) {
				try {
					originalSetup( editor );
				} catch ( e ) { /* ignore original setup error */ }
			}
			attachEd11yLifecycleToEditor( editor );
		};
	} )();
} )();

try {
	if ( typeof Ed11y !== 'undefined' && ! window.Ed11y ) {
		window.Ed11y = Ed11y;
	}
} catch ( e ) { /* ignore final export attempt error */ }
