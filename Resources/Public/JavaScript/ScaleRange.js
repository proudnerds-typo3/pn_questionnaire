/**
 * ScaleRange
 *
 * Keeps the <output> element next to a range slider in sync with its current
 * value so the visitor always sees the number they have selected.
 *
 * Loaded only when a scale question is rendered as a range slider
 * (FlexForm setting "Scale question display" = "Range slider").
 * Included via <f:asset.script> in the Scale.html partial.
 *
 * Works without this script — the <output> element is server-rendered with
 * the correct initial value; this file only adds live updating while dragging.
 *
 * @module ScaleRange
 * @version 1.0.0
 */
class ScaleRange {
  /** @type {HTMLElement} */
  #container

  /**
   * @param {HTMLElement} container - The questionnaire root element ([data-questionnaire])
   */
  constructor(container) {
    this.#container = container
    this.#init()
  }

  /**
   * Attach input listeners to every range input found in the container.
   *
   * @returns {void}
   */
  #init() {
    this.#container.querySelectorAll('.pn-questionnaire__range').forEach((input) => {
      // Sync once on load (in case the server-rendered value differs from
      // what the browser normalises to, e.g. value out of min/max bounds)
      this.#sync(input)
      input.addEventListener('input', () => this.#sync(input))
    })
  }

  /**
   * Update the <output> whose `for` attribute matches the input id.
   *
   * @param {HTMLInputElement} input
   * @returns {void}
   */
  #sync(input) {
    const output = this.#container.querySelector(`output[for="${input.id}"]`)
    if (output) {
      output.value = input.value
      output.textContent = input.value
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-questionnaire]').forEach((el) => {
    new ScaleRange(el)
  })
})
