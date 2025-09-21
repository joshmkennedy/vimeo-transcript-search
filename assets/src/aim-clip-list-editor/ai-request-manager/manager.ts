import { atom, createStore } from "jotai";
import type { Store } from "jotai/vanilla/store";
import { API, store } from "../store";

type VideoSummaryRequest = {
  vimeoId: string;
  start: number;
  end: number;
  clipId: string;
  bypassCache?: boolean;
};

type EmailSummaryRequest = {
  summaries: string[];
  weekIndex: string;
  postId: number;
  bypassCache?: boolean;
};

type AiRequest = VideoSummaryRequest | EmailSummaryRequest;

type VideoSummaryResult = {
  clipId: string;
  summary: string;
};

type EmailSummaryResult = {
  weekIndex: string;
  summary: string;
}

type AiRequestError = {
  code: number,
  message: string,
}

type AiResponse = VideoSummaryResult | EmailSummaryResult | AiRequestError;

export type AiEmail = { weekIndex: string, clipIds: string[], summary: string | undefined };
export type AiVideo = { vimeoId: string, start: number, end: number, clipId: string, summary: string | undefined };

type MessageType = 'error' | 'success' | 'info' | 'progress';
export type Message = {
  type: MessageType;
  message: string | { done: number, total: number } | { data: { emails: AiEmail[], videos: AiVideo[] }, content: string };
}

const SUMMARIZE_VIDEO = 'summarize-video';
const SUMMARIZE_EMAIL = 'summarize-email';
type SUMMARIZE_VIDEO = typeof SUMMARIZE_VIDEO;
type SUMMARIZE_EMAIL = typeof SUMMARIZE_EMAIL;

export const MessagesStore = atom<Message[]>([]);

export class AiRequestManager {
  private emails: AiEmail[] = [];
  private videos: AiVideo[] = [];
  private total: number = 0;
  private done: number = 0;
  private isRunning: boolean = false;

  public store: Store;
  constructor(
    private route: string = "",
    private nonce: string = "",
  ) {
    this.store = store
  }

  public setAPI(route: string, nonce: string) {
    this.route = route;
    this.nonce = nonce;
  }

  public setWork({ videos, emails }: { videos: AiVideo[], emails?: AiEmail[] }) {
    if (this.isRunning) {
      throw new Error("AiRequestManager is already running");
    }
    this.videos = videos;
    const videoClipIds = videos.map(v => v.clipId);
    this.total += this.videos.length;
    if (emails) {
      this.emails = emails.filter(email => email.clipIds.every(clipId => videoClipIds.includes(clipId)));
      if (emails.length !== this.emails.length) {
        this.notify({ type: "info", message: "Sumbitted emails were given clips ids that did not exist in the submitted videos" + ` submitted: ${emails.length}, valid: ${this.emails.length}` });
      }
      this.total += this.emails.length;
    }
  }

  public async start(bypassCache = false) {
    if (this.isRunning) {
      console.error("AiRequestManager is already running");
      return;
    }
    if (this.videos.length === 0) {
      console.error("No videos to submit");
      return;
    }

    this.notify({ type: 'info', message: "Starting Summary Generation" });
    this.notify({ type: 'progress', message: { done: this.done, total: this.total } });
    this.isRunning = true;

    for (let i = 0; i < this.videos.length; i++) {
      const video = this.videos[i]!;
      const results = await this.send(SUMMARIZE_VIDEO, {...video, bypassCache});

      if (this.isResponseError(results)) {
        this.notify({ type: 'error', message: results.message });
        continue;
      }
      video.summary = results.summary;
      this.videos[i] = video;
      this.done++;
      this.notify({ type: 'progress', message: { done: this.done, total: this.total } });
    }
    if (this.emails.length <= 0) {
      this.notify({ type: 'success', message: { content: "Finished Summary Generation", data: { videos: this.videos, emails: this.emails } } });
    }

    const clipIdsWithSummaries = this.videos.reduce((collection, video) => {
      if (video.summary) {
        collection[video.clipId] = video.summary;
      }
      return collection;
    }, {} as { [clipId: string]: string });

    for (let i = 0; i < this.emails.length; i++) {
      const email = this.emails[i]!;
      const summaries = email.clipIds.map(clipId => clipIdsWithSummaries[clipId]).filter(Boolean) as string[];
      if (summaries.length != email.clipIds.length) {
        this.notify({ type: 'info', message: "Some clips were not found in the summaries" });
      }
      let results;
      try {
        results = await this.send(SUMMARIZE_EMAIL, {
          bypassCache,
          summaries,
          weekIndex: email.weekIndex,
          postId: parseInt(window.vtsACLEditor.postId.toString()),
        });
      } catch {
        results = {
          code: 500,
          message: "Failed to send request",
        }
      }

      if (this.isResponseError(results)) {
        this.notify({ type: 'error', message: results.message });
        continue;
      }
      email.summary = results.summary;
      this.emails[i] = email;
      this.done++;
      this.notify({ type: 'progress', message: { done: this.done, total: this.total } });
    }

    this.notify({ type: 'success', message: { content: "Finished Summary Generation", data: { videos: this.videos, emails: this.emails } } });

    // reset
    const returnData = { videos: this.videos, emails: this.emails };
    this.isRunning = false;
    this.total = 0;
    this.done = 0;
    this.videos = [];
    this.emails = [];
    // give react a chance to read any last bit fo messages
    setTimeout(() => {
      this.clearAllMessages();
    }, 1000);
    return returnData;
  }

  async send(action: SUMMARIZE_VIDEO | SUMMARIZE_EMAIL, data: AiRequest): Promise<AiResponse> {
    const response = await fetch(`${this.route}/${action}`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': this.nonce,
      },
      body: JSON.stringify(data),
    }).catch(e => {
      console.error("error sending request", e);
      return e
    })
    return await response.json();
  }

  private notify(message: Message) {
    this.store.set(MessagesStore, (prevMessages) => [...prevMessages, message]);
  }

  private isResponseError(response: AiResponse): response is AiRequestError {
    const isError = response.hasOwnProperty('code') && response.hasOwnProperty('message');
    if (isError) {
      console.error("got error response", response);
    }
    return isError;
  }

  private clearAllMessages() {
    this.store.set(MessagesStore, []);
  }
}
