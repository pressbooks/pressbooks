/*! For license information please see pressbooks-reorderable-multiselect.js.LICENSE.txt */
(()=>{"use strict";const t=globalThis,e=t.ShadowRoot&&(void 0===t.ShadyCSS||t.ShadyCSS.nativeShadow)&&"adoptedStyleSheets"in Document.prototype&&"replace"in CSSStyleSheet.prototype,s=Symbol(),i=new WeakMap;class o{constructor(t,e,i){if(this._$cssResult$=!0,i!==s)throw Error("CSSResult is not constructable. Use `unsafeCSS` or `css` instead.");this.cssText=t,this.t=e}get styleSheet(){let t=this.o;const s=this.t;if(e&&void 0===t){const e=void 0!==s&&1===s.length;e&&(t=i.get(s)),void 0===t&&((this.o=t=new CSSStyleSheet).replaceSync(this.cssText),e&&i.set(s,t))}return t}toString(){return this.cssText}}const n=(t,...e)=>{const i=1===t.length?t[0]:e.reduce(((e,s,i)=>e+(t=>{if(!0===t._$cssResult$)return t.cssText;if("number"==typeof t)return t;throw Error("Value passed to 'css' function must be a 'css' function result: "+t+". Use 'unsafeCSS' to pass non-literal values, but take care to ensure page security.")})(s)+t[i+1]),t[0]);return new o(i,t,s)},r=(s,i)=>{if(e)s.adoptedStyleSheets=i.map((t=>t instanceof CSSStyleSheet?t:t.styleSheet));else for(const e of i){const i=document.createElement("style"),o=t.litNonce;void 0!==o&&i.setAttribute("nonce",o),i.textContent=e.cssText,s.appendChild(i)}},a=e?t=>t:t=>t instanceof CSSStyleSheet?(t=>{let e="";for(const s of t.cssRules)e+=s.cssText;return(t=>new o("string"==typeof t?t:t+"",void 0,s))(e)})(t):t,{is:l,defineProperty:c,getOwnPropertyDescriptor:h,getOwnPropertyNames:d,getOwnPropertySymbols:p,getPrototypeOf:u}=Object,b=globalThis,v=b.trustedTypes,$=v?v.emptyScript:"",m=b.reactiveElementPolyfillSupport,g=(t,e)=>t,f={toAttribute(t,e){switch(e){case Boolean:t=t?$:null;break;case Object:case Array:t=null==t?t:JSON.stringify(t)}return t},fromAttribute(t,e){let s=t;switch(e){case Boolean:s=null!==t;break;case Number:s=null===t?null:Number(t);break;case Object:case Array:try{s=JSON.parse(t)}catch(t){s=null}}return s}},y=(t,e)=>!l(t,e),_={attribute:!0,type:String,converter:f,reflect:!1,hasChanged:y};Symbol.metadata??=Symbol("metadata"),b.litPropertyMetadata??=new WeakMap;class A extends HTMLElement{static addInitializer(t){this._$Ei(),(this.l??=[]).push(t)}static get observedAttributes(){return this.finalize(),this._$Eh&&[...this._$Eh.keys()]}static createProperty(t,e=_){if(e.state&&(e.attribute=!1),this._$Ei(),this.elementProperties.set(t,e),!e.noAccessor){const s=Symbol(),i=this.getPropertyDescriptor(t,s,e);void 0!==i&&c(this.prototype,t,i)}}static getPropertyDescriptor(t,e,s){const{get:i,set:o}=h(this.prototype,t)??{get(){return this[e]},set(t){this[e]=t}};return{get(){return i?.call(this)},set(e){const n=i?.call(this);o.call(this,e),this.requestUpdate(t,n,s)},configurable:!0,enumerable:!0}}static getPropertyOptions(t){return this.elementProperties.get(t)??_}static _$Ei(){if(this.hasOwnProperty(g("elementProperties")))return;const t=u(this);t.finalize(),void 0!==t.l&&(this.l=[...t.l]),this.elementProperties=new Map(t.elementProperties)}static finalize(){if(this.hasOwnProperty(g("finalized")))return;if(this.finalized=!0,this._$Ei(),this.hasOwnProperty(g("properties"))){const t=this.properties,e=[...d(t),...p(t)];for(const s of e)this.createProperty(s,t[s])}const t=this[Symbol.metadata];if(null!==t){const e=litPropertyMetadata.get(t);if(void 0!==e)for(const[t,s]of e)this.elementProperties.set(t,s)}this._$Eh=new Map;for(const[t,e]of this.elementProperties){const s=this._$Eu(t,e);void 0!==s&&this._$Eh.set(s,t)}this.elementStyles=this.finalizeStyles(this.styles)}static finalizeStyles(t){const e=[];if(Array.isArray(t)){const s=new Set(t.flat(1/0).reverse());for(const t of s)e.unshift(a(t))}else void 0!==t&&e.push(a(t));return e}static _$Eu(t,e){const s=e.attribute;return!1===s?void 0:"string"==typeof s?s:"string"==typeof t?t.toLowerCase():void 0}constructor(){super(),this._$Ep=void 0,this.isUpdatePending=!1,this.hasUpdated=!1,this._$Em=null,this._$Ev()}_$Ev(){this._$ES=new Promise((t=>this.enableUpdating=t)),this._$AL=new Map,this._$E_(),this.requestUpdate(),this.constructor.l?.forEach((t=>t(this)))}addController(t){(this._$EO??=new Set).add(t),void 0!==this.renderRoot&&this.isConnected&&t.hostConnected?.()}removeController(t){this._$EO?.delete(t)}_$E_(){const t=new Map,e=this.constructor.elementProperties;for(const s of e.keys())this.hasOwnProperty(s)&&(t.set(s,this[s]),delete this[s]);t.size>0&&(this._$Ep=t)}createRenderRoot(){const t=this.shadowRoot??this.attachShadow(this.constructor.shadowRootOptions);return r(t,this.constructor.elementStyles),t}connectedCallback(){this.renderRoot??=this.createRenderRoot(),this.enableUpdating(!0),this._$EO?.forEach((t=>t.hostConnected?.()))}enableUpdating(t){}disconnectedCallback(){this._$EO?.forEach((t=>t.hostDisconnected?.()))}attributeChangedCallback(t,e,s){this._$AK(t,s)}_$EC(t,e){const s=this.constructor.elementProperties.get(t),i=this.constructor._$Eu(t,s);if(void 0!==i&&!0===s.reflect){const o=(void 0!==s.converter?.toAttribute?s.converter:f).toAttribute(e,s.type);this._$Em=t,null==o?this.removeAttribute(i):this.setAttribute(i,o),this._$Em=null}}_$AK(t,e){const s=this.constructor,i=s._$Eh.get(t);if(void 0!==i&&this._$Em!==i){const t=s.getPropertyOptions(i),o="function"==typeof t.converter?{fromAttribute:t.converter}:void 0!==t.converter?.fromAttribute?t.converter:f;this._$Em=i,this[i]=o.fromAttribute(e,t.type),this._$Em=null}}requestUpdate(t,e,s){if(void 0!==t){if(s??=this.constructor.getPropertyOptions(t),!(s.hasChanged??y)(this[t],e))return;this.P(t,e,s)}!1===this.isUpdatePending&&(this._$ES=this._$ET())}P(t,e,s){this._$AL.has(t)||this._$AL.set(t,e),!0===s.reflect&&this._$Em!==t&&(this._$Ej??=new Set).add(t)}async _$ET(){this.isUpdatePending=!0;try{await this._$ES}catch(t){Promise.reject(t)}const t=this.scheduleUpdate();return null!=t&&await t,!this.isUpdatePending}scheduleUpdate(){return this.performUpdate()}performUpdate(){if(!this.isUpdatePending)return;if(!this.hasUpdated){if(this.renderRoot??=this.createRenderRoot(),this._$Ep){for(const[t,e]of this._$Ep)this[t]=e;this._$Ep=void 0}const t=this.constructor.elementProperties;if(t.size>0)for(const[e,s]of t)!0!==s.wrapped||this._$AL.has(e)||void 0===this[e]||this.P(e,this[e],s)}let t=!1;const e=this._$AL;try{t=this.shouldUpdate(e),t?(this.willUpdate(e),this._$EO?.forEach((t=>t.hostUpdate?.())),this.update(e)):this._$EU()}catch(e){throw t=!1,this._$EU(),e}t&&this._$AE(e)}willUpdate(t){}_$AE(t){this._$EO?.forEach((t=>t.hostUpdated?.())),this.hasUpdated||(this.hasUpdated=!0,this.firstUpdated(t)),this.updated(t)}_$EU(){this._$AL=new Map,this.isUpdatePending=!1}get updateComplete(){return this.getUpdateComplete()}getUpdateComplete(){return this._$ES}shouldUpdate(t){return!0}update(t){this._$Ej&&=this._$Ej.forEach((t=>this._$EC(t,this[t]))),this._$EU()}updated(t){}firstUpdated(t){}}A.elementStyles=[],A.shadowRootOptions={mode:"open"},A[g("elementProperties")]=new Map,A[g("finalized")]=new Map,m?.({ReactiveElement:A}),(b.reactiveElementVersions??=[]).push("2.0.4");const S=globalThis,x=S.trustedTypes,w=x?x.createPolicy("lit-html",{createHTML:t=>t}):void 0,O="$lit$",E=`lit$${(Math.random()+"").slice(9)}$`,C="?"+E,k=`<${C}>`,U=document,M=()=>U.createComment(""),D=t=>null===t||"object"!=typeof t&&"function"!=typeof t,P=Array.isArray,T=t=>P(t)||"function"==typeof t?.[Symbol.iterator],R="[ \t\n\f\r]",H=/<(?:(!--|\/[^a-zA-Z])|(\/?[a-zA-Z][^>\s]*)|(\/?$))/g,j=/-->/g,N=/>/g,z=RegExp(`>|${R}(?:([^\\s"'>=/]+)(${R}*=${R}*(?:[^ \t\n\f\r"'\`<>=]|("|')|))|$)`,"g"),L=/'/g,I=/"/g,B=/^(?:script|style|textarea|title)$/i,F=t=>(e,...s)=>({_$litType$:t,strings:e,values:s}),K=F(1),q=(F(2),Symbol.for("lit-noChange")),W=Symbol.for("lit-nothing"),V=new WeakMap,J=U.createTreeWalker(U,129);function Z(t,e){if(!Array.isArray(t)||!t.hasOwnProperty("raw"))throw Error("invalid template strings array");return void 0!==w?w.createHTML(e):e}const G=(t,e)=>{const s=t.length-1,i=[];let o,n=2===e?"<svg>":"",r=H;for(let e=0;e<s;e++){const s=t[e];let a,l,c=-1,h=0;for(;h<s.length&&(r.lastIndex=h,l=r.exec(s),null!==l);)h=r.lastIndex,r===H?"!--"===l[1]?r=j:void 0!==l[1]?r=N:void 0!==l[2]?(B.test(l[2])&&(o=RegExp("</"+l[2],"g")),r=z):void 0!==l[3]&&(r=z):r===z?">"===l[0]?(r=o??H,c=-1):void 0===l[1]?c=-2:(c=r.lastIndex-l[2].length,a=l[1],r=void 0===l[3]?z:'"'===l[3]?I:L):r===I||r===L?r=z:r===j||r===N?r=H:(r=z,o=void 0);const d=r===z&&t[e+1].startsWith("/>")?" ":"";n+=r===H?s+k:c>=0?(i.push(a),s.slice(0,c)+O+s.slice(c)+E+d):s+E+(-2===c?e:d)}return[Z(t,n+(t[s]||"<?>")+(2===e?"</svg>":"")),i]};class Q{constructor({strings:t,_$litType$:e},s){let i;this.parts=[];let o=0,n=0;const r=t.length-1,a=this.parts,[l,c]=G(t,e);if(this.el=Q.createElement(l,s),J.currentNode=this.el.content,2===e){const t=this.el.content.firstChild;t.replaceWith(...t.childNodes)}for(;null!==(i=J.nextNode())&&a.length<r;){if(1===i.nodeType){if(i.hasAttributes())for(const t of i.getAttributeNames())if(t.endsWith(O)){const e=c[n++],s=i.getAttribute(t).split(E),r=/([.?@])?(.*)/.exec(e);a.push({type:1,index:o,name:r[2],strings:s,ctor:"."===r[1]?st:"?"===r[1]?it:"@"===r[1]?ot:et}),i.removeAttribute(t)}else t.startsWith(E)&&(a.push({type:6,index:o}),i.removeAttribute(t));if(B.test(i.tagName)){const t=i.textContent.split(E),e=t.length-1;if(e>0){i.textContent=x?x.emptyScript:"";for(let s=0;s<e;s++)i.append(t[s],M()),J.nextNode(),a.push({type:2,index:++o});i.append(t[e],M())}}}else if(8===i.nodeType)if(i.data===C)a.push({type:2,index:o});else{let t=-1;for(;-1!==(t=i.data.indexOf(E,t+1));)a.push({type:7,index:o}),t+=E.length-1}o++}}static createElement(t,e){const s=U.createElement("template");return s.innerHTML=t,s}}function X(t,e,s=t,i){if(e===q)return e;let o=void 0!==i?s._$Co?.[i]:s._$Cl;const n=D(e)?void 0:e._$litDirective$;return o?.constructor!==n&&(o?._$AO?.(!1),void 0===n?o=void 0:(o=new n(t),o._$AT(t,s,i)),void 0!==i?(s._$Co??=[])[i]=o:s._$Cl=o),void 0!==o&&(e=X(t,o._$AS(t,e.values),o,i)),e}class Y{constructor(t,e){this._$AV=[],this._$AN=void 0,this._$AD=t,this._$AM=e}get parentNode(){return this._$AM.parentNode}get _$AU(){return this._$AM._$AU}u(t){const{el:{content:e},parts:s}=this._$AD,i=(t?.creationScope??U).importNode(e,!0);J.currentNode=i;let o=J.nextNode(),n=0,r=0,a=s[0];for(;void 0!==a;){if(n===a.index){let e;2===a.type?e=new tt(o,o.nextSibling,this,t):1===a.type?e=new a.ctor(o,a.name,a.strings,this,t):6===a.type&&(e=new nt(o,this,t)),this._$AV.push(e),a=s[++r]}n!==a?.index&&(o=J.nextNode(),n++)}return J.currentNode=U,i}p(t){let e=0;for(const s of this._$AV)void 0!==s&&(void 0!==s.strings?(s._$AI(t,s,e),e+=s.strings.length-2):s._$AI(t[e])),e++}}class tt{get _$AU(){return this._$AM?._$AU??this._$Cv}constructor(t,e,s,i){this.type=2,this._$AH=W,this._$AN=void 0,this._$AA=t,this._$AB=e,this._$AM=s,this.options=i,this._$Cv=i?.isConnected??!0}get parentNode(){let t=this._$AA.parentNode;const e=this._$AM;return void 0!==e&&11===t?.nodeType&&(t=e.parentNode),t}get startNode(){return this._$AA}get endNode(){return this._$AB}_$AI(t,e=this){t=X(this,t,e),D(t)?t===W||null==t||""===t?(this._$AH!==W&&this._$AR(),this._$AH=W):t!==this._$AH&&t!==q&&this._(t):void 0!==t._$litType$?this.$(t):void 0!==t.nodeType?this.T(t):T(t)?this.k(t):this._(t)}S(t){return this._$AA.parentNode.insertBefore(t,this._$AB)}T(t){this._$AH!==t&&(this._$AR(),this._$AH=this.S(t))}_(t){this._$AH!==W&&D(this._$AH)?this._$AA.nextSibling.data=t:this.T(U.createTextNode(t)),this._$AH=t}$(t){const{values:e,_$litType$:s}=t,i="number"==typeof s?this._$AC(t):(void 0===s.el&&(s.el=Q.createElement(Z(s.h,s.h[0]),this.options)),s);if(this._$AH?._$AD===i)this._$AH.p(e);else{const t=new Y(i,this),s=t.u(this.options);t.p(e),this.T(s),this._$AH=t}}_$AC(t){let e=V.get(t.strings);return void 0===e&&V.set(t.strings,e=new Q(t)),e}k(t){P(this._$AH)||(this._$AH=[],this._$AR());const e=this._$AH;let s,i=0;for(const o of t)i===e.length?e.push(s=new tt(this.S(M()),this.S(M()),this,this.options)):s=e[i],s._$AI(o),i++;i<e.length&&(this._$AR(s&&s._$AB.nextSibling,i),e.length=i)}_$AR(t=this._$AA.nextSibling,e){for(this._$AP?.(!1,!0,e);t&&t!==this._$AB;){const e=t.nextSibling;t.remove(),t=e}}setConnected(t){void 0===this._$AM&&(this._$Cv=t,this._$AP?.(t))}}class et{get tagName(){return this.element.tagName}get _$AU(){return this._$AM._$AU}constructor(t,e,s,i,o){this.type=1,this._$AH=W,this._$AN=void 0,this.element=t,this.name=e,this._$AM=i,this.options=o,s.length>2||""!==s[0]||""!==s[1]?(this._$AH=Array(s.length-1).fill(new String),this.strings=s):this._$AH=W}_$AI(t,e=this,s,i){const o=this.strings;let n=!1;if(void 0===o)t=X(this,t,e,0),n=!D(t)||t!==this._$AH&&t!==q,n&&(this._$AH=t);else{const i=t;let r,a;for(t=o[0],r=0;r<o.length-1;r++)a=X(this,i[s+r],e,r),a===q&&(a=this._$AH[r]),n||=!D(a)||a!==this._$AH[r],a===W?t=W:t!==W&&(t+=(a??"")+o[r+1]),this._$AH[r]=a}n&&!i&&this.j(t)}j(t){t===W?this.element.removeAttribute(this.name):this.element.setAttribute(this.name,t??"")}}class st extends et{constructor(){super(...arguments),this.type=3}j(t){this.element[this.name]=t===W?void 0:t}}class it extends et{constructor(){super(...arguments),this.type=4}j(t){this.element.toggleAttribute(this.name,!!t&&t!==W)}}class ot extends et{constructor(t,e,s,i,o){super(t,e,s,i,o),this.type=5}_$AI(t,e=this){if((t=X(this,t,e,0)??W)===q)return;const s=this._$AH,i=t===W&&s!==W||t.capture!==s.capture||t.once!==s.once||t.passive!==s.passive,o=t!==W&&(s===W||i);i&&this.element.removeEventListener(this.name,this,s),o&&this.element.addEventListener(this.name,this,t),this._$AH=t}handleEvent(t){"function"==typeof this._$AH?this._$AH.call(this.options?.host??this.element,t):this._$AH.handleEvent(t)}}class nt{constructor(t,e,s){this.element=t,this.type=6,this._$AN=void 0,this._$AM=e,this.options=s}get _$AU(){return this._$AM._$AU}_$AI(t){X(this,t)}}const rt=S.litHtmlPolyfillSupport;rt?.(Q,tt),(S.litHtmlVersions??=[]).push("3.1.2");class at extends A{constructor(){super(...arguments),this.renderOptions={host:this},this._$Do=void 0}createRenderRoot(){const t=super.createRenderRoot();return this.renderOptions.renderBefore??=t.firstChild,t}update(t){const e=this.render();this.hasUpdated||(this.renderOptions.isConnected=this.isConnected),super.update(t),this._$Do=((t,e,s)=>{const i=s?.renderBefore??e;let o=i._$litPart$;if(void 0===o){const t=s?.renderBefore??null;i._$litPart$=o=new tt(e.insertBefore(M(),t),t,void 0,s??{})}return o._$AI(t),o})(e,this.renderRoot,this.renderOptions)}connectedCallback(){super.connectedCallback(),this._$Do?.setConnected(!0)}disconnectedCallback(){super.disconnectedCallback(),this._$Do?.setConnected(!1)}render(){return q}}at._$litElement$=!0,at.finalized=!0,globalThis.litElementHydrateSupport?.({LitElement:at});const lt=globalThis.litElementPolyfillSupport;lt?.({LitElement:at});(globalThis.litElementVersions??=[]).push("4.0.4");const ct=t=>t??W;window.customElements.define("pressbooks-reorderable-multiselect",class extends at{static get styles(){return n`
      :host {
        font-size: var(--pb-multiselect-font-size, 1rem);
      }

      * {
        box-sizing: border-box;
      }

      .visually-hidden {
        height: 1px;
        overflow: hidden;
        position: absolute;
        width: 1px;
        clip: rect(1px 1px 1px 1px);
        clip: rect(1px, 1px, 1px, 1px);
        font-size: 14px;
        white-space: nowrap;
      }

      label {
        color: var(--pb-label-color, #000);
        display: block;
        font-family: var(
          --pb-label-font-family,
          -apple-system,
          BlinkMacSystemFont,
          'Segoe UI',
          Roboto,
          Oxygen-Sans,
          Ubuntu,
          Cantarell,
          'Helvetica Neue',
          sans-serif
        );
        font-size: var(--pb-label-font-size, 0.8125rem);
        font-weight: var(--pb-label-font-weight, 600);
        line-height: var(--pb-label-font-size, 0.7222);
        margin: var(--pb-label-margin, 0.3125rem 0);
      }

      .hint {
        font-size: var(--pb-hint-font-size, 0.6875rem);
        margin-bottom: var(--pb-hint-margin-bottom, 0);
        margin-top: var(--pb-hint-margin-top, 0.1875rem);
      }

      button {
        align-items: center;
        appearance: none;
        background: var(--pb-button-secondary-background, #f6f7f7);
        border: var(--pb-button-secondary-border, 1px #d4002d solid);
        border-radius: var(--pb-button-border-radius, 3px);
        color: var(--pb-button-secondary-color, #d4002d);
        cursor: pointer;
        display: inline-flex;
        font-family: var(
          --pb-button-font-family,
          -apple-system,
          BlinkMacSystemFont,
          'Segoe UI',
          Roboto,
          Oxygen-Sans,
          Ubuntu,
          Cantarell,
          'Helvetica Neue',
          sans-serif
        );
        font-size: var(--pb-button-font-size, 13px);
        gap: var(--pb-button-gap, 0.125em);
        line-height: var(--pb-button-line-height, 2.15384615);
        margin: 0;
        min-height: var(--pb-button-min-height, 30px);
        padding: var(--pb-button-padding, 0 10px);
        text-decoration: none;
        white-space: nowrap;
      }

      button:hover {
        background: var(--pb-button-secondary-background-hover, #f0f0f1);
        border-color: var(--pb-button-secondary-border-color-hover, #a10022);
        color: var(--pb-button-secondary-color-hover, #a10022);
      }

      button:focus {
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

      button:active {
        background: var(--pb-button-secondary-background-active, #f6f7f7);
        border-color: var(--pb-button-secondary-border-color-active, #7e8993);
        box-shadow: none;
        color: var(--pb-button-secondary-color-active, #262a2e);
      }

      button:disabled,
      button:disabled:hover {
        background: var(--pb-button-secondary-background, #f6f7f7);
        border-color: var(--pb-button-secondary-colr-disabled, #dcdcde);
        color: var(--pb-button-secondary-colr-disabled, #a7aaad);
        cursor: default;
      }

      select {
        -webkit-appearance: none;
        background: #fff
          url(data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%206l5%205%205-5%202%201-7%207-7-7%202-1z%22%20fill%3D%22%23555%22%2F%3E%3C%2Fsvg%3E)
          no-repeat right 0.3125rem top 55%;
        background-size: 1rem 1rem;
        border-color: #8c8f94;
        border-radius: 0.1875rem;
        box-shadow: none;
        color: #2c3338;
        cursor: pointer;

        font-size: 0.875rem;
        line-height: 2;
        margin: 0.1875rem 0.5rem 0.1875rem 0;
        max-width: 25rem;
        min-height: 1.875rem;
        min-width: 12.5rem;
        padding: 0 0.5rem;
        width: 100%;
      }

      select:focus {
        border-color: #d4002d;
        outline-color: #d4002d;
      }

      .selected-options {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        width: 100%;
      }

      .selected-options-controls {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
      }

      [role='listbox'] {
        border: 1px solid var(--pb-listbox-border-color, #8c8f94);
        border-radius: 2px;
        height: auto;
        list-style: none;
        margin: 0;
        max-height: 12rem;
        max-width: var(--pb-selected-options-max-width, 100%);
        overflow-y: scroll;
        padding: 0;
        width: var(--pb-selected-options-width, 66%);
      }

      [role='listbox']:focus-visible {
        border-color: #d4002d;
        outline-color: #d4002d;
      }

      [role='option'] {
        background: var(--pb-listbox-option-background, #fff);
        cursor: default;
        font-family: var(
          --pb-listbox-option-font-family,
          -apple-system,
          BlinkMacSystemFont,
          'Segoe UI',
          Roboto,
          Oxygen-Sans,
          Ubuntu,
          Cantarell,
          'Helvetica Neue',
          sans-serif
        );
        padding: var(--pb-listbox-option-padding, 0.25rem 0.5rem);
      }

      [role='option'][aria-selected='true'] {
        background: var(--pb-listbox-option-background-selected, #d4002d);
        color: var(--pb-listbox-option-color-selected, #fff);
      }
    `}static get properties(){return{messages:{type:Object},name:{type:String},label:{type:String},hint:{type:String},listBoxHasFocus:{type:Boolean},activeDescendant:{type:String},options:{type:Object},selectedOptions:{type:Object},availableOptions:{type:Object},selectedAvailableOption:{type:String},actionMessage:{type:String},ListboxActions:{type:Object},Keys:{type:Object}}}constructor(){super(),this.messages={},this.label="",this.hint="",this.listBoxHasFocus=!1,this.activeDescendant=null,this.options={},this.selectedOptions={},this.availableOptions={},this.selectedAvailableOption="",this.actionMessage=null,this.ListboxActions={MoveUp:"MoveUp",MoveDown:"MoveDown",MoveSelectionUp:"MoveSelectionUp",MoveSelectionDown:"MoveSelectionDown",Remove:"Remove"},this.Keys={Backspace:"Backspace",Clear:"Clear",Down:"ArrowDown",End:"End",Enter:"Enter",Escape:"Escape",Home:"Home",Left:"ArrowLeft",PageDown:"PageDown",PageUp:"PageUp",Right:"ArrowRight",Space:" ",Tab:"Tab",Up:"ArrowUp"}}labelTemplate(){return K`<label id="${this.name}-label">${this.label}</label>`}hintTemplate(){return K`<p id="${this.name}-hint" class="hint">${this.hint}</p>`}selectedOptionsTemplate(){return K` ${Object.keys(this.selectedOptions).map((t=>K`<input type="hidden" name="${this.name}[]" .value=${t} />`))}
      <ul
        class="selected-options-list"
        role="listbox"
        aria-labelledby="${this.name}-label"
        aria-activedescendant=${ct(this.activeDescendant)}
        tabindex="0"
        @keydown=${this._handleKeydown}
        @focus=${this._handleFocus}
        @blur=${this._handleBlur}
      >
        ${Object.entries(this.selectedOptions).map((t=>K`<li
              role="option"
              id=${t[0]}
              aria-selected=${this.activeDescendant===t[0]}
              @click=${this._handleClick}
              @keydown=${this._handleKeydown}
            >
              ${t[1]}
            </li>`))}
      </ul>`}availableOptionsTemplate(){return K` <select
        id="available-options"
        @change="${this._handleSelectChange}"
        aria-label="${this.messages["Available options"]??"Available options"}"
        ?disabled="${0===Object.keys(this.availableOptions).length}"
      >
        ${Object.entries(this.availableOptions).map((t=>Object.keys(this.selectedOptions).includes(t)?null:K`<option value="${t[0]}">${t[1]}</option>`))}
      </select>
      <button
        type="button"
        class="add"
        @click=${this._handleClick}
        ?disabled="${0===Object.keys(this.availableOptions).length}"
      >
        ${this.messages.Add??"Add"}
      </button>`}selectedOptionsControlsTemplate(){return K`
      <div class="selected-options-controls">
        <button
          type="button"
          class="move-up"
          tabindex=${this.activeDescendant?0:-1}
          aria-keyshortcuts="Alt+ArrowUp"
          @click=${this._handleClick}
          ?disabled="${!this.activeDescendant||0===Object.keys(this.selectedOptions).indexOf(this.activeDescendant)}"
        >
          ${this.messages["Move Up"]??"Move Up"}
        </button>
        <button
          type="button"
          class="move-down"
          tabindex=${this.activeDescendant?0:-1}
          aria-keyshortcuts="Alt+ArrowDown"
          @click=${this._handleClick}
          ?disabled="${!this.activeDescendant||Object.keys(this.selectedOptions).indexOf(this.activeDescendant)===Object.keys(this.selectedOptions).length-1}"
        >
          ${this.messages["Move Down"]??"Move Down"}
        </button>
        <button
          type="button"
          class="remove"
          tabindex=${this.activeDescendant?0:-1}
          @click=${this._handleClick}
          ?disabled="${!this.activeDescendant}"
        >
          ${this.messages.Remove??"Remove"}
        </button>
      </div>
    `}liveRegionTemplate(){return K`
      <div class="visually-hidden" aria-live="polite">
        ${ct(this.actionMessage)}
      </div>
    `}render(){return K` ${this.labelTemplate()}
      <div class="selected-options">
        ${this.selectedOptionsTemplate()}
        ${this.selectedOptionsControlsTemplate()}
      </div>
      ${this.availableOptionsTemplate()} ${this.hintTemplate()}
      ${this.liveRegionTemplate()}`}connectedCallback(){super.connectedCallback(),this.dataset.messages&&(this.messages=JSON.parse(this.dataset.messages)),this.label=this.querySelector("label").innerText,this.hint=this.querySelector("hint").innerText;const t=this.querySelectorAll("option"),e=this.querySelector('input[type="hidden"]');this.name=e.getAttribute("name"),Array.prototype.forEach.call(t,(t=>{this.options[t.getAttribute("value")]=t.innerText})),e.value&&Array.prototype.forEach.call(e.value.split(","),(t=>{this.selectedOptions[t]=this.options[t]})),e.remove(),this.querySelector("label").remove(),this.querySelector("hint").remove(),this.querySelector("select").remove(),this.updateAvailableOptions(),this.updateSelectedOptions()}disconnectedCallback(){super.disconnectedCallback()}_handleSelectChange(t){this.selectedAvailableOption=t.target.value}_handleFocus(){this.activeDescendant=Object.keys(this.selectedOptions)[0]}_handleKeydown(t){switch(this.getActionFromKey(t)){case this.ListboxActions.MoveUp:return this.updateIndex(-1);case this.ListboxActions.MoveSelectionUp:return this.updateSelectedIndex(-1);case this.ListboxActions.MoveDown:return this.updateIndex(1);case this.ListboxActions.MoveSelectionDown:return this.updateSelectedIndex(1);default:return!1}}_handleClick(t){t.target.closest('[role="option"]')?this.activeDescendant=t.target.closest('[role="option"]').id:t.target.closest(".move-up")?this.updateIndex(-1):t.target.closest(".move-down")?this.updateIndex(1):t.target.closest(".remove")?(delete this.selectedOptions[this.activeDescendant],this.actionMessage=this.messages["Removed $1 from selection"]?this.messages["Removed $1 from selection"].replace("$1",this.options[this.activeDescendant]):`Removed ${this.options[this.activeDescendant]} from selection`,this.updateAvailableOptions(),this.activeDescendant=null):t.target.closest(".add")&&(this.selectedOptions[this.selectedAvailableOption]=this.options[this.selectedAvailableOption],this.actionMessage=this.messages["Added $1 to selection"]?this.messages["Added $1 to selection"].replace("$1",this.options[this.activeDescendant]):`Added ${this.options[this.activeDescendant]} to selection`,this.updateAvailableOptions()),this.updateSelectedOptions()}getActionFromKey(t){const{key:e,altKey:s}=t;return e===this.Keys.Down&&s?this.ListboxActions.MoveDown:e===this.Keys.Down?this.ListboxActions.MoveSelectionDown:e===this.Keys.Up&&s?this.ListboxActions.MoveUp:e===this.Keys.Up&&this.ListboxActions.MoveSelectionUp}updateSelectedOptions(){const t=this.querySelector("input");if(t)t.setAttribute("value",Object.keys(this.selectedOptions).join(","));else{const t=document.createElement("input");t.setAttribute("type","hidden"),t.setAttribute("name",`${this.name}[]`),t.setAttribute("value",Object.keys(this.selectedOptions).join(",")),this.appendChild(t)}}updateAvailableOptions(){this.availableOptions=Object.keys(this.options).reduce(((t,e)=>(Object.prototype.hasOwnProperty.call(this.selectedOptions,e)||(t[e]=this.options[e]),t)),{}),this.selectedAvailableOption=Object.keys(this.availableOptions)[0]}updateIndex(t){const e=Object.entries(this.selectedOptions),s=Object.keys(this.selectedOptions).indexOf(this.activeDescendant),i=s+t;if(i>=0&&i<e.length){const t=e.splice(s,1)[0];e.splice(i,0,t)}this.selectedOptions=Object.fromEntries(e),this.actionMessage=this.messages["Moved to position $1"]?this.messages["Moved to position $1"].replace("$1",i+1):`Moved to position ${i+1}`}updateSelectedIndex(t){const e=Object.entries(this.selectedOptions),s=Object.keys(this.selectedOptions).indexOf(this.activeDescendant)+t;s>=0&&s<e.length&&(this.activeDescendant=Object.keys(this.selectedOptions)[s])}setFocus(t){this.listBoxHasFocus=t}})})();