import { initFlashMessages } from "./flashMessages";
import AimClipPlayer from "./vimeo-plugin/aim-clip-player";

if (!window.aimVimeoPlugins) {
  window.aimVimeoPlugins = [];
}
window.aimVimeoPlugins.push({
  aimClipPlayer: new AimClipPlayer(),
});


initFlashMessages();
