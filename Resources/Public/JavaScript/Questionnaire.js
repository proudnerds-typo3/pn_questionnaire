/**
 * Questionnaire
 *
 * Frontend behaviour for the pn_questionnaire plugin.
 *
 * Responsibilities
 * ─────────────────
 * - Scroll the questionnaire into view on each step load so the visitor
 *   always sees the top of the form after a page transition.
 * - Client-side required-field validation with an accessible error message
 *   (reads the error text from a data attribute set by the Fluid template).
 * - Disable the submit button after a valid submission to prevent double-posting.
 * - Copy the link to a saved result to the clipboard, reporting the outcome in
 *   a status region instead of moving the focus.
 *
 * This class is intentionally lightweight and enhancement-only. All core
 * functionality works without JavaScript through standard HTML form submissions.
 *
 * @module Questionnaire
 * @version 1.0.0
 */
class Questionnaire {
  /** @type {HTMLElement} */
  #container

  /** @type {HTMLFormElement|null} */
  #form

  /** @type {HTMLButtonElement|HTMLInputElement|null} */
  #submitButton

  /** @type {HTMLElement|null} */
  #errorElement

  /** @type {HTMLButtonElement|null} */
  #copyButton

  /** @type {HTMLElement|null} */
  #copyStatus

  /** @type {HTMLAnchorElement|null} */
  #savedResultLink

  /**
   * @param {string} containerSelector CSS selector for the questionnaire wrapper element
   */
  constructor(containerSelector) {
    this.#container = document.querySelector(containerSelector)
    if (!this.#container) return

    this.#form = this.#container.querySelector('.pn-questionnaire__form')
    this.#submitButton = this.#container.querySelector(
      '[data-questionnaire-submit]'
    )
    this.#errorElement =
      this.#form?.querySelector('.pn-questionnaire__error') ?? null
    this.#copyButton = this.#container.querySelector('[data-saved-result-copy]')
    this.#copyStatus = this.#container.querySelector(
      '[data-saved-result-status]'
    )
    this.#savedResultLink = this.#container.querySelector(
      '[data-saved-result-url]'
    )

    this.#scrollIntoView()
    this.#attachEventListeners()
    this.#enableCopyButton()
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Private methods
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Scroll the questionnaire container to the top of the viewport.
   * Only activates when a step is actively shown (data-active-step present),
   * i.e. on question and result views — not on the initial intro load.
   *
   * @returns {void}
   */
  #scrollIntoView() {
    const hasActiveStep =
      this.#container.querySelector('[data-active-step]') !== null
    if (hasActiveStep) {
      this.#container.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }

  /**
   * Attach all event listeners.
   *
   * @returns {void}
   */
  #attachEventListeners() {
    if (!this.#form) return
    this.#form.addEventListener('submit', (event) => this.#handleSubmit(event))
  }

  /**
   * Handle form submission: validate required fields, then lock the submit button.
   *
   * @param {SubmitEvent} event
   * @returns {void}
   */
  #handleSubmit(event) {
    if (!this.#validateRequired()) {
      event.preventDefault()
      return
    }

    // Prevent double-submission on slow connections
    if (this.#submitButton) {
      this.#submitButton.disabled = true
      this.#submitButton.setAttribute('aria-busy', 'true')
    }
  }

  /**
   * Validate that the visitor has provided an answer when the question is required.
   * The required flag is signalled by a hidden `[data-required]` element in the form.
   *
   * @returns {boolean} true when validation passes or the question is not required
   */
  #validateRequired() {
    const requiredFlag = this.#form?.querySelector('[data-required]')
    if (!requiredFlag) return true

    const answerInputs = Array.from(
      this.#form?.querySelectorAll(
        'input[name="tx_pnquestionnaire_questionnaire[answers][]"]:not([type="hidden"])'
      ) ?? []
    )

    const isAnswered = answerInputs.some((input) => {
      if (input.type === 'radio' || input.type === 'checkbox') {
        return input.checked
      }
      return input.value.trim() !== '' && input.value !== ''
    })

    if (!isAnswered) {
      this.#showError()
      answerInputs[0]?.focus()
      return false
    }

    this.#hideError()
    return true
  }

  /**
   * Show the inline validation error message.
   *
   * @returns {void}
   */
  #showError() {
    if (!this.#errorElement) return
    const message = this.#form?.dataset.requiredError ?? ''
    this.#errorElement.textContent = message
    this.#errorElement.removeAttribute('hidden')
    this.#errorElement.setAttribute('role', 'alert')
    this.#answerGroup()?.setAttribute('aria-invalid', 'true')
  }

  /**
   * Hide the inline validation error message.
   *
   * @returns {void}
   */
  #hideError() {
    if (!this.#errorElement) return
    this.#errorElement.setAttribute('hidden', 'hidden')
    this.#errorElement.removeAttribute('role')
    this.#answerGroup()?.removeAttribute('aria-invalid')
  }

  /**
   * The fieldset holding the answers. It carries aria-invalid so assistive technology
   * reports the group as erroneous, not just the announced message.
   *
   * @returns {HTMLElement|null}
   */
  #answerGroup() {
    return this.#form?.querySelector('.pn-questionnaire__answers') ?? null
  }

  /**
   * Reveal the copy button, but only when the browser can actually copy.
   * The template renders it hidden, so a visitor without JavaScript or without
   * the Clipboard API never faces a button that does nothing — they can select
   * the link themselves instead.
   *
   * @returns {void}
   */
  #enableCopyButton() {
    if (!this.#copyButton || !this.#savedResultLink || !navigator.clipboard)
      return

    this.#copyButton.removeAttribute('hidden')
    this.#copyButton.addEventListener('click', () =>
      this.#copySavedResultLink()
    )
  }

  /**
   * Copy the saved result link and report the outcome in the status region.
   * That region announces itself, so the focus stays on the button.
   *
   * @returns {Promise<void>}
   */
  async #copySavedResultLink() {
    if (!this.#copyButton || !this.#copyStatus || !this.#savedResultLink) return

    const { copiedLabel, failedLabel } = this.#copyButton.dataset

    try {
      await navigator.clipboard.writeText(this.#savedResultLink.href)
      this.#copyStatus.textContent = copiedLabel ?? ''
    } catch {
      this.#copyStatus.textContent = failedLabel ?? ''
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  new Questionnaire('[data-questionnaire]')
})
