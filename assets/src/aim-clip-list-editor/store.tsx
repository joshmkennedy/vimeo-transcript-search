import { atom } from "jotai";
import type { AimClipListPost, AimClipListResources, ClipListMetaItem } from "./types";

type IDLE_STATE = {
  status: "idle";
};

type LOADING_STATE = {
  status: "loading";
};

type ERROR_STATE = {
  status: "error";
  error: string;
};

type SUCCESS_STATE = {
  status: "success";
};
type AppState = IDLE_STATE | LOADING_STATE | ERROR_STATE | SUCCESS_STATE;

export const AppState = atom<AppState>({ status: "idle" });

export type AppStoreT = {
  post: AimClipListPost;
  postId: number | string;
  items: ClipListMetaItem[];
  resources: AimClipListResources[];
  weeksInfo: Record<number, WeekInfoType>;
	formId: number;
	category: number;
};

export const AppStore = atom<AppStoreT>({
  post: { title: "" },
  postId: 0,
  items: [],
  resources: [],
  weeksInfo: {},
	formId: 19902, // aim-100-days
	category:74, // beginner
});

export const PostData = atom<AimClipListPost, [{ data: AimClipListPost, fromDb?: boolean }], void>(
  (get) => get(AppStore)?.post,
  (get, set, { data, fromDb = false }) => {
    set(AppStore, {
      ...get(AppStore),
      post: data,
    });
    if (!fromDb) {
      set(AppDataDirty, !fromDb);
    }
  }
)

export const PostId = atom<number | string, [(number | string), boolean], void>(
  (get) => get(AppStore)?.postId,
  (get, set, updatedPostId, fromDb = false) => {
    set(AppStore, {
      ...get(AppStore),
      postId: updatedPostId,
    });
    if (!fromDb) {
      set(AppDataDirty, !fromDb);
    }
  }
);

export const Resources = atom<AimClipListResources[], [{ data: AimClipListResources[], fromDb?: boolean | undefined }], void>(
  (get) => get(AppStore)?.resources,
  (get, set, { data, fromDb = false }) => {
    set(AppStore, {
      ...get(AppStore),
      resources: data,
    });
    if (!fromDb) {
      set(AppDataDirty, !fromDb);
    }
  }
);

export const ListItems = atom<ClipListMetaItem[], [{ data: ClipListMetaItem[], fromDb?: boolean | undefined }], void>(
  (get) => get(AppStore)?.items,
  (get, set, { data, fromDb = false }) => {
    console.log("commiting to store", data)
    set(AppStore, {
      ...get(AppStore),
      items: data,
    });
    if (!fromDb) {
      set(AppDataDirty, !fromDb);
    }
  }
);

export const API = atom<{ url: string; nonce: string }>({ url: "", nonce: "" });


export const AppDataDirty = atom<boolean>(false);

export const AppLocation = atom<string>('videos');

export type WeekInfoType = {
	week_index: string;
  emails: {
    email: string; // 'week_1_clips_for_this_week'
    kind: 'clipList' | 'textBased';
    textContent: string;
    sendTime: string; // day of week ( '1', '2' ,'3'...) 0=Sunday, 1=Monday
  }[];
}

type WeekInfoRecords = Record<number, WeekInfoType>;


export const WeekInfo = atom<WeekInfoRecords, [{ data: WeekInfoRecords, fromDb?: boolean }], void>(
  (get) => get(AppStore).weeksInfo,
  (get, set, { data, fromDb = false }) => {
    set(AppStore, {
      ...get(AppStore),
      weeksInfo: data,
    });
    if (!fromDb) {
      set(AppDataDirty, !fromDb);
    }
  }
);

export const Category = atom<number, [{ data: number, fromDb?: boolean }], void>(
  (get) => get(AppStore).category,
  (get, set, { data, fromDb = false }) => {
    set(AppStore, {
      ...get(AppStore),
      category: data,
    });
    if (!fromDb) {
      set(AppDataDirty, !fromDb);
    }
  }
);

export const FormId = atom<number, [{ data: number, fromDb?: boolean }], void>(
	(get) => get(AppStore).formId,
	(get, set, { data, fromDb = false }) => {
		set(AppStore, {
			...get(AppStore),
			formId: data,
		});
		if (!fromDb) {
			set(AppDataDirty, !fromDb);
		}
	}
);
