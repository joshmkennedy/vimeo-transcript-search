import "./flash-messages.css"
import Cookie from "js-cookie";

const PREFIX = "vts-flash--";

export function initFlashMessages() {
  window.addEventListener("DOMContentLoaded", init)
}

function init() {
  const messages = parseFlashMessages();

  if (messages && messages.length && Array.isArray(messages)) {
    appendMessageRoot();
    messages.forEach(createAndClearMessage)
  }
}

function parseFlashMessages() {
  return document.cookie.split(";").map(c => c.trim()).filter(c => c.startsWith(PREFIX)).map(c => c.replace(PREFIX, "").split("=")).map(c => ({
    id: c[0],
    message: c[1],
  }))
}

function createAndClearMessage({ message, id }: { message: string | undefined, id: string | undefined }) {
  if (!message || !id) {
    return;
  }

  const el = document.createElement("div");
  el.classList.add("vts-flash-message");
  el.setAttribute("data-vts-flash-message-id", id);
  el.innerHTML = `
  <button class="dismiss">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
  </button>
  <p>${decodeURIComponent(message)}</p>
  `
  const messageRoot = document.querySelector<HTMLElement>("#vts-flash-messages");
  if (messageRoot) {
    messageRoot.appendChild(el);
    Cookie.remove(fullId(id));
  }
}

function appendMessageRoot() {
  let root = document.querySelector<HTMLElement>("#vts-flash-messages");
  if (!root) {
    root = document.createElement("div");
    root.id = "vts-flash-messages";
    addMessageListeners(root);
  }
  document.body.appendChild(root);
}

function addMessageListeners(root: HTMLElement) {
  root.addEventListener("click", dissmissMessage)
  root.addEventListener("animationend", removeMessageElement)
}

function dissmissMessage(e: Event) {
  const target = e.target as HTMLElement;
  if (target.classList.contains("dismiss")) {
    const message = target.closest(".vts-flash-message");
    if (message) {
      message.classList.add("vts-flash-message-leave");
    }
  }
}

function removeMessageElement(e: Event) {
  const target = e.target as HTMLElement;
  if (target.classList.contains("vts-flash-message-leave")) {
    const message = target.closest(".vts-flash-message.vts-flash-message-leave");
    if (message) {
      message.remove();
    }
  }
}

function fullId(id: string) {
  return `${PREFIX}${id}`;
}

