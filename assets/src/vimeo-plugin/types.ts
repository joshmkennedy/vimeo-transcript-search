export type { VimeoPluginInterface } from "..";

export type Video = {
  vimeoId: string;
  start: number;
  end: number;
  clipId: string;
  summary: string;
  name: string;
  image_url: string;
	video_type: VideoType;
};


export type VideoType = "lecture" | "secondary-lecture" | "lab";
