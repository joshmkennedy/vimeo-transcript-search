import Vimeo from "@vimeo/player";

import type { VimeoPluginInterface } from "./types";
import { mountReactApp } from "./app/app";

export default class AimClipPlayer implements VimeoPluginInterface {
  private loaded = false;
  private vimeoId: string | undefined;
  private player: Vimeo | undefined;

  public startTime: number | undefined;
  public endTime: number | undefined;

  constructor() {
  }
  shouldUse() {
    const vimeoId = this.getVimeoId();
    if (!vimeoId) {
      console.log("no vimeo clip param");
      return false;
    }
    const iframe = document.querySelector<HTMLIFrameElement>(`iframe[src*='${vimeoId}']`);
    if (!iframe) {
      console.log("no iframe found");
      return false;
    }
    return true; // its safe to now assume we can do all we need to and will assert that things that can be null are now not able to be undefined or null
  }

  init() {
    console.log("Starting the skip to clip plugin");
    this.loadPlayer(this.vimeoId!);
  }

  loadPlayer(id: string) {
    if (!this.player) {
      const video = document.querySelector<HTMLIFrameElement>(`iframe[src*='${id}']`)!;
      this.player = new Vimeo(video)
      console.log("loading player", this.player);
      this.player!.on("loaded", () => {
        this.loaded = true;
        this.startPlugin()
      });
    } else {
      console.log("player already loaded");
      return this.player;
    }
  }

  async startPlugin() {
    if (!this.loaded) {
      throw new Error("Vimeo Plugin has not been properly loaded, need to call loadPlayer before startPlugin");
    }
    await this.setTimes();
    this.mountApp()
  }

  async setTimes() {
    const params = new URLSearchParams(window.location.search);
    const startParamKey = params.has("start") ? "start" : "ts";
    const endParamKey = params.has("end") ? "end" : "end-ts";
    if (!params.has(startParamKey) && !params.has(endParamKey)) {
      console.log("no start or end param");
      return;
    }
    const start = parseInt(params.get(startParamKey) ?? "0");
    const end = parseInt(params.get(endParamKey) ?? "0");
    this.startTime = start;
    this.endTime = end;
    let timout = setTimeout(async () => {
      console.log("setting start time again");
      await this.setCurrentTime(start);
    }, 2000)
    await this.setCurrentTime(start);
    clearTimeout(timout);
  }

  private async setCurrentTime(time: number) {
    await this.player!.setCurrentTime(time).catch(console.error);
  }

  mountApp() {
    const app = document.createElement("div");
    app.id = "aim-clip-player-app";
    const iframeEl = document.querySelector<HTMLIFrameElement>(`iframe[src*='${this.vimeoId}']`)!;
    const wrapperEl = iframeEl.closest(".wp-block-embed");
    if (!wrapperEl) throw new Error("Could not find wrapper element");
    wrapperEl.appendChild(app);
    const toastMarkup = {
      heading: `You have completed the currated clip!`,
      message: `Keep watching to go deeper and learn more about this topic.`,
    }

    mountReactApp(app, { player: this.player!, vimeoId: this.vimeoId!, times: { start: this.startTime ?? 0, end: this.endTime ?? -1 }, toastMessage: toastMarkup });
  }

  private getVimeoId() {
    if (this.vimeoId) return this.vimeoId;

    if (window.vtsPublic.aimClip) {
      this.vimeoId = window.vtsPublic.aimClip;
      return this.vimeoId;
    }

    const params = new URLSearchParams(window.location.search);
    if (params.has(this.vimeoClipParamKey(params))) {
      this.vimeoId = params.get(this.vimeoClipParamKey(params))?.toString();
      return this.vimeoId;
    }
  }

  private vimeoClipParamKey(params: URLSearchParams) {
    if (params.has("aim-clip")) {
      return "aim-clip";
    }
    return "skip-to-clip-video";
  }
}
