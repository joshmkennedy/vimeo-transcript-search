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
};

export const AppStore = atom<AppStoreT>({
  post: { title: "" },
  postId: 0,
  items: [],
  resources: [],
});

export const PostData = atom<AimClipListPost, [{data:AimClipListPost, fromDb?:boolean}], void>(
  (get) => get(AppStore)?.post,
  (get, set, {data, fromDb=false}) => {
    set(AppStore, {
      ...get(AppStore),
      post: data,
    });
    if(!fromDb){
      set(AppDataDirty, !fromDb);
    }
  }
)

export const PostId = atom<number | string, [(number | string), boolean], void>(
  (get) => get(AppStore)?.postId,
  (get, set, updatedPostId, fromDb=false) => {
    set(AppStore, {
      ...get(AppStore),
      postId: updatedPostId,
    });
    if(!fromDb){
      set(AppDataDirty, !fromDb);
    }
  }
);

export const Resources = atom<AimClipListResources[], [{data:AimClipListResources[], fromDb?:boolean|undefined}], void>(
  (get) => get(AppStore)?.resources,
  (get, set, {data, fromDb=false}) => {
    set(AppStore, {
      ...get(AppStore),
      resources: data,
    });
    if(!fromDb){
      set(AppDataDirty, !fromDb);
    }
  }
);

export const ListItems = atom<ClipListMetaItem[], [{data:ClipListMetaItem[], fromDb?:boolean|undefined}], void>(
  (get) => get(AppStore)?.items,
  (get, set, {data, fromDb=false}) => {
    console.log("commiting to store",data)
    set(AppStore, {
      ...get(AppStore),
      items: data,
    });
    if(!fromDb){
      set(AppDataDirty, !fromDb);
    }
  }
);

export const API = atom<{ url: string; nonce: string }>({ url: "", nonce: "" });


export const AppDataDirty = atom<boolean>(false);

export const AppLocation = atom<string>('videos');
