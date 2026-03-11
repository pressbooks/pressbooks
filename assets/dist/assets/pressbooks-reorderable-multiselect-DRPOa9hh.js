import{E as r,i as c,a as d,x as i}from"./lit-element-DwTj7qCT.js";const a=n=>n??r;class p extends c{static get styles(){return d`
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
          "Segoe UI",
          Roboto,
          Oxygen-Sans,
          Ubuntu,
          Cantarell,
          "Helvetica Neue",
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

      [role="listbox"] {
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

      [role="listbox"]:focus-visible {
        border-color: #d4002d;
        outline-color: #d4002d;
      }

      [role="option"] {
        background: var(--pb-listbox-option-background, #fff);
        cursor: default;
        font-family: var(
          --pb-listbox-option-font-family,
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
        padding: var(--pb-listbox-option-padding, 0.25rem 0.5rem);
      }

      [role="option"][aria-selected="true"] {
        background: var(--pb-listbox-option-background-selected, #d4002d);
        color: var(--pb-listbox-option-color-selected, #fff);
      }
    `}static get properties(){return{messages:{type:Object},name:{type:String},label:{type:String},hint:{type:String},listBoxHasFocus:{type:Boolean},activeDescendant:{type:String},options:{type:Object},selectedOptions:{type:Object},availableOptions:{type:Object},selectedAvailableOption:{type:String},actionMessage:{type:String},ListboxActions:{type:Object},Keys:{type:Object}}}constructor(){super(),this.messages={},this.label="",this.hint="",this.listBoxHasFocus=!1,this.activeDescendant=null,this.options={},this.selectedOptions={},this.availableOptions={},this.selectedAvailableOption="",this.actionMessage=null,this.ListboxActions={MoveUp:"MoveUp",MoveDown:"MoveDown",MoveSelectionUp:"MoveSelectionUp",MoveSelectionDown:"MoveSelectionDown",Remove:"Remove"},this.Keys={Backspace:"Backspace",Clear:"Clear",Down:"ArrowDown",End:"End",Enter:"Enter",Escape:"Escape",Home:"Home",Left:"ArrowLeft",PageDown:"PageDown",PageUp:"PageUp",Right:"ArrowRight",Space:" ",Tab:"Tab",Up:"ArrowUp"}}labelTemplate(){return i`<label id="${this.name}-label">${this.label}</label>`}hintTemplate(){return i`<p id="${this.name}-hint" class="hint">${this.hint}</p>`}selectedOptionsTemplate(){return i` ${Object.keys(this.selectedOptions).map(e=>i`<input type="hidden" name="${this.name}[]" .value=${e} />`)}
      <ul
        class="selected-options-list"
        role="listbox"
        aria-labelledby="${this.name}-label"
        aria-describedby="${this.name}-hint"
        aria-activedescendant=${a(this.activeDescendant)}
        tabindex="0"
        @keydown=${this._handleKeydown}
        @focus=${this._handleFocus}
        @blur=${this._handleBlur}
      >
        ${Object.entries(this.selectedOptions).map(e=>i`<li
              role="option"
              id=${e[0]}
              aria-selected=${this.activeDescendant===e[0]}
              @click=${this._handleClick}
              @keydown=${this._handleKeydown}
            >
              ${e[1]}
            </li>`)}
      </ul>`}availableOptionsTemplate(){return i` <select
        id="available-options"
        @change="${this._handleSelectChange}"
        aria-label="${this.messages["$1 available options"]??`${this.label} available options`}"
        ?disabled="${Object.keys(this.availableOptions).length===0}"
      >
        ${Object.entries(this.availableOptions).map(e=>Object.keys(this.selectedOptions).includes(e)?null:i`<option value="${e[0]}">${e[1]}</option>`)}
      </select>
      <button
        type="button"
        class="add"
        aria-label=${this.messages["Add $1"]?this.messages["Add $1"].replace("$1",this.options[this.selectedAvailableOption]):`Add ${this.options[this.selectedAvailableOption]}`}
        @click=${this._handleClick}
        ?disabled="${Object.keys(this.availableOptions).length===0}"
      >
        ${this.messages.Add??"Add"}
      </button>`}selectedOptionsControlsTemplate(){return i`
      <div class="selected-options-controls">
        <button
          type="button"
          class="move-up"
          tabindex=${this.activeDescendant?0:-1}
          aria-keyshortcuts="Alt+ArrowUp"
          aria-label=${this.messages["Move $1 up"]?this.messages["Move $1 up"].replace("$1",this.options[this.activeDescendant]):`Move ${this.options[this.activeDescendant]} up`}
          @click=${this._handleClick}
          ?disabled="${!this.activeDescendant||Object.keys(this.selectedOptions).indexOf(this.activeDescendant)===0}"
        >
          ${this.messages["Move Up"]??"Move Up"}
        </button>
        <button
          type="button"
          class="move-down"
          tabindex=${this.activeDescendant?0:-1}
          aria-keyshortcuts="Alt+ArrowDown"
          aria-label=${this.messages["Move $1 down"]?this.messages["Move $1 down"].replace("$1",this.options[this.activeDescendant]):`Move ${this.options[this.activeDescendant]} down`}
          @click=${this._handleClick}
          ?disabled="${!this.activeDescendant||Object.keys(this.selectedOptions).indexOf(this.activeDescendant)===Object.keys(this.selectedOptions).length-1}"
        >
          ${this.messages["Move Down"]??"Move Down"}
        </button>
        <button
          type="button"
          class="remove"
          tabindex=${this.activeDescendant?0:-1}
          aria-label=${this.messages["Remove $1"]?this.messages["Remove $1"].replace("$1",this.options[this.activeDescendant]):`Remove ${this.options[this.activeDescendant]}`}
          @click=${this._handleClick}
          ?disabled="${!this.activeDescendant}"
        >
          ${this.messages.Remove??"Remove"}
        </button>
      </div>
    `}liveRegionTemplate(){return i`
      <div class="visually-hidden" aria-live="polite">
        ${a(this.actionMessage)}
      </div>
    `}render(){return i` ${this.labelTemplate()}
      <div class="selected-options">
        ${this.selectedOptionsTemplate()}
        ${this.selectedOptionsControlsTemplate()}
      </div>
      ${this.availableOptionsTemplate()} ${this.hintTemplate()}
      ${this.liveRegionTemplate()}`}connectedCallback(){super.connectedCallback(),this.dataset.messages&&(this.messages=JSON.parse(this.dataset.messages)),this.label=this.querySelector("label").innerText,this.hint=this.querySelector("hint").innerText;const e=this.querySelectorAll("option"),t=this.querySelector('input[type="hidden"]');this.name=t.getAttribute("name"),Array.prototype.forEach.call(e,s=>{this.options[s.getAttribute("value")]=s.innerText}),t.value&&Array.prototype.forEach.call(t.value.split(","),s=>{this.selectedOptions[s]=this.options[s]}),t.remove(),this.querySelector("label").remove(),this.querySelector("hint").remove(),this.querySelector("select").remove(),this.updateAvailableOptions(),this.updateSelectedOptions()}disconnectedCallback(){super.disconnectedCallback()}_handleSelectChange(e){this.selectedAvailableOption=e.target.value}_handleFocus(){this.activeDescendant=Object.keys(this.selectedOptions)[0]}_handleKeydown(e){switch(this.getActionFromKey(e)){case this.ListboxActions.MoveUp:return this.updateIndex(-1);case this.ListboxActions.MoveSelectionUp:return this.updateSelectedIndex(-1);case this.ListboxActions.MoveDown:return this.updateIndex(1);case this.ListboxActions.MoveSelectionDown:return this.updateSelectedIndex(1);default:return!1}}_handleClick(e){e.target.closest('[role="option"]')?this.activeDescendant=e.target.closest('[role="option"]').id:e.target.closest(".move-up")?this.updateIndex(-1):e.target.closest(".move-down")?this.updateIndex(1):e.target.closest(".remove")?(delete this.selectedOptions[this.activeDescendant],this.actionMessage=this.messages["Removed $1 from selection"]?this.messages["Removed $1 from selection"].replace("$1",this.options[this.activeDescendant]):`Removed ${this.options[this.activeDescendant]} from selection`,this.updateAvailableOptions(),this.activeDescendant=null):e.target.closest(".add")&&(this.selectedOptions[this.selectedAvailableOption]=this.options[this.selectedAvailableOption],this.actionMessage=this.messages["Added $1 to selection"]?this.messages["Added $1 to selection"].replace("$1",this.options[this.selectedAvailableOption]):`Added ${this.options[this.selectedAvailableOption]} to selection`,this.updateAvailableOptions()),this.updateSelectedOptions()}getActionFromKey(e){const{key:t,altKey:s}=e;return t===this.Keys.Down&&s?this.ListboxActions.MoveDown:t===this.Keys.Down?this.ListboxActions.MoveSelectionDown:t===this.Keys.Up&&s?this.ListboxActions.MoveUp:t===this.Keys.Up?this.ListboxActions.MoveSelectionUp:!1}updateSelectedOptions(){const e=this.querySelector("input");if(e)e.setAttribute("value",Object.keys(this.selectedOptions).join(","));else{const t=document.createElement("input");t.setAttribute("type","hidden"),t.setAttribute("name",`${this.name}[]`),t.setAttribute("value",Object.keys(this.selectedOptions).join(",")),this.appendChild(t)}}updateAvailableOptions(){this.availableOptions=Object.keys(this.options).reduce((e,t)=>(Object.prototype.hasOwnProperty.call(this.selectedOptions,t)||(e[t]=this.options[t]),e),{}),this.selectedAvailableOption=Object.keys(this.availableOptions)[0]}updateIndex(e){const t=Object.entries(this.selectedOptions),s=Object.keys(this.selectedOptions).indexOf(this.activeDescendant),o=s+e;if(o>=0&&o<t.length){const l=t.splice(s,1)[0];t.splice(o,0,l)}this.selectedOptions=Object.fromEntries(t),this.actionMessage=this.messages["Moved to position $1"]?this.messages["Moved to position $1"].replace("$1",o+1):`Moved to position ${o+1}`}updateSelectedIndex(e){const t=Object.entries(this.selectedOptions),o=Object.keys(this.selectedOptions).indexOf(this.activeDescendant)+e;o>=0&&o<t.length&&(this.activeDescendant=Object.keys(this.selectedOptions)[o])}setFocus(e){this.listBoxHasFocus=e}}window.customElements.define("pressbooks-reorderable-multiselect",p);
//# sourceMappingURL=pressbooks-reorderable-multiselect-DRPOa9hh.js.map
