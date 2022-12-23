/*! For license information please see pressbooks-multiselect.js.LICENSE.txt */
(()=>{"use strict";const t=window,e=t.ShadowRoot&&(void 0===t.ShadyCSS||t.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,i=Symbol(),s=new WeakMap;class o{constructor(t,e,s){if(this._$cssResult$=!0,s!==i)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=t,this.t=e}get styleSheet(){let t=this.o;const i=this.t;if(e&&void 0===t){const e=void 0!==i&&1===i.length;e&&(t=s.get(i)),void 0===t&&((this.o=t=new CSSStyleSheet).replaceSync(this.cssText),e&&s.set(i,t))}return t}toString(){return this.cssText}}const n=(t,...e)=>{const s=1===t.length?t[0]:e.reduce(((e,i,s)=>e+(t=>{if(!0===t._$cssResult$)return t.cssText;if("number"==typeof t)return t;throw Error("Value passed to 'css' function must be a 'css' function result: "+t+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(i)+t[s+1]),t[0]);return new o(s,t,i)},r=e?t=>t:t=>t instanceof CSSStyleSheet?(t=>{let e="";for(const i of t.cssRules)e+=i.cssText;return(t=>new o("string"==typeof t?t:t+"",void 0,i))(e)})(t):t;var l;const a=window,h=a.trustedTypes,d=h?h.emptyScript:"",c=a.reactiveElementPolyfillSupport,p={toAttribute(t,e){switch(e){case Boolean:t=t?d:null;break;case Object:case Array:t=null==t?t:JSON.stringify(t)}return t},fromAttribute(t,e){let i=t;switch(e){case Boolean:i=null!==t;break;case Number:i=null===t?null:Number(t);break;case Object:case Array:try{i=JSON.parse(t)}catch(t){i=null}}return i}},u=(t,e)=>e!==t&&(e==e||t==t),v={attribute:!0,type:String,converter:p,reflect:!1,hasChanged:u};class b extends HTMLElement{constructor(){super(),this._$Ei=new Map,this.isUpdatePending=!1,this.hasUpdated=!1,this._$El=null,this.u()}static addInitializer(t){var e;this.finalize(),(null!==(e=this.h)&&void 0!==e?e:this.h=[]).push(t)}static get observedAttributes(){this.finalize();const t=[];return this.elementProperties.forEach(((e,i)=>{const s=this._$Ep(i,e);void 0!==s&&(this._$Ev.set(s,i),t.push(s))})),t}static createProperty(t,e=v){if(e.state&&(e.attribute=!1),this.finalize(),this.elementProperties.set(t,e),!e.noAccessor&&!this.prototype.hasOwnProperty(t)){const i="symbol"==typeof t?Symbol():"__"+t,s=this.getPropertyDescriptor(t,i,e);void 0!==s&&Object.defineProperty(this.prototype,t,s)}}static getPropertyDescriptor(t,e,i){return{get(){return this[e]},set(s){const o=this[t];this[e]=s,this.requestUpdate(t,o,i)},configurable:!0,enumerable:!0}}static getPropertyOptions(t){return this.elementProperties.get(t)||v}static finalize(){if(this.hasOwnProperty("finalized"))return!1;this.finalized=!0;const t=Object.getPrototypeOf(this);if(t.finalize(),void 0!==t.h&&(this.h=[...t.h]),this.elementProperties=new Map(t.elementProperties),this._$Ev=new Map,this.hasOwnProperty("properties")){const t=this.properties,e=[...Object.getOwnPropertyNames(t),...Object.getOwnPropertySymbols(t)];for(const i of e)this.createProperty(i,t[i])}return this.elementStyles=this.finalizeStyles(this.styles),!0}static finalizeStyles(t){const e=[];if(Array.isArray(t)){const i=new Set(t.flat(1/0).reverse());for(const t of i)e.unshift(r(t))}else void 0!==t&&e.push(r(t));return e}static _$Ep(t,e){const i=e.attribute;return!1===i?void 0:"string"==typeof i?i:"string"==typeof t?t.toLowerCase():void 0}u(){var t;this._$E_=new Promise((t=>this.enableUpdating=t)),this._$AL=new Map,this._$Eg(),this.requestUpdate(),null===(t=this.constructor.h)||void 0===t||t.forEach((t=>t(this)))}addController(t){var e,i;(null!==(e=this._$ES)&&void 0!==e?e:this._$ES=[]).push(t),void 0!==this.renderRoot&&this.isConnected&&(null===(i=t.hostConnected)||void 0===i||i.call(t))}removeController(t){var e;null===(e=this._$ES)||void 0===e||e.splice(this._$ES.indexOf(t)>>>0,1)}_$Eg(){this.constructor.elementProperties.forEach(((t,e)=>{this.hasOwnProperty(e)&&(this._$Ei.set(e,this[e]),delete this[e])}))}createRenderRoot(){var i;const s=null!==(i=this.shadowRoot)&&void 0!==i?i:this.attachShadow(this.constructor.shadowRootOptions);return((i,s)=>{e?i.adoptedStyleSheets=s.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet)):s.forEach((e=>{const s=document.createElement("style"),o=t.litNonce;void 0!==o&&s.setAttribute("nonce",o),s.textContent=e.cssText,i.appendChild(s)}))})(s,this.constructor.elementStyles),s}connectedCallback(){var t;void 0===this.renderRoot&&(this.renderRoot=this.createRenderRoot()),this.enableUpdating(!0),null===(t=this._$ES)||void 0===t||t.forEach((t=>{var e;return null===(e=t.hostConnected)||void 0===e?void 0:e.call(t)}))}enableUpdating(t){}disconnectedCallback(){var t;null===(t=this._$ES)||void 0===t||t.forEach((t=>{var e;return null===(e=t.hostDisconnected)||void 0===e?void 0:e.call(t)}))}attributeChangedCallback(t,e,i){this._$AK(t,i)}_$EO(t,e,i=v){var s;const o=this.constructor._$Ep(t,i);if(void 0!==o&&!0===i.reflect){const n=(void 0!==(null===(s=i.converter)||void 0===s?void 0:s.toAttribute)?i.converter:p).toAttribute(e,i.type);this._$El=t,null==n?this.removeAttribute(o):this.setAttribute(o,n),this._$El=null}}_$AK(t,e){var i;const s=this.constructor,o=s._$Ev.get(t);if(void 0!==o&&this._$El!==o){const t=s.getPropertyOptions(o),n="function"==typeof t.converter?{fromAttribute:t.converter}:void 0!==(null===(i=t.converter)||void 0===i?void 0:i.fromAttribute)?t.converter:p;this._$El=o,this[o]=n.fromAttribute(e,t.type),this._$El=null}}requestUpdate(t,e,i){let s=!0;void 0!==t&&(((i=i||this.constructor.getPropertyOptions(t)).hasChanged||u)(this[t],e)?(this._$AL.has(t)||this._$AL.set(t,e),!0===i.reflect&&this._$El!==t&&(void 0===this._$EC&&(this._$EC=new Map),this._$EC.set(t,i))):s=!1),!this.isUpdatePending&&s&&(this._$E_=this._$Ej())}async _$Ej(){this.isUpdatePending=!0;try{await this._$E_}catch(t){Promise.reject(t)}const t=this.scheduleUpdate();return null!=t&&await t,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){var t;if(!this.isUpdatePending)return;this.hasUpdated,this._$Ei&&(this._$Ei.forEach(((t,e)=>this[e]=t)),this._$Ei=void 0);let e=!1;const i=this._$AL;try{e=this.shouldUpdate(i),e?(this.willUpdate(i),null===(t=this._$ES)||void 0===t||t.forEach((t=>{var e;return null===(e=t.hostUpdate)||void 0===e?void 0:e.call(t)})),this.update(i)):this._$Ek()}catch(t){throw e=!1,this._$Ek(),t}e&&this._$AE(i)}willUpdate(t){}_$AE(t){var e;null===(e=this._$ES)||void 0===e||e.forEach((t=>{var e;return null===(e=t.hostUpdated)||void 0===e?void 0:e.call(t)})),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(t)),this.updated(t)}_$Ek(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$E_}shouldUpdate(t){return!0}update(t){void 0!==this._$EC&&(this._$EC.forEach(((t,e)=>this._$EO(e,this[e],t))),this._$EC=void 0),this._$Ek()}updated(t){}firstUpdated(t){}}var f;b.finalized=!0,b.elementProperties=new Map,b.elementStyles=[],b.shadowRootOptions={mode:"open"},null==c||c({ReactiveElement:b}),(null!==(l=a.reactiveElementVersions)&&void 0!==l?l:a.reactiveElementVersions=[]).push("1.5.0");const $=window,m=$.trustedTypes,g=m?m.createPolicy("lit-html",{createHTML:t=>t}):void 0,y=`lit$${(Math.random()+"").slice(9)}$`,_="?"+y,A=`<${_}>`,x=document,w=(t="")=>x.createComment(t),S=t=>null===t||"object"!=typeof t&&"function"!=typeof t,E=Array.isArray,O=t=>E(t)||"function"==typeof(null==t?void 0:t[Symbol.iterator]),C=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,U=/-->/g,M=/>/g,P=RegExp(">|[ \t\n\f\r](?:([^\\s\"'>=/]+)([ \t\n\f\r]*=[ \t\n\f\r]*(?:[^ \t\n\f\r\"'`<>=]|(\"|')|))|$)","g"),k=/'/g,I=/"/g,H=/^(?:script|style|textarea|title)$/i,T=t=>(e,...i)=>({_$litType$:t,strings:e,values:i}),N=T(1),R=(T(2),Symbol.for("lit-noChange")),L=Symbol.for("lit-nothing"),D=new WeakMap,z=x.createTreeWalker(x,129,null,!1),K=(t,e)=>{const i=t.length-1,s=[];let o,n=2===e?"<svg>":"",r=C;for(let e=0;e<i;e++){const i=t[e];let l,a,h=-1,d=0;for(;d<i.length&&(r.lastIndex=d,a=r.exec(i),null!==a);)d=r.lastIndex,r===C?"!--"===a[1]?r=U:void 0!==a[1]?r=M:void 0!==a[2]?(H.test(a[2])&&(o=RegExp("</"+a[2],"g")),r=P):void 0!==a[3]&&(r=P):r===P?">"===a[0]?(r=null!=o?o:C,h=-1):void 0===a[1]?h=-2:(h=r.lastIndex-a[2].length,l=a[1],r=void 0===a[3]?P:'"'===a[3]?I:k):r===I||r===k?r=P:r===U||r===M?r=C:(r=P,o=void 0);const c=r===P&&t[e+1].startsWith("/>")?" ":"";n+=r===C?i+A:h>=0?(s.push(l),i.slice(0,h)+"$lit$"+i.slice(h)+y+c):i+y+(-2===h?(s.push(void 0),e):c)}const l=n+(t[i]||"<?>")+(2===e?"</svg>":"");if(!Array.isArray(t)||!t.hasOwnProperty("raw"))throw Error("invalid template strings array");return[void 0!==g?g.createHTML(l):l,s]};class j{constructor({strings:t,_$litType$:e},i){let s;this.parts=[];let o=0,n=0;const r=t.length-1,l=this.parts,[a,h]=K(t,e);if(this.el=j.createElement(a,i),z.currentNode=this.el.content,2===e){const t=this.el.content,e=t.firstChild;e.remove(),t.append(...e.childNodes)}for(;null!==(s=z.nextNode())&&l.length<r;){if(1===s.nodeType){if(s.hasAttributes()){const t=[];for(const e of s.getAttributeNames())if(e.endsWith("$lit$")||e.startsWith(y)){const i=h[n++];if(t.push(e),void 0!==i){const t=s.getAttribute(i.toLowerCase()+"$lit$").split(y),e=/([.?@])?(.*)/.exec(i);l.push({type:1,index:o,name:e[2],strings:t,ctor:"."===e[1]?V:"?"===e[1]?Z:"@"===e[1]?G:W})}else l.push({type:6,index:o})}for(const e of t)s.removeAttribute(e)}if(H.test(s.tagName)){const t=s.textContent.split(y),e=t.length-1;if(e>0){s.textContent=m?m.emptyScript:"";for(let i=0;i<e;i++)s.append(t[i],w()),z.nextNode(),l.push({type:2,index:++o});s.append(t[e],w())}}}else if(8===s.nodeType)if(s.data===_)l.push({type:2,index:o});else{let t=-1;for(;-1!==(t=s.data.indexOf(y,t+1));)l.push({type:7,index:o}),t+=y.length-1}o++}}static createElement(t,e){const i=x.createElement("template");return i.innerHTML=t,i}}function B(t,e,i=t,s){var o,n,r,l;if(e===R)return e;let a=void 0!==s?null===(o=i._$Co)||void 0===o?void 0:o[s]:i._$Cl;const h=S(e)?void 0:e._$litDirective$;return(null==a?void 0:a.constructor)!==h&&(null===(n=null==a?void 0:a._$AO)||void 0===n||n.call(a,!1),void 0===h?a=void 0:(a=new h(t),a._$AT(t,i,s)),void 0!==s?(null!==(r=(l=i)._$Co)&&void 0!==r?r:l._$Co=[])[s]=a:i._$Cl=a),void 0!==a&&(e=B(t,a._$AS(t,e.values),a,s)),e}class q{constructor(t,e){this.u=[],this._$AN=void 0,this._$AD=t,this._$AM=e}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}v(t){var e;const{el:{content:i},parts:s}=this._$AD,o=(null!==(e=null==t?void 0:t.creationScope)&&void 0!==e?e:x).importNode(i,!0);z.currentNode=o;let n=z.nextNode(),r=0,l=0,a=s[0];for(;void 0!==a;){if(r===a.index){let e;2===a.type?e=new F(n,n.nextSibling,this,t):1===a.type?e=new a.ctor(n,a.name,a.strings,this,t):6===a.type&&(e=new Q(n,this,t)),this.u.push(e),a=s[++l]}r!==(null==a?void 0:a.index)&&(n=z.nextNode(),r++)}return o}p(t){let e=0;for(const i of this.u)void 0!==i&&(void 0!==i.strings?(i._$AI(t,i,e),e+=i.strings.length-2):i._$AI(t[e])),e++}}class F{constructor(t,e,i,s){var o;this.type=2,this._$AH=L,this._$AN=void 0,this._$AA=t,this._$AB=e,this._$AM=i,this.options=s,this._$Cm=null===(o=null==s?void 0:s.isConnected)||void 0===o||o}get _$AU(){var t,e;return null!==(e=null===(t=this._$AM)||void 0===t?void 0:t._$AU)&&void 0!==e?e:this._$Cm}get parentNode(){let t=this._$AA.parentNode;const e=this._$AM;return void 0!==e&&11===t.nodeType&&(t=e.parentNode),t}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(t,e=this){t=B(this,t,e),S(t)?t===L||null==t||""===t?(this._$AH!==L&&this._$AR(),this._$AH=L):t!==this._$AH&&t!==R&&this.g(t):void 0!==t._$litType$?this.$(t):void 0!==t.nodeType?this.T(t):O(t)?this.k(t):this.g(t)}O(t,e=this._$AB){return this._$AA.parentNode.insertBefore(t,e)}T(t){this._$AH!==t&&(this._$AR(),this._$AH=this.O(t))}g(t){this._$AH!==L&&S(this._$AH)?this._$AA.nextSibling.data=t:this.T(x.createTextNode(t)),this._$AH=t}$(t){var e;const{values:i,_$litType$:s}=t,o="number"==typeof s?this._$AC(t):(void 0===s.el&&(s.el=j.createElement(s.h,this.options)),s);if((null===(e=this._$AH)||void 0===e?void 0:e._$AD)===o)this._$AH.p(i);else{const t=new q(o,this),e=t.v(this.options);t.p(i),this.T(e),this._$AH=t}}_$AC(t){let e=D.get(t.strings);return void 0===e&&D.set(t.strings,e=new j(t)),e}k(t){E(this._$AH)||(this._$AH=[],this._$AR());const e=this._$AH;let i,s=0;for(const o of t)s===e.length?e.push(i=new F(this.O(w()),this.O(w()),this,this.options)):i=e[s],i._$AI(o),s++;s<e.length&&(this._$AR(i&&i._$AB.nextSibling,s),e.length=s)}_$AR(t=this._$AA.nextSibling,e){var i;for(null===(i=this._$AP)||void 0===i||i.call(this,!1,!0,e);t&&t!==this._$AB;){const e=t.nextSibling;t.remove(),t=e}}setConnected(t){var e;void 0===this._$AM&&(this._$Cm=t,null===(e=this._$AP)||void 0===e||e.call(this,t))}}class W{constructor(t,e,i,s,o){this.type=1,this._$AH=L,this._$AN=void 0,this.element=t,this.name=e,this._$AM=s,this.options=o,i.length>2||""!==i[0]||""!==i[1]?(this._$AH=Array(i.length-1).fill(new String),this.strings=i):this._$AH=L}get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}_$AI(t,e=this,i,s){const o=this.strings;let n=!1;if(void 0===o)t=B(this,t,e,0),n=!S(t)||t!==this._$AH&&t!==R,n&&(this._$AH=t);else{const s=t;let r,l;for(t=o[0],r=0;r<o.length-1;r++)l=B(this,s[i+r],e,r),l===R&&(l=this._$AH[r]),n||(n=!S(l)||l!==this._$AH[r]),l===L?t=L:t!==L&&(t+=(null!=l?l:"")+o[r+1]),this._$AH[r]=l}n&&!s&&this.j(t)}j(t){t===L?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,null!=t?t:"")}}class V extends W{constructor(){super(...arguments),this.type=3}j(t){this.element[this.name]=t===L?void 0:t}}const J=m?m.emptyScript:"";class Z extends W{constructor(){super(...arguments),this.type=4}j(t){t&&t!==L?this.element.setAttribute(this.name,J):this.element.removeAttribute(this.name)}}class G extends W{constructor(t,e,i,s,o){super(t,e,i,s,o),this.type=5}_$AI(t,e=this){var i;if((t=null!==(i=B(this,t,e,0))&&void 0!==i?i:L)===R)return;const s=this._$AH,o=t===L&&s!==L||t.capture!==s.capture||t.once!==s.once||t.passive!==s.passive,n=t!==L&&(s===L||o);o&&this.element.removeEventListener(this.name,this,s),n&&this.element.addEventListener(this.name,this,t),this._$AH=t}handleEvent(t){var e,i;"function"==typeof this._$AH?this._$AH.call(null!==(i=null===(e=this.options)||void 0===e?void 0:e.host)&&void 0!==i?i:this.element,t):this._$AH.handleEvent(t)}}class Q{constructor(t,e,i){this.element=t,this.type=6,this._$AN=void 0,this._$AM=e,this.options=i}get _$AU(){return this._$AM._$AU}_$AI(t){B(this,t)}}const X=$.litHtmlPolyfillSupport;null==X||X(j,F),(null!==(f=$.litHtmlVersions)&&void 0!==f?f:$.litHtmlVersions=[]).push("2.5.0");var Y,tt;class et extends b{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){var t,e;const i=super.createRenderRoot();return null!==(t=(e=this.renderOptions).renderBefore)&&void 0!==t||(e.renderBefore=i.firstChild),i}update(t){const e=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(t),this._$Do=((t,e,i)=>{var s,o;const n=null!==(s=null==i?void 0:i.renderBefore)&&void 0!==s?s:e;let r=n._$litPart$;if(void 0===r){const t=null!==(o=null==i?void 0:i.renderBefore)&&void 0!==o?o:null;n._$litPart$=r=new F(e.insertBefore(w(),t),t,void 0,null!=i?i:{})}return r._$AI(t),r})(e,this.renderRoot,this.renderOptions)}connectedCallback(){var t;super.connectedCallback(),null===(t=this._$Do)||void 0===t||t.setConnected(!0)}disconnectedCallback(){var t;super.disconnectedCallback(),null===(t=this._$Do)||void 0===t||t.setConnected(!1)}render(){return R}}et.finalized=!0,et._$litElement$=!0,null===(Y=globalThis.litElementHydrateSupport)||void 0===Y||Y.call(globalThis,{LitElement:et});const it=globalThis.litElementPolyfillSupport;null==it||it({LitElement:et});(null!==(tt=globalThis.litElementVersions)&&void 0!==tt?tt:globalThis.litElementVersions=[]).push("3.2.2");window.customElements.define("pressbooks-multiselect",class extends et{static get styles(){return n`
      :host {
        font-size: var(--pb-multiselect-font-size, 1rem);
      }

      .hidden {
        display: none;
      }

      .screen-reader-text {
        border: 0;
        clip: rect(1px, 1px, 1px, 1px);
        clip-path: inset(50%);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
        word-wrap: normal !important;
      }

      .selected-options {
        list-style-type: none;
        display: flex;
        gap: 0.5rem;
        padding-inline-start: 0;
      }

      .selected-options button {
        align-items: center;
        appearance: none;
        background: var(--pb-button-secondary-background, #f6f7f7);
        border-radius: var(--pb-button-border-radius, 3px);
        border: var(--pb-button-secondary-border, 1px #d4002d solid);
        box-sizing: border-box;
        color: var(--pb-button-secondary-color, #d4002d);
        cursor: pointer;
        display: inline-flex;
        font-size: var(--pb-button-font-size, 13px);
        gap: var(--pb-button-gap, 0.125em);
        line-height: var(--pb-button-line-height, 2.15384615);
        margin: 0;
        min-height: var(--pb-button-min-height, 30px);
        padding: var(--pb-button-padding, 0 10px);
        text-decoration: none;
        white-space: nowrap;
      }

      .selected-options button:hover {
        background: var(--pb-button-secondary-background-hover, #f0f0f1);
        border-color: var(--pb-button-secondary-border-color-hover, #a10022);
        color: var(--pb-button-secondary-color-hover, #a10022);
      }

      .selected-options button:focus {
        border-color: var(--pb-button-secondary-border-color-focus, #ff083c);
        box-shadow: var(
          --pb-button-secondary-box-shadow-focus,
          0 0 0 1px #ff083c
        );
        color: var(--pb-button-secondary-color-focus, #6e0017);
        outline: var(
          --pb-button-secondary-outline-focus,
          2px solid transparent
        );
        outline-offset: 0;
      }

      .selected-options button:active {
        background: var(--pb-button-secondary-background-active, #f6f7f7);
        border-color: var(--pb-button-secondary-border-color-active, #7e8993);
        box-shadow: none;
        color: var(--pb-button-secondary-color-active, #262a2e);
      }

      .selected-options button svg,
      .combo-option svg {
        width: var(--pb-button-icon-size, 1.25em);
        height: var(--pb-button-icon-size, 1.25em);
      }

      .combo-option {
        align-items: center;
        cursor: default;
        display: flex;
        gap: 0.125em;
        padding: 0.25rem 0.5rem;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
          Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
      }

      .combo-option:last-of-type {
        border-bottom-left-radius: var(--pb-input-border-radius, 4px);
        border-bottom-right-radius: var(--pb-input-border-radius, 4px);
      }

      .combo-option[aria-selected='true'] {
        background: #d4002d;
        color: white;
      }

      .combo-option:hover,
      .combo-option.option-current {
        background: #dedede;
        color: #000;
      }

      .combo-option:active,
      .combo-option:active:hover {
        background: #333;
        color: #fff;
      }

      input {
        background-color: var(--pb-input-background-color, #fff);
        border-radius: var(--pb-input-border-radius, 4px);
        border: var(--pb-input-border, 1px solid #8c8f94);
        box-shadow: var(--pb-input-box-shadow, 0 0 0 transparent);
        box-sizing: border-box;
        color: var(--pb-input-color, #2c3338);
        font-size: var(--pb-input-font-size, 14px);
        line-height: var(--pb-input-line-height, 2);
        min-height: var(--pb-input-min-height, 30px);
        padding: var(--pb-input-padding, 0 8px);
        width: var(--pb-input-width, 100%);
      }

      input:focus {
        border-color: var(--pb-input-border-color-focus, #d4002d);
        box-shadow: var(--pb-input-box-shadow-focus, 0 0 0 1px #d4002d);
        outline: var(--pb-input-outline-focus, 2px solid transparent);
      }

      /** TODO: Props etc. */
      input.combo-open {
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
      }

      .combo-menu {
        width: 100%;
        box-sizing: border-box;
        border-bottom: var(--pb-input-border, 1px solid #8c8f94);
        border-left: var(--pb-input-border, 1px solid #8c8f94);
        border-right: var(--pb-input-border, 1px solid #8c8f94);
        box-shadow: 0;
        border-bottom-left-radius: var(--pb-input-border-radius, 4px);
        border-bottom-right-radius: var(--pb-input-border-radius, 4px);
      }

      input:focus + .combo-menu {
        border-color: var(--pb-input-border-color-focus, #d4002d);
        box-shadow: var(--pb-input-box-shadow-focus, 0 0 0 1px #d4002d);
      }
    `}static get properties(){return{htmlId:{type:String},label:{type:String},hint:{type:String},activeIndex:{type:Number},value:{type:String},open:{type:Boolean},options:{type:Object},selectedOptions:{type:Array},filteredOptions:{type:Object},MenuActions:{type:Object},Keys:{type:Object}}}constructor(){super(),this.htmlId="",this.activeIndex=0,this.value="",this.open=!1,this.options={},this.selectedOptions=[],this.filteredOptions={},this.MenuActions={Close:"Close",CloseSelect:"CloseSelect",First:"First",Last:"Last",Next:"Next",Open:"Open",PageDown:"PageDown",PageUp:"PageUp",Previous:"Previous",Select:"Select",Space:"Space",Type:"Type"},this.Keys={Backspace:"Backspace",Clear:"Clear",Down:"ArrowDown",End:"End",Enter:"Enter",Escape:"Escape",Home:"Home",Left:"ArrowLeft",PageDown:"PageDown",PageUp:"PageUp",Right:"ArrowRight",Space:" ",Tab:"Tab",Up:"ArrowUp"}}get _label(){return this.shadowRoot.querySelector("slot").assignedElements().filter((t=>t.matches("label")))[0]}get _select(){return this.shadowRoot.querySelector("slot").assignedElements().filter((t=>t.matches("select[multiple]")))[0]}get _hint(){const t=this.shadowRoot.querySelector("slot");if(this._select.getAttribute("aria-describedby")){const e=this._select.getAttribute("aria-describedby");return t.assignedElements().filter((t=>t.matches(`#${e}`)))[0]}return!1}render(){return N`
      <div class="multiselect">
        <slot></slot>
        <ul class="selected-options">
          <span id="${this.htmlId}-remove" hidden>remove</span>
          ${this.selectedOptions.map((t=>N`<li>
              <button
                class="remove-option"
                type="button"
                aria-describedby="${this.htmlId}-remove"
                data-option="${t}"
                @click="${this.handleRemove}"
              >
                <span>${this.options[t]}</span
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  aria-hidden="true"
                  role="presentation"
                  fill="currentColor"
                >
                  <path
                    d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                  />
                </svg>
              </button>
            </li>`))}
        </ul>
        <div>
          <span class="screen-reader-text" id="${this.htmlId}-hint"
            >${this.hint??L}</span
          >
          <input
            aria-controls="${this.htmlId}-listbox"
            aria-activedescendant="${this.htmlId}-${this.activeIndex}"
            aria-autocomplete="list"
            aria-expanded="${this.open}"
            aria-haspopup="listbox"
            aria-label="${this.label}"
            aria-describedby="${this.htmlId}-hint"
            class="combo-input${this.open?" combo-open":""}"
            role="combobox"
            type="text"
            @input="${this.handleInput}"
            @focus="${this.handleInputFocus}"
            @keydown="${this.handleInputKeydown}"
          />
          <div
            class="combo-menu ${this.open?"":"hidden"}"
            role="listbox"
            aria-multiselectable="true"
            id="${this.htmlId}-listbox"
          >
            ${Object.keys(this.filteredOptions).map(((t,e)=>N` <div
                  class="combo-option ${this.activeIndex===e?"option-current":""}"
                  id="${this.htmlId}-${e}"
                  aria-selected="${this.selectedOptions.indexOf(t)>-1}"
                  role="option"
                  data-option="${t}"
                  @click="${this.handleOptionClick}"
                  @mouseDown="${this.handleOptionMouseDown}"
                >
                  ${this.options[t]}
                </div>`))}
          </div>
        </div>
      </div>
    `}connectedCallback(){super.connectedCallback(),window.addEventListener("click",this._handleWindowClick.bind(this))}disconnectedCallback(){window.removeEventListener("click",this._handleWindowClick.bind(this)),super.disconnectedCallback()}firstUpdated(){this._select.hidden=!0,this.htmlId=this._select.id,this.label=this._label.innerText,this.hint=this._hint.innerText,this.options=Object.fromEntries(Array.from(this._select.querySelectorAll("option")).map((t=>[t.value,t.textContent]))),this.selectedOptions=Array.from(this._select.querySelectorAll("option[selected]")).map((t=>t.value)),this.filteredOptions=this.options}_handleWindowClick(t){this.shadowRoot.contains(t.target)||this.contains(t.target)||(this.open=!1,this.update())}addOption(t){this._select.querySelector(`option[value="${t}"]`).setAttribute("selected",!0),this.selectedOptions.push(t)}removeOption(t){this._select.querySelector(`option[value="${t}"]`).removeAttribute("selected"),this.selectedOptions.splice(this.selectedOptions.indexOf(t),1)}updateMenuState(t){this.open=t}getUpdatedIndex(t,e,i){switch(i){case this.MenuActions.First:return 0;case this.MenuActions.Last:return e;case this.MenuActions.Previous:return Math.max(0,t-1);case this.MenuActions.Next:return Math.min(e,t+1);default:return t}}updateIndex(t){this.activeIndex=t,this.requestUpdate()}handleRemove(t){const{option:e}=t.target.closest("button").dataset;this.removeOption(e),this.updateMenuState(!1),this.requestUpdate()}handleInputFocus(){this.updateMenuState(!0)}handleInputKeydown(t){const e=Object.keys(this.filteredOptions).length-1,i=this.getActionFromKey(t,this.open);switch(i){case this.MenuActions.Next:case this.MenuActions.Last:case this.MenuActions.First:case this.MenuActions.Previous:return t.preventDefault(),this.updateIndex(this.getUpdatedIndex(this.activeIndex,e,i));case this.MenuActions.CloseSelect:return t.preventDefault(),this.updateOption(this.activeIndex);case this.MenuActions.Close:return t.preventDefault(),this.updateMenuState(!1);case this.MenuActions.Open:return this.updateMenuState(!0);default:return!1}}handleInput(t){this.open||(this.open=!0);const e=t.target.value.toLowerCase().trim();this.filteredOptions={};for(const[t,i]of Object.entries(this.options)){0===i.toLowerCase().indexOf(e)&&(this.filteredOptions[t]=i)}}handleOptionClick(t){const{option:e}=t.target.closest(".combo-option").dataset;this.selectedOptions.indexOf(e)>-1?this.removeOption(e):this.addOption(e),this.requestUpdate()}getActionFromKey(t,e){const{key:i,altKey:s,ctrlKey:o,metaKey:n}=t;if(!e&&["ArrowDown","ArrowUp","Enter"," ","Home","End"].includes(i))return this.MenuActions.Open;if(i===this.Keys.Backspace||i===this.Keys.Clear||1===i.length&&" "!==i&&!s&&!o&&!n)return this.MenuActions.Type;if(e){if(i===this.Keys.Down&&!s||i===this.Keys.Right)return this.MenuActions.Next;if(i===this.Keys.Up&&s)return this.MenuActions.CloseSelect;if(i===this.Keys.Up||i===this.Keys.Left)return this.MenuActions.Previous;if(i===this.Keys.Home)return this.MenuActions.First;if(i===this.Keys.End)return this.MenuActions.Last;if(i===this.Keys.PageUp)return this.MenuActions.PageUp;if(i===this.Keys.PageDown)return this.MenuActions.PageDown;if(i===this.Keys.Escape)return this.MenuActions.Close;if(i===this.Keys.Enter)return this.MenuActions.CloseSelect;if(i===this.Keys.Space)return this.MenuActions.Space}return!1}updateOption(t){const e=Object.keys(this.filteredOptions)[t];e&&(this.selectedOptions.indexOf(e)>-1?(this.removeOption(e),this.value=""):(this.addOption(e),this.value="",this.filteredOptions=this.options,this.activeIndex=Object.keys(this.filteredOptions).indexOf(e)),this.requestUpdate())}})})();