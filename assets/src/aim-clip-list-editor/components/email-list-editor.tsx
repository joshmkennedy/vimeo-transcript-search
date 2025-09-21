import type { AimClipListResources, ClipListMetaItem } from "../types";
import { Label } from "../../components/ui/label";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"
import { Button } from "@/components/ui/button";
import { formatTime } from "@/lib/format-time";
import type { AiVimeoResult } from "../types";
import { Badge } from "@/components/ui/badge";
import toast from "react-hot-toast";
import { Provider, useAtom } from "jotai";
import { API, AppState, ListItems, Resources, WeekInfo, type WeekInfoType } from "../store";
import { FormInput } from "@/components/ui/form-input";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { EllipsisVertical } from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useCallback, useEffect, useRef, useState, type FormEvent } from "react";
import { useAPI } from "../hooks/useAPI";
import { AiRequestManager, MessagesStore as AiRequestManagerMessages, type AiEmail, type AiVideo } from "../ai-request-manager/manager";
import { Progress } from "@/components/ui/progress";

const WEEKS = ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5", "Week 6", "Week 7", "Week 8", "Week 9", "Week 10", "Week 11", "Week 12", "Week 13", "Week 14"];

const aiRequestManager = new AiRequestManager();

export function AimEmailListEditor({ activeWeek, setActiveWeek, videos }: { activeWeek: number, setActiveWeek: (week: number) => void, videos: AiVimeoResult[] }) {
  const [, setAppState] = useAtom(AppState);
  const [appResources, setResources] = useAtom(Resources);
  const [apiInfo] = useAtom(API);
  const api = useAPI();
  aiRequestManager.setAPI(apiInfo.url, apiInfo.nonce);

  const videosByWeek = Object.groupBy(videos, v => v.week_index ?? -1);
  const resourcesByWeek = Object.groupBy(appResources ?? [], v => v.week_index ?? -1);

  async function previewEmail(weekIndex: number) {
    const videosInWeek = videosByWeek[weekIndex]!;
    const markup = generateEmailMarkup(videosInWeek, [] as any);
    if (!markup) {
      toast.error("No email markup found");
      return;
    }
    // setEmailMarkupModel(markup);
    toast.success("Email previewed");
  }

  async function buildResources() {
    const data = await api.post('/build-resources', {
      weeks: videosByWeek,
    })
    if (data.code) {
      setAppState({
        status: 'error',
        error: data.message,
      });
      return;
    }
    if (data.resources) {
      setResources({ data: data.resources });
    }
  }
  return <div className="">
    <Button variant="secondary" onClick={buildResources}>Find Resources Weeks</Button>
    <Accordion type="single" collapsible onValueChange={(week) => setActiveWeek(Number(week))} value={activeWeek.toString()}>
      {WEEKS.map((week, i) => {
        const weekIndex = i + 1;
        return <AccordionItem key={i} value={weekIndex.toString()}>
          <AccordionTrigger className="no-underline w-full " >
            <div className="flex flex-row items-center gap-2 ">
              {week}
              <Badge className="ml-2" variant="secondary">Vidoes {videosByWeek[weekIndex]?.length ?? 0}</Badge>
              <Badge className="ml-2" variant="secondary">Resources {resourcesByWeek[weekIndex]?.length ?? 0}</Badge>
            </div>
          </AccordionTrigger>
          <AccordionContent className="flex flex-col gap-4">
            <header className="flex flex-row items-center gap-2 justify-between">
              <p className="text-lg font-bold">Week {i + 1}</p>
              <Button variant="secondary" onClick={() => previewEmail(weekIndex)}>Preview Email</Button>
              <AddAiSummaryButton weekIndex={weekIndex} videos={videosByWeek[weekIndex] ?? []} />
            </header>

            <WeekInfoEditor weekIndex={weekIndex} />

            {videosByWeek[weekIndex] && videosByWeek[weekIndex].length > 0 ? (
              <>
                <VideosInWeek weekIndex={weekIndex} videos={videosByWeek[weekIndex]} />
                <ResourcesInWeek weekIndex={weekIndex} resources={resourcesByWeek[weekIndex] ?? []} />
              </>
            ) : null}
          </AccordionContent>
        </AccordionItem>
      })}
    </Accordion>
  </div>
}

