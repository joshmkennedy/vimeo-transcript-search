import Vimeo from "@vimeo/player";


// THIS IS NOW JUST USED ON LEARNING PATHS TEMPLATES DOESNT WORK ON THE NORMAL VIMEO PLAYER

import type { VimeoPluginInterface } from "./types";
import { mountReactApp, type Video } from "./app/app";

export default class AimClipPlayer implements VimeoPluginInterface {
  private loaded = false;
  private playerApi: PlayerApi | undefined;

  constructor() {
  }
  shouldUse() {
    if (!window?.aimVimeoPluginData?.AimClipPlayerData) {
      return false;
    }
    if (!("videos" in window.aimVimeoPluginData.AimClipPlayerData)) {
      console.error("no videos in window.aimVimeoPluginData.AimClipPlayerData");
      return false;
    }

    return true; // its safe to now assume we can do all we need to and will assert that things that can be null are now not able to be undefined or null
  }

  init() {
    console.log("Starting the skip to clip plugin");
    this.loadPlayer();
  }

  loadPlayer() {
    if (!this.playerApi) {
      const video = document.querySelector<HTMLIFrameElement>(`.aim-clip-player iframe`)!;
      if (!video) {
        throw new Error("No iframe found");
      }
      this.playerApi = new PlayerApi(video);
      this.loaded = true;
      this.startPlugin()
    } else {
      console.log("player already loaded");
      return this.playerApi;
    }
  }

  async startPlugin() {
    if (!this.loaded) {
      throw new Error("Vimeo Plugin has not been properly loaded, need to call loadPlayer before startPlugin");
    }
    console.log("trying to mount app")
    this.mountApp()
  }


  mountApp() {
    const app = document.createElement("div");
    app.id = "aim-clip-player-app";
    const wrapperEl = this.playerApi?.getPlayerEl()?.closest(".wp-block-embed");
    if (!wrapperEl) throw new Error("Could not find wrapper element");
    wrapperEl.appendChild(app);
    console.log("App is now mounting");
    mountReactApp(app, {
      playerApi: this.playerApi!,
      videos: window.aimVimeoPluginData.AimClipPlayerData.videos,
      selectedVideo: window.aimVimeoPluginData.AimClipPlayerData.selectedVideo,
      resources: window.aimVimeoPluginData.AimClipPlayerData.resources,
    });
  }
}


export class PlayerApi {
  private player: Vimeo;
  private currentVideo: Video | undefined;

  constructor(private playerEl: HTMLElement) {
    this.player = new Vimeo(this.playerEl);
    this.player.on("loaded", () => {
      if (this.currentVideo) {
        console.log("player loaded", this);
        setTimeout(() => {
          if (!this.currentVideo) {
            return;
          }
          this.player.setCurrentTime(this.currentVideo.start).then(console.log).catch(console.error);
        }, 500);
      }
    });
    this.player.on("timeupdate", (timeEvent) => {
      if (this.currentVideo) {
        if (this.currentVideo.end <= timeEvent.seconds) {
          this.playerEl.dispatchEvent(new CustomEvent("finishedVideo", { detail: this.currentVideo }));
        }
      }
    });
  }

  getPlayerEl() {
    return this.playerEl;
  }

  setCurrentVideo(video: Video) {
    this.currentVideo = video;
    this.playerEl.setAttribute("src", `https://player.vimeo.com/video/${video.vimeoId}`);
    this.#loadVideo();
  }

  #loadVideo() {
    setTimeout(() => {
      if (!this.currentVideo) {
        return;
      }
      this.player.setCurrentTime(this.currentVideo.start);
    }, 500);
  }
}
