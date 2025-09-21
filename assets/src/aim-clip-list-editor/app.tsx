import { createRoot } from "react-dom/client";
import "../css/main.css";
import type { AimClipListPost, AimClipListResources, AiVimeoResult, ClipListMetaItem } from "./types";
import { useMemo, useRef, useState } from "react";
import { Provider, useAtom } from "jotai";
import { API, AppState, AppStore, ListItems, store, type WeekInfoType } from "./store";
import toast, { Toaster } from "react-hot-toast";
import { AppStateListener } from "./components/app-state-listener";
import { UploadVideoCsv } from "./components/upload-video-csv";
import { EditorHeader } from "./components/editor-header";
import { TabbedVideoList, VideoList } from "./components/video-list";
import { PreviewVimeoVideo } from "./components/video-preview";
import { ListItemEditor } from "./components/list-item-editor";
import { useAPI } from "./hooks/useAPI";
import { Page } from "./components/page";
import { AimEmailListEditor } from "./components/email-list-editor";
import { MetaEditor } from "./components/meta-editor";

type AppProps = {
  apiUrl: string;
  nonce: string;
  post: AimClipListPost;
  postId: number | string;
  items: ClipListMetaItem[];
  previewList: Record<string, Omit<AiVimeoResult, keyof ClipListMetaItem>>;
  resources: AimClipListResources[];
  weeksInfo: Record<number, WeekInfoType>;
  formId: number;
  category: number;
  clipListCategories: Record<number, string>;
}

function App({
  previewList,
  apiUrl,
  nonce,
  post,
  postId,
  items,
  resources,
  weeksInfo = {},
  formId,
  category,
  clipListCategories,
}: AppProps) {
  const [store, setAppStore] = useAtom(AppStore);
  const [, setAPI] = useAtom(API);
  const api = useAPI();
  const [, setAppState] = useAtom(AppState);
  const [_items, setItems] = useAtom(ListItems);

  useMemo(() => {
    setAPI({
      url: apiUrl,
      nonce: nonce,
    });
    setAppStore({ post, postId, items, resources, weeksInfo, formId, category });
  }, [apiUrl, nonce]);

  function upgradeToEditPost(postId: number) {
    const url = new URL(window.location.href);
    if (!url.searchParams.has('post_id')) {
      toast('Redirecting to edit page...');
      url.searchParams.delete('new')
      url.searchParams.set('post_id', postId.toString());
      window.location.href = url.toString();
      return;
    }
  }


  const listWithPreview = useMemo(() => {
    return store.items.map(item => {
      const previewDetails = previewList[item.vimeoId];
      if (!previewDetails) {
        throw new Error("Preview details not found");
      }
      console.log("rebuilding preview");
      return {
        ...previewDetails,
        ...item,
      }
    })
  }, [_items, previewList]);

  const [showingUploadCsv, setShowingUploadCsv] = useState(!(items?.length && items.length > 0));
  const [showingMetaEditor, setShowingMetaEditor] = useState(false);

  const menuItems = [
    {
      label: "Edit Clip List Meta",
      onClick: () => {
        setShowingMetaEditor(s => !s);
      },
    },
    {
      label: showingUploadCsv ? 'Hide Uploader' : 'Upload CSV',
      onClick: () => {
        setShowingUploadCsv(s => !s);
      },
    },
  ];

  const previewRef = useRef<HTMLIFrameElement>(null);
  const [vimeoInstance, setVimeoInstance] = useState<Vimeo | undefined>(undefined);

  const [activeClipId, setActiveClipId] = useState<string>((store.items[0] && store.items[0]?.clip_id) ?? '');
  const { selectedItem, selectedPreview } = useMemo(() => {
    const selectedItem = store.items.find(v => v.clip_id === activeClipId);
    if (!selectedItem) {
      return {
        selectedItem: undefined,
        selectedPreview: undefined,
      }
    }
    const selectedPreview = { ...previewList[selectedItem.vimeoId]!, ...selectedItem }
    return {
      selectedItem,
      selectedPreview,
    }
  }, [activeClipId, _items, previewList]);

  async function duplicateItem(item: ClipListMetaItem) {
    const newId = await api.post('/get-new-clip-id', {})
    if (newId.code) {
      setAppState({
        status: 'error',
        error: newId.message,
      });
      return;
    } else {
      setAppState({
        status: 'success',
      });
    }

    const newItem = {
      ...item,
      clip_id: newId,
    }
    setItems({
      data: [..._items, newItem],
    })
    setActiveClipId(newId);
  }

  const [activeWeek, setActiveWeek] = useState(1);

  function addVideoToWeek(clip_id: string) {
    const itemIdx = _items.findIndex(v => v.clip_id === clip_id);
    if (itemIdx === -1) {
      toast.error("Item not found");
      return;
    }
    const item = _items[itemIdx]!;
    if (item.week_index === activeWeek) {
      toast.error("Item already in week");
      return;
    }
    item.week_index = activeWeek;
    item.in_list = true;
    const newItems = [..._items];
    newItems[itemIdx] = item;
    setItems({
      data: [...newItems],
    })
    toast.success("Item added to week");
  }

  return <>
    <div className="max-w-[2000px] bg-white p-4 lg:p-8 xl:p-12 xl:py-8 rounded-lg shadow-sm border-neutral-200 border flex flex-col gap-6 items-start">
      <EditorHeader menuItems={menuItems} />
      <MetaEditor categories={clipListCategories} setShowing={setShowingMetaEditor} isShowing={showingMetaEditor} />
      <UploadVideoCsv isShowing={showingUploadCsv} onUpload={upgradeToEditPost} setShowing={setShowingUploadCsv} />
      <Page slug={'videos'}>
        <div className="flex flex-row items-start justify-between w-full ">
          <TabbedVideoList results={listWithPreview} selectResult={setActiveClipId} selectedResult={activeClipId} />
          {listWithPreview.length > 0 ?
            <div className="w-full flex flex-col gap-4 lg:flex-row flex-1 max-h-full">
              <div className="flex flex-col gap-4 flex-1 shrink-1 min-w-2">
                <PreviewVimeoVideo video={selectedPreview} videoRef={previewRef} setVimeoInstance={setVimeoInstance} />
                {vimeoInstance ?
                  <ListItemEditor item={selectedItem} vimeoInstance={vimeoInstance} duplicateItem={duplicateItem} />
                  : null}
              </div>
            </div>
            : null}
        </div>
      </Page>
      <Page slug={'email-campaign'}>
        <div className="flex flex-row items-start justify-between w-full ">
          <VideoList videos={listWithPreview.filter(v => v.in_list && !v.week_index)} onVideoSelect={addVideoToWeek} />
          <div className="w-full flex flex-col gap-4 lg:flex-row flex-1 max-h-full">
            <div className="flex flex-col gap-4 flex-1 shrink-1 min-w-2 max-h-[70vh] overflow-y-scroll">
              <AimEmailListEditor activeWeek={activeWeek} setActiveWeek={setActiveWeek} videos={listWithPreview.filter(v => v.in_list && v.week_index)} />
            </div>
          </div>
        </div>
      </Page>
    </div>
    <AppStateListener />
    <Toaster containerStyle={{ top: '100px' }} />
  </>
}

const AppProviders = ({ children }: { children: React.ReactNode }) => {
  return <Provider store={store}>
    {children}
  </Provider>
}



export function renderApp(id: string, props: AppProps) {
  const el = document.getElementById(id);
  if (!el) {
    throw new Error("Element not found");
  }
  const root = createRoot(el);
  root.render(
    <AppProviders>
      <App {...props} />
    </AppProviders>
  );
}