function VideosInWeek({ weekIndex, videos, }: { weekIndex: number, videos: AiVimeoResult[] }) {
  return <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    {videos.map(video => {
      return <VideoInWeek weekIndex={weekIndex} video={video} key={video.clip_id} />
    })}
  </div>
}

function ResourcesInWeek({ weekIndex, resources }: { weekIndex: number, resources: AimClipListResources[] }) {
  return <div className="flex flex-col gap-2">
    {resources.map(resource => {
      return <div key={resource.link}>
        <a
          className="text-blue-800 no-underline hover:text-blue-600 font-bold text-base px-2 py-1"
          href={resource.link}
          target="_blank"
          rel="noreferrer"
        >
          <span dangerouslySetInnerHTML={{ __html: resource.label }} />
        </a>
      </div>
    })}
  </div>
}

function VideoInWeek({ weekIndex, video }: { weekIndex: number, video: AiVimeoResult }) {
  const [items, setItems] = useAtom(ListItems);
  function handleRemove() {
    const index = items.findIndex(v => v.clip_id === video.clip_id);
    if (index === -1) {
      toast.error("Item not found");
      return;
    }
    const item = items[index]!;
    item.week_index = undefined;
    setItems({
      data: [...items],
    })
    toast.success("Item removed from week");
  }

  function handleSave(options: ClipListMetaItem) {
    const index = items.findIndex(v => v.clip_id === video.clip_id);
    if (index === -1) {
      toast.error("Item not found");
      return;
    }
    const item = items[index]!;
    const newItem = {
      ...item,
      ...options,
    }
    const newItems = [...items];
    newItems[index] = newItem;

    setItems({
      data: newItems,
    })
    toast.success("Updated Item Meta");

  }

  const options: ClipListMetaItem = {
    video_type: video.video_type ?? "lecture",
    in_list: true,
    clip_id: video.clip_id,
    vimeoId: video.vimeoId,
    start: video.start,
    end: video.end,
    summary: video.summary,
    week_index: weekIndex,
    // TODO: add topics if we have them
  }

  return <div className="flex flex-col gap-2 bg-neutral-100 p-4 rounded-md hover:bg-neutral-200">
    <header className="relative">
      <div>
        <div className="flex flex-row items-start gap-2 ">
          <p>{video.name}</p>
          <VideoInWeekMenu options={options} save={handleSave} />
        </div>
        <p className="text-xs">
          {formatTime(video.start)} - {video.end ? formatTime(video.end) : 'end'}
        </p>
        <div>{video.video_type}</div>
      </div>
    </header>
    <div>
      <img className="w-full rounded-md" src={video.pictures.base_link} alt={video.name} />
    </div>
    <div className="flex flex-row items-center gap-2">
      <Button variant="destructive" onClick={handleRemove}>
        Remove
      </Button>
      <Button variant="secondary" onClick={() => toast.error("Not implemented")}>
        Watch
      </Button>
    </div>
  </div>
}

function generateEmailMarkup(videos: AiVimeoResult[], resources: { link: string, label: string; }) {
  toast.error("Not implemented");
  return null;
}

function VideoInWeekMenu({ options, save }: { options: ClipListMetaItem, save: (options: ClipListMetaItem) => void }) {

  const [isopen, setIsOpen] = useState(false);
  function handleSave(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    const updated = {
      ...options,
      video_type: formData.get('video_type')?.toString(),
    }
    save(updated);
    setIsOpen(false);
  }

  return <Dialog open={isopen} onOpenChange={(s) => setIsOpen(s)}>
    <DialogTrigger className="hover:bg-neutral-300 p-2 rounded-md relative -top-1 -right-1">
      <EllipsisVertical className="h-5 w-5" />
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Options For Video</DialogTitle>
        <DialogDescription>
          <p>These options effect how the video is displayed in the email.</p>
        </DialogDescription>
      </DialogHeader>
      <div>
        <form onSubmit={handleSave} className="flex flex-col gap-4">
          <FormInput>
            <Label className="text-sm font-bold">Video Type</Label>
            <VideoInWeekTypeOptions value={options.video_type ?? undefined} />
          </FormInput>
          <Button variant="secondary" type="submit">Save</Button>
        </form>
      </div>
    </DialogContent>
  </Dialog>
}
function VideoInWeekTypeOptions({ value }: { value: string | undefined }) {
  return <Select name="video_type" defaultValue={value || "main"}>
    <SelectTrigger className="w-[180px]">
      <SelectValue placeholder="Select a video type" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="Lecture">Lecture</SelectItem>
      <SelectItem value="Secondary Lecture">Secondary Lecture</SelectItem>
      <SelectItem value="Lab">Lab</SelectItem>
    </SelectContent>
  </Select>
}

