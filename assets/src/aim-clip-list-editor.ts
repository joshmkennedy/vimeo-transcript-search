import "./aim-clip-list-editor/app"
import { renderApp } from "./aim-clip-list-editor/app"

window.addEventListener("DOMContentLoaded", () => {
  console.log(window.vtsACLEditor);
  renderApp("aim-clip-list-editor-app", {
    postId: window.vtsACLEditor.postId ?? "new",
    items: window.vtsACLEditor.items ?? [],
    previewList: window.vtsACLEditor.previewList ?? [],
    apiUrl: window.vtsACLEditor.apiUrl,
    nonce: window.vtsACLEditor.nonce,
    post: window.vtsACLEditor.post,
    resources: window.vtsACLEditor.resources ?? [],
  });
});
