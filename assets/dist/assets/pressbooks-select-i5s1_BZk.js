import{i as p,a as u,b as s,A as d}from"./lit-element-BlzW_tbz.js";function*h(a,t){if(a!==void 0){let e=0;for(const o of a)yield t(o,e++)}}class b extends p{static get styles(){return u`
      :host {
        font-size: var(--pb-select-font-size, 1rem);
      }

      * {
        box-sizing: border-box;
      }

      .hidden {
        display: none;
      }

      .selected-options {
        display: flex;
        flex-flow: var(--pb-selected-options-flex-direction, row) wrap;
        gap: 0.5rem;
        list-style-type: none;
        max-width: var(--pb-selected-options-max-width, 100%);
        padding-inline-start: 0;
        width: var(--pb-selected-options-width, 100%);
      }

      .selected-options:not(:has(li)) {
        margin-block: 0;
      }

      .selected-options button {
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
          "Segoe UI",
          Roboto,
          Oxygen-Sans,
          Ubuntu,
          Cantarell,
          "Helvetica Neue",
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

      .selected-options button svg {
        height: var(--pb-button-icon-size, 1.25em);
        width: var(--pb-button-icon-size, 1.25em);
      }

      .selected-options button[disabled] {
        background: var(--pb-button-background-disabled, #f6f7f7) !important;
        border-color: var(
          --pb-button-border-color-disabled,
          #dcdcde
        ) !important;
        box-shadow: var(--pb-button-box-shadow-disabled, none) !important;
        color: var(--pb-button-color-disabled, #a7aaad) !important;
        cursor: default;
        transform: none !important;
      }

      .combo-container {
        margin-block-start: 1em;
        max-width: var(--pb-combo-container-max-width, 100%);
        position: relative;
        width: var(--pb-combo-container-width, 100%);
      }

      input {
        background-color: var(--pb-input-background, #fff);
        border: var(--pb-input-border, 1px solid #8c8f94);
        border-radius: var(--pb-input-border-radius, 4px);
        box-shadow: var(--pb-input-box-shadow, 0 0 0 transparent);
        color: var(--pb-input-color, #2c3338);
        font-family: var(
          --pb-input-font-family,
          -apple-system,
          BlinkMacSystemFont,
          "Segoe UI",
          Roboto,
          Oxygen-Sans,
          Ubuntu,
          Cantarell,
          "Helvetica Neue",
          sans-serif
        );
        font-size: var(--pb-input-font-size, 14px);
        line-height: var(--pb-input-line-height, 2);
        max-width: 100%;
        min-height: var(--pb-input-min-height, 30px);
        padding: var(--pb-input-padding, 0 8px);
        width: var(--pb-input-width, 100%);
      }

      input[data-multiple="false"] {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%232c3338' class='size-5'%3E%3Cpath fill-rule='evenodd' d='M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z' clip-rule='evenodd' /%3E%3C/svg%3E%0A");
        background-repeat: no-repeat;
        background-position: center right;
        padding: var(--pb-select-input-padding, 0 32px 0 8px);
      }

      input:focus {
        border-color: var(--pb-input-border-color-focus, #d4002d);
        box-shadow: var(--pb-input-box-shadow-focus, 0 0 0 1px #d4002d);
        outline: var(--pb-input-outline-focus, 2px solid transparent);
      }

      input:disabled {
        background: var(
          --pb-input-background-disabled,
          rgba(255 255 255 / 50%)
        );
        border-color: var(
          --pb-input-border-color-disabled,
          rgba(220, 220, 222, 0.75)
        );
        box-shadow: var(
          --pb-input-box-shadow-disabled,
          inset 0 1px 2px rgba(0, 0, 0, 0.04)
        );
        color: var(--pb-input-color-disabled, rgba(44, 51, 56, 0.5));
      }

      input.combo-open {
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
      }

      .combo-menu {
        background-color: var(--pb-combo-menu-background, #fff);
        border-bottom: var(--pb-combo-menu-border, 1px solid #8c8f94);
        border-bottom-left-radius: var(--pb-combo-menu-border-radius, 4px);
        border-bottom-right-radius: var(--pb-combo-menu-border-radius, 4px);
        border-left: var(--pb-combo-menu-border, 1px solid #8c8f94);
        border-right: var(--pb-combo-menu-border, 1px solid #8c8f94);
        box-shadow: 0;
        box-sizing: border-box;
        height: auto;
        margin: 0;
        max-height: 20rem;
        overflow-y: scroll;
        padding-inline-start: 0;
        position: absolute;
        width: 100%;
        z-index: var(--pb-combo-menu-z-index, 1);
      }

      .combo-group {
        margin: 0;
        padding-inline-start: 0;
      }

      input:focus + .combo-menu {
        border-color: var(--pb-input-border-color-focus, #d4002d);
        box-shadow: var(--pb-input-box-shadow-focus, 0 0 0 1px #d4002d);
      }

      .combo-option {
        background: var(--pb-combo-option-background, #fff);
      }

      .combo-group-label {
        background: var(--pb-combo-group-label-background, #f0f0f1);
        font-weight: 600;
      }

      .combo-option,
      .combo-group-label {
        cursor: default;
        font-family: var(
          --pb-combo-option-font-family,
          -apple-system,
          BlinkMacSystemFont,
          "Segoe UI",
          Roboto,
          Oxygen-Sans,
          Ubuntu,
          Cantarell,
          "Helvetica Neue",
          sans-serif
        );
        list-style: none;
        padding: var(--pb-combo-option-padding, 0.25rem 0.5rem);
      }

      .combo-group .combo-option {
        padding-inline-start: 1.25rem;
      }

      .combo-option:hover,
      .combo-option.option-current {
        background: var(--pb-combo-option-background-hover, #dedede);
        color: var(--pb-combo-option-color-hover, #000);
      }

      .combo-option:active,
      .combo-option:active:hover {
        background: var(--pb-combo-option-background-active, #333);
        color: var(--pb-combo-option-color-active, #fff);
      }

      .combo-option[aria-selected="true"] {
        background: var(--pb-combo-option-background-selected, #d4002d);
        color: var(--pb-combo-option-color-selected, #fff);
      }

      .combo-option:last-of-type {
        border-bottom-left-radius: var(--pb-combo-menu-border-radius, 3px);
        border-bottom-right-radius: var(--pb-combo-menu-border-radius, 3px);
      }
    `}static get properties(){return{htmlId:{type:String},callFocus:{type:Boolean},ignoreBlur:{type:Boolean},disabled:{type:Boolean},max:{type:Number},label:{type:String},hint:{type:String},activeIndex:{type:Number},value:{type:String},open:{type:Boolean},multiple:{type:Boolean},groups:{type:Array},options:{type:Object},selectedOptions:{type:Array},filteredOptions:{type:Object},MenuActions:{type:Object},Keys:{type:Object}}}constructor(){super(),this.max=0,this.htmlId="",this.activeIndex=0,this.value="",this.callFocus=!1,this.ignoreBlur=!1,this.open=!1,this.multiple=!1,this.groups=[],this.options={},this.selectedOptions=[],this.filteredOptions={},this.MenuActions={Close:"Close",CloseSelect:"CloseSelect",First:"First",Last:"Last",Next:"Next",Open:"Open",PageDown:"PageDown",PageUp:"PageUp",Previous:"Previous",Select:"Select",Space:"Space",Type:"Type"},this.Keys={Backspace:"Backspace",Clear:"Clear",Down:"ArrowDown",End:"End",Enter:"Enter",Escape:"Escape",Home:"Home",Left:"ArrowLeft",PageDown:"PageDown",PageUp:"PageUp",Right:"ArrowRight",Space:" ",Tab:"Tab",Up:"ArrowUp"}}get _label(){return this.shadowRoot.querySelector("slot").assignedElements().filter(e=>e.matches("label"))[0]}get _select(){return this.shadowRoot.querySelector("slot").assignedElements().filter(e=>e.matches("select"))[0]}get _hint(){const t=this.shadowRoot.querySelector("slot:not([name])"),e=this.shadowRoot.querySelector('slot[name="after"]');if(this._select.getAttribute("aria-describedby")){const o=this._select.getAttribute("aria-describedby"),n=t.assignedElements().filter(r=>r.matches(`#${o}`))[0];if(n)return n;if(e){const l=e.assignedElements().filter(c=>c.matches(`#${o}`))[0];if(l)return l}}return!1}get _input(){return this.shadowRoot.querySelector("input")}get _selectionLessThanMax(){return this.max>0?this.selectedOptions.length<this.max:!0}selectionsTemplate(){return s` <span id="${this.htmlId}-remove" hidden>remove</span>
      <ul class="selected-options">
        ${this.selectedOptions.map(t=>s`<li>
              <button
                class="remove-option"
                type="button"
                ?disabled="${this.disabled}"
                aria-describedby="${this.htmlId}-remove"
                data-option="${t}"
                @click="${this._handleRemove}"
              >
                <span>${this.options[t].label}</span
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
            </li>`)}
      </ul>`}hintTemplate(){return s`<span id="${this.htmlId}-hint" hidden>${this.hint}</span>`}comboBoxTemplate(){const t={};for(const e of this.groups)t[e]=[];return Object.keys(this.filteredOptions).forEach((e,o)=>{const{group:i}=this.options[e];t[i??"null"].push(s`<li
          class="combo-option ${this.activeIndex===o?"option-current":""}"
          id="${this.htmlId}-${o}"
          aria-selected="${this.selectedOptions.indexOf(e)>-1}"
          role="option"
          data-option="${e}"
          @click="${this._handleOptionClick}"
          @mousedown="${this._handleOptionMousedown}"
        >
          ${this.options[e].label}
        </li>`)}),s`<div class="combo-container">
      ${this.hint?this.hintTemplate():d}
      <input
        aria-controls="${this.htmlId}-listbox"
        aria-activedescendant="${this.htmlId}-${this.activeIndex}"
        aria-autocomplete="list"
        aria-expanded="${this.open}"
        aria-haspopup="listbox"
        aria-label="${this.label}"
        aria-describedby="${this.htmlId}-hint"
        class="combo-input${this.open&&this._selectionLessThanMax?" combo-open":""}"
        data-multiple="${this.multiple}"
        ?disabled="${this.disabled||!this._selectionLessThanMax}"
        role="combobox"
        type="text"
        value="${this.value}"
        @input="${this._handleInput}"
        @focus="${this._handleInputFocus}"
        @blur="${this._handleInputBlur}"
        @keydown="${this._handleInputKeydown}"
      />
      <ul
        class="combo-menu ${this.open&&this._selectionLessThanMax?"":"hidden"}"
        role="listbox"
        aria-label="${this.label}"
        aria-multiselectable="true"
        id="${this.htmlId}-listbox"
      >
        ${h(this.groups,(e,o)=>s`${e?s`<ul
                  class="combo-group"
                  role="group"
                  aria-labelledby="group-${o}"
                >
                  <li
                    class="combo-group-label"
                    role="presentation"
                    id="group-${o}"
                  >
                    ${e}
                  </li>
                  ${t[e]}
                </ul>`:s`${t.null}`}`)}
      </ul>
    </div>`}render(){return s`
      <div class="pressbooks-multiselect">
        <slot></slot>
        ${this.htmlId!==""&&this.label!==""&&this.multiple?this.selectionsTemplate():d}
        ${this.htmlId!==""&&this.label!==""?this.comboBoxTemplate():d}
        <slot name="after"></slot>
      </div>
    `}connectedCallback(){super.connectedCallback(),window.addEventListener("click",this._handleWindowClick.bind(this)),window.addEventListener("focus",this._handleWindowFocus.bind(this))}disconnectedCallback(){window.removeEventListener("click",this._handleWindowClick.bind(this)),window.removeEventListener("focus",this._handleWindowFocus.bind(this)),super.disconnectedCallback()}firstUpdated(){this._select&&(this._select.hidden=!0,this.multiple=this._select.hasAttribute("multiple"),this.htmlId=this._select.id,this._select.disabled&&(this.disabled=this._select.disabled),this.label=this._label.innerText,this.hint=this._hint?this._hint.innerText:"",this.options=Object.fromEntries(Array.from(this._select.querySelectorAll("option")).map(t=>[t.value,{label:t.textContent,group:t.parentNode.tagName==="OPTGROUP"?t.parentNode.getAttribute("label"):null}])),this.selectedOptions=Array.from(this._select.querySelectorAll("option[selected]")).map(t=>t.value),this.filteredOptions=this.options,this.multiple||(this.value=this._select.querySelector("option[selected]")?.textContent||""),this.groups=[...new Set(Object.values(this.filteredOptions).map(t=>t.group))])}updated(){this.callFocus===!0&&(this._input.focus(),this.callFocus=!1)}_handleWindowClick(t){!this.shadowRoot.contains(t.target)&&!this.contains(t.target)&&(this.open=!1,this.update())}_handleWindowFocus(t){this.open=!1,this.update()}addOption(t){this._select.querySelector(`option[value="${t}"]`).setAttribute("selected",!0),this.multiple?this.selectedOptions.push(t):(this.selectedOptions=[t],this._input.blur(),this._input.value=this.options[t].label,this.open=!1,this.update())}removeOption(t){this._select.querySelector(`option[value="${t}"]`).removeAttribute("selected"),this.multiple?this.selectedOptions.splice(this.selectedOptions.indexOf(t),1):(this.selectedOptions=[],this._input.blur(),this._input.value="",this.open=!1,this.update())}updateMenuState(t,e=!0){this.open=t,this.callFocus=e}getUpdatedIndex(t,e,o){switch(o){case this.MenuActions.First:return 0;case this.MenuActions.Last:return e;case this.MenuActions.Previous:return Math.max(0,t-1);case this.MenuActions.Next:return Math.min(e,t+1);default:return t}}updateIndex(t){this.activeIndex=t,this.requestUpdate();const e=this.shadowRoot.querySelector(".combo-menu"),o=this.shadowRoot.querySelector(".option-current");o&&(e.scrollTop=o.offsetTop-e.offsetTop)}_handleRemove(t){const{option:e}=t.target.closest("button").dataset;this.removeOption(e),this.updateMenuState(!1),this.requestUpdate()}_handleInputFocus(){this.updateMenuState(!0)}_handleInputBlur(){if(this.ignoreBlur){this.ignoreBlur=!1;return}this.updateMenuState(!1,!1)}_handleInputKeydown(t){const e=Object.keys(this.filteredOptions).length-1,o=this.getActionFromKey(t,this.open);switch(o){case this.MenuActions.Next:case this.MenuActions.Last:case this.MenuActions.First:case this.MenuActions.Previous:return t.preventDefault(),this.updateIndex(this.getUpdatedIndex(this.activeIndex,e,o));case this.MenuActions.CloseSelect:return t.preventDefault(),this.updateOption(this.activeIndex);case this.MenuActions.Close:return t.preventDefault(),this.updateMenuState(!1);case this.MenuActions.Open:return this.updateMenuState(!0);default:return!1}}_handleInput(t){this.open||(this.open=!0);const e=t.target.value.toLowerCase().trim();this.filteredOptions={};for(const[o,i]of Object.entries(this.options))i.label.toLowerCase().includes(e)&&(this.filteredOptions[o]=i);this.groups=[...new Set(Object.values(this.filteredOptions).map(o=>o.group))]}_handleOptionClick(t){const{option:e}=t.target.closest(".combo-option").dataset;this.selectedOptions.indexOf(e)>-1?this.removeOption(e):this.addOption(e),this.requestUpdate()}_handleOptionMousedown(){this.ignoreBlur=!0,this.callFocus=!0}getActionFromKey(t,e){const{key:o,altKey:i,ctrlKey:n,metaKey:r}=t;if(!e&&["ArrowDown","ArrowUp","Enter"," ","Home","End"].includes(o))return this.MenuActions.Open;if(o===this.Keys.Backspace||o===this.Keys.Clear||o.length===1&&o!==" "&&!i&&!n&&!r)return this.MenuActions.Type;if(e){if(o===this.Keys.Down&&!i||o===this.Keys.Right)return this.MenuActions.Next;if(o===this.Keys.Up&&i)return this.MenuActions.CloseSelect;if(o===this.Keys.Up||o===this.Keys.Left)return this.MenuActions.Previous;if(o===this.Keys.Home)return this.MenuActions.First;if(o===this.Keys.End)return this.MenuActions.Last;if(o===this.Keys.PageUp)return this.MenuActions.PageUp;if(o===this.Keys.PageDown)return this.MenuActions.PageDown;if(o===this.Keys.Escape)return this.MenuActions.Close;if(o===this.Keys.Enter)return this.MenuActions.CloseSelect;if(o===this.Keys.Space)return this.MenuActions.Space}return!1}updateOption(t){const e=Object.keys(this.filteredOptions)[t];e&&(this.selectedOptions.indexOf(e)>-1?this.removeOption(e):(this.addOption(e),this.filteredOptions=this.options,this.activeIndex=Object.keys(this.filteredOptions).indexOf(e)),this.requestUpdate())}}window.customElements.get("pressbooks-select")||window.customElements.define("pressbooks-select",b);
//# sourceMappingURL=pressbooks-select-i5s1_BZk.js.map