const defaultWeekInfo = (weekIndex: number) => ({
  week_index: `week_${weekIndex}`,
  emails: [
    {
      email: `week_${weekIndex}_videos_for_this_week`,
      kind: 'clipList' as const,
      textContent: 'Write the introduction to this weeks videos here',
      sendTime: '1', // send on the following Monday,
    },
  ],
});

export function WeekInfoEditor({ weekIndex }: { weekIndex: number }) {
  const [weekInfo, setWeekInfo] = useAtom(WeekInfo);

  const week = weekInfo[weekIndex] ?? defaultWeekInfo(weekIndex);

  const [localEmailInfo, setLocalEmailInfo] = useState(week.emails[0]!);

  function saveWeekInfo(weekIndex: number, updatedInfo: Partial<WeekInfoType['emails'][number]>) {
    const copy = { ...weekInfo };
    copy[weekIndex] = { ...week, emails: [{ ...week.emails[0], ...updatedInfo }] } as WeekInfoType;
    setWeekInfo({
      data: copy,
    })
  }
  return <form
    className="flex flex-col gap-2 p-2 bg-neutral-50/20 rounded-md "
    onSubmit={(e) => { e.preventDefault(); saveWeekInfo(weekIndex, localEmailInfo); }}
  >
    <div className="flex flex-row items-center gap-2 justify-end">
      <Button onClick={() => toast.error("Not Implemented Yet")} className="bg-purple-50 text-purple-800 hover:bg-purple-100 hover:text-purple-900 ">
        Generate Intro with Ai
      </Button>
      <Button type="submit" variant="secondary">Save Intro</Button>
    </div>
    <FormInput>
      <Label className="text-sm font-bold">Week {weekIndex}'s Introduction to the Email</Label>
      <textarea
        placeholder="Week Intro"
        className="rounded-sm w-full md:text-lg border-1 border-neutral-200 focus-visible:border-transparent h-auto font-medium focus-visible:ring-4 focus-visible:ring-blue-200/35"
        value={localEmailInfo.textContent}
        onChange={(e) => setLocalEmailInfo({ ...localEmailInfo, textContent: e.target.value })}
      ></textarea>
    </FormInput>
  </form>
}


