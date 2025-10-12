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

    this.mountApp()
  }


  mountApp() {
    const app = document.createElement("div");
    app.id = "aim-clip-player-app";
    const wrapperEl = this.playerApi?.getPlayerEl()?.closest(".wp-block-embed");
    if (!wrapperEl) throw new Error("Could not find wrapper element");
    wrapperEl.appendChild(app);

    mountReactApp(app, {
      playerApi: this.playerApi!,
      intro: window.aimVimeoPluginData.AimClipPlayerData.intro,
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
  }

  #loadTimeListener() {
    setTimeout(() => {
      this.player.on("timeupdate", (timeEvent) => {
        if (this.currentVideo) {
          if (this.currentVideo.end <= timeEvent.seconds) {
            this.playerEl.dispatchEvent(new CustomEvent("finishedVideo", { detail: this.currentVideo }));
          }
        }
      })
    }, 100);
  }

  getPlayerEl() {
    return this.playerEl;
  }

  async setCurrentVideo(video: Video) {

    const p = this.playerEl.parentElement!;
    await this.player.destroy()
    this.player.off("timeupdate")

    p.prepend(this.playerEl);
    this.player = new Vimeo(this.playerEl);

    this.currentVideo = video;
    await this.player.loadVideo(this.currentVideo.vimeoId);

    await this.setCurrentTime();
    this.#loadTimeListener();
  }

  async setCurrentTime() {
    await this.player.setCurrentTime(this.currentVideo?.start ?? 0);
  }
}
