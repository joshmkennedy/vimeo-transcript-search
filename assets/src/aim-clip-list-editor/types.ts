export type AimClipListPost = {
  title:string;
}

export type ClipListMetaItem = {
  clip_id: string;
  vimeoId: string;
  start: number;
  end: number;
  summary?: string;
  topics?: string[];
  in_list: boolean;
  video_type?: string;
  week_index?: number;
};

export type Term = {
  term_id: number;
  name: string;
  slug: string;
};

export type AiVimeoResult = Omit<ClipListMetaItem, "level" | "topics" | "position" > & {
  name: string,
  uri: string,
  pictures: {
    base_link: string,
  },
  player_embed_url: string,
};

export type AimClipListResources = {
  link: string,
  label: string,
  week_index: number,
}