function AddAiSummaryButton({ weekIndex, videos }: { weekIndex: number, videos: AiVimeoResult[] }) {
  const [weekInfo, setWeekInfo] = useAtom(WeekInfo);
  const [items, setItems] = useAtom(ListItems);
  const [aiManagerMessages] = useAtom(AiRequestManagerMessages);
  const [seenMessageCount, setSeenMessageCount] = useState(0);
  const [progress, setProgress] = useState({ done: 0, total: 0 });
  const [isOpen, setIsOpen] = useState(false);
  const [currentState, setCurrentState] = useState<"confirm" | "progress" | "finished">("confirm");

  const handleIncMessageCount = useCallback(() => setSeenMessageCount(prev => prev + 1), [setSeenMessageCount]);

  async function handleAddAiSummary() {
    const aiVideos = videos.map(v => ({
      vimeoId: v.vimeoId,
      start: v.start,
      end: v.end,
      clipId: v.clip_id,
      summary: undefined,
    }))
    const aiEmail = {
      weekIndex: `week_${weekIndex}`,
      clipIds: videos.map(v => v.clip_id),
      summary: undefined,
    };
    aiRequestManager.setWork({ videos: aiVideos, emails: [aiEmail] });
    await aiRequestManager.start();
  }

  function handleOpenChange(open: boolean) {
    setIsOpen(open);
    setSeenMessageCount(0)
    setProgress({ done: 0, total: 0 });
    if (open) {
      setCurrentState("confirm");
    }
  }

  async function handleConfirm() {
    setCurrentState("progress");
    await handleAddAiSummary();
  }

  const updateWithAiResults = useCallback(({ videos, emails }: { videos: AiVideo[], emails: AiEmail[] }) => {
    console.log(items, weekInfo);
    const clipIds = videos.map(v => v.clipId);
    const newItems = items.map(item => {
      if (clipIds.includes(item.clip_id)) {
        item.summary = videos.find(v => v.clipId === item.clip_id)?.summary;
      }
      return item;
    })

    setItems({
      data: newItems,
    })

    const week = weekInfo[weekIndex];
    if (week) {
      week.emails[0]!.textContent = emails.find(e => e.weekIndex === `week_${weekIndex}`)?.summary ?? "";
      const copy = { ...weekInfo, [weekIndex]: week };
      setWeekInfo({
        data: copy
      })
    }
    setCurrentState("finished");
  }, [items, setItems, weekInfo, setWeekInfo, weekIndex])

  const updateProgress = useCallback(({ done, total }: { done: number, total: number }) => {
    setProgress({ done, total });
  }, [])


  useEffect(() => {
    if (isOpen) {
      const messages = aiManagerMessages.slice(seenMessageCount);
      if (!messages.length) return;
      for (const message of messages) {
        switch (message.type) {
          case 'error': {
            if (typeof message.message != "string") {
              console.error("bad error message recieved");
              console.error(message.message);
              break;
            }
            toast.error(message.message);
            break;
          }
          case 'success': {
            if (
              typeof message.message != "object"
              || !('content' in message.message && 'data' in message.message)
              || typeof message.message.content != "string"
              || !('videos' in message.message.data && 'emails' in message.message.data)
            ) {
              console.error("bad success message recieved");
              console.error(message.message);
              break;
            }
            toast.success(message.message.content);
            updateWithAiResults(message.message.data);
            break;
          }
          case 'info': {
            if (typeof message.message != "string") {
              console.error("bad info message recieved");
              console.error(message.message);
              break;
            }
            toast(message.message);
            break;
          }
          case 'progress': {
            if (typeof message.message != "object" || !('done' in message.message && 'total' in message.message)) {
              console.error("bad progress message recieved");
              console.error(message.message);
              break;
            }
            updateProgress(message.message);
            break;
          }
          default: {
            console.error("unknown message type recieved", message);
          }
        }
        handleIncMessageCount()
      }
    }
  }, [aiManagerMessages, updateWithAiResults, updateProgress, isOpen, items])

  const button = <Button className="bg-purple-50 text-purple-800 hover:bg-purple-100 hover:text-purple-900 ">
    Add Ai Summary to Week {weekIndex}
  </Button>;

  return <Dialog open={isOpen} onOpenChange={handleOpenChange}>
    <DialogTrigger asChild>
      {button}
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle className="mb-0">
          {currentState == "finished"
            ? "Finished"
            : currentState == "confirm"
              ? "Ready?"
              : `Generating Summaries for Week ${weekIndex}`}
        </DialogTitle>
        <DialogDescription>
          {currentState == "finished"
            ? "The summaries have been added to this week, please review."
            : currentState == "confirm"
              ? "This will replace the summary and all the videos in this weeks email, are you sure you want to continue?"
              : "Please wait, and dont close this window while the AI generates the summary for this weeks email and video."
          }
        </DialogDescription>
      </DialogHeader>
      {
        currentState == "finished" || currentState == "progress" ? (
          <div className="mb-4">
            <div className="flex flex-row items-center gap-2">
              <Progress value={Math.floor((progress.done / progress.total) * 100)} className="w-full" />
            </div>
          </div>
        ) : (
          <Button onClick={handleConfirm} >
            Generate Summaries
          </Button>
        )}
      {currentState == "finished" ?
        <div>
          <Button size={"sm"} onClick={() => setIsOpen(false)}>Close</Button>
        </div> : null}
    </DialogContent>
  </Dialog>
}
