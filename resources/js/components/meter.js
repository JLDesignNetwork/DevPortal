/**
 * @since 1.2.0
 * @version 1.2.0
 */
export class JLMeter extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  static get observedAttributes() {
    return ['value', 'max', 'theme', 'variant', 'shape', 'stripes', 'animated', 'size'];
  }

  connectedCallback() {
    this.render();
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (oldValue !== newValue) {
      this.render();
    }
  }

  get value() {
    return parseFloat(this.getAttribute('value')) || 0;
  }

  get max() {
    return parseFloat(this.getAttribute('max')) || 100;
  }

  get percentage() {
    return Math.min(100, Math.max(0, (this.value / this.max) * 100));
  }

  render() {
    const theme = this.getAttribute('theme') || 'primary';
    const variant = this.getAttribute('variant') || 'flat';
    const shape = this.getAttribute('shape') || 'square';
    const stripesVal = this.getAttribute('stripes') || 'none';
    const isFill = stripesVal === 'true' || stripesVal === 'fill';
    const isBorder = stripesVal === 'border';
    const animated = this.getAttribute('animated') === 'true';

    // We apply these attributes to the host so the global SASS rules target it
    if (this.getAttribute('theme') !== theme) this.setAttribute('theme', theme);
    if (this.getAttribute('variant') !== variant) this.setAttribute('variant', variant);
    if (this.getAttribute('shape') !== shape) this.setAttribute('shape', shape);

    // Shape radius mapping
    let computedRadius = '0px';
    if (shape === 'rounded') computedRadius = 'var(--surface-radius, 8px)';
    else if (shape === 'pill') computedRadius = '9999px';

    this.shadowRoot.innerHTML = `
      <style>
        :host {
          display: block;
          position: relative;
          width: var(--meter-width, 100%);
          --meter-height: 30px;
          --meter-border-width: var(--border-width, 1px);
          --computed-radius: ${computedRadius};
        }

        :host([size="sm"]) { --meter-height: 15px; }
        :host([size="lg"]) { --meter-height: 45px; }

        .meter-outer {
          position: relative;
          width: 100%;
          height: var(--meter-height);
          background-color: ${isBorder ? 'var(--border-color, #000)' : 'var(--surface-base, #555)'};
          border: ${isBorder ? 'none' : 'var(--meter-border-width) solid var(--border-color, #000)'};
          border-radius: var(--computed-radius);
          box-shadow: var(--surface-shadow, none);
          overflow: hidden;
          z-index: 1;
        }

        .border-stripe-layer {
          position: absolute;
          top: 0; left: 0; bottom: 0; right: 0;
          z-index: 1;
          pointer-events: none;
          background-size: 50px 50px;
          background-image: linear-gradient(
            135deg,
            var(--overlay-stripe-color) 25%,
            transparent 25%,
            transparent 50%,
            var(--overlay-stripe-color) 50%,
            var(--overlay-stripe-color) 75%,
            transparent 75%,
            transparent
          );
        }

        .meter-inner {
          position: absolute;
          top: ${isBorder ? 'var(--meter-border-width)' : '0'};
          left: ${isBorder ? 'var(--meter-border-width)' : '0'};
          right: ${isBorder ? 'var(--meter-border-width)' : '0'};
          bottom: ${isBorder ? 'var(--meter-border-width)' : '0'};
          background-color: var(--surface-base, #555);
          border-radius: ${isBorder ? 'calc(var(--computed-radius) - var(--meter-border-width))' : '0'};
          overflow: hidden;
          z-index: 2;
        }

        .fill-layer {
          position: absolute;
          top: 0; left: 0; bottom: 0;
          width: ${this.percentage}%;
          background: var(--surface-fill, #3498db);
          transition: width 0.3s ease-out;
          z-index: 3;
        }

        .stripe-layer {
          position: absolute;
          top: 0; left: 0; bottom: 0;
          width: ${this.percentage}%;
          transition: width 0.3s ease-out;
          z-index: 4;
          pointer-events: none;
        }

        .stripe-layer.stripes {
          background-size: 50px 50px;
          background-image: linear-gradient(
            135deg,
            var(--overlay-stripe-color) 25%,
            transparent 25%,
            transparent 50%,
            var(--overlay-stripe-color) 50%,
            var(--overlay-stripe-color) 75%,
            transparent 75%,
            transparent
          );
        }

        .animated {
          animation: candystripe var(--transition-speed, 2s) linear infinite;
        }

        /* Base Glossy Overlay (used by 3d variant) */
        .convex-overlay {
          position: absolute;
          top: 0; left: 0; bottom: 0; right: 0;
          z-index: 5;
          background: linear-gradient(
            to bottom,
            rgba(255, 255, 255, 0.4) 0%,
            rgba(255, 255, 255, 0.1) 40%,
            rgba(0, 0, 0, 0.1) 60%,
            rgba(0, 0, 0, 0.2) 100%
          );
          opacity: var(--overlay-convex-opacity, 0);
          pointer-events: none;
        }

        .text-layer {
          position: absolute;
          top: 0; left: 0; bottom: 0; right: 0;
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--surface-text, #fff);
          font-family: var(--surface-font-family, monospace);
          font-size: var(--surface-text-size, 1rem);
          font-weight: bold;
          z-index: 6;
          text-shadow: var(--surface-text-shadow, 1px 1px 2px rgba(0,0,0,0.5));
        }

        @keyframes candystripe {
          0% { background-position: 0 0; }
          100% { background-position: 50px 50px; }
        }

        /* ========================
           VARIANTS (STYLES)
           ======================== */

        /* 3D Glossy */
        :host([variant="3d"]) .convex-overlay {
          opacity: 1; /* Override the default opacity to force glossy */
        }

        /* Glassmorphic */
        :host([variant="glassmorphic"]) .meter-outer {
          background-color: rgba(255, 255, 255, 0.1);
          backdrop-filter: blur(10px);
          -webkit-backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
          box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        :host([variant="glassmorphic"]) .meter-inner {
          background-color: transparent;
        }
        :host([variant="glassmorphic"]) .fill-layer {
          opacity: 0.6;
          backdrop-filter: blur(5px);
        }

        /* Neumorphic (Soft) */
        :host([variant="neumorphic"]) .meter-outer {
          border: none;
          box-shadow: 
            inset 4px 4px 8px rgba(0,0,0,0.4), 
            inset -4px -4px 8px rgba(255,255,255,0.1) !important;
          background-color: var(--surface-base);
        }
        :host([variant="neumorphic"]) .fill-layer {
          box-shadow: 2px 0px 5px rgba(0,0,0,0.5);
        }

        /* Cartoon */
        :host([variant="cartoon"]) .meter-outer {
          border: max(3px, calc(var(--meter-border-width) * 2)) solid #000 !important;
          box-shadow: 4px 4px 0px #000 !important;
        }

        /* Cyberpunk */
        :host([variant="cyberpunk"]) .meter-outer {
          transform: skewX(-15deg);
          border: 2px solid var(--surface-fill) !important;
          box-shadow: 0 0 10px var(--surface-fill), 0 0 20px var(--surface-fill) !important;
          background-color: #0f0f1a;
        }
        :host([variant="cyberpunk"]) .fill-layer {
          box-shadow: 0 0 15px var(--surface-fill);
        }
        :host([variant="cyberpunk"]) .text-layer {
          transform: skewX(15deg); /* un-skew text so it's readable */
          text-shadow: 0 0 5px var(--surface-fill), 0 0 10px var(--surface-text);
          letter-spacing: 2px;
        }

        /* Professional */
        :host([variant="professional"]) .meter-outer {
          border: 1px solid rgba(255,255,255,0.1);
          box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);
        }
        :host([variant="professional"]) .fill-layer {
          background: linear-gradient(180deg, var(--surface-fill) 0%, rgba(0,0,0,0.2) 100%);
        }
      </style>

      <div class="meter-outer">
        ${isBorder ? `<div class="border-stripe-layer ${animated ? 'animated' : ''}"></div>` : ''}
        <div class="meter-inner">
          <div class="fill-layer"></div>
          ${isFill ? `<div class="stripe-layer stripes ${animated ? 'animated' : ''}"></div>` : ''}
          <div class="convex-overlay"></div>
          <div class="text-layer"><slot></slot></div>
        </div>
      </div>
    `;
  }
}
