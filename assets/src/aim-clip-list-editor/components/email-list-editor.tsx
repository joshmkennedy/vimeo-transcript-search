import type { AimClipListResources, ClipListMetaItem } from "../types";
import { Label } from "../../components/ui/label";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { formatTime } from "@/lib/format-time";
import type { AiVimeoResult } from "../types";
import { Badge } from "@/components/ui/badge";
import toast from "react-hot-toast";
import { Provider, useAtom } from "jotai";
import { API, AppState, ListItems, Resources, WeekInfo, type WeekInfoRecords, type WeekInfoType } from "../store";
import { FormInput } from "@/components/ui/form-input";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { EllipsisVertical } from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useCallback, useEffect, useMemo, useRef, useState, type FormEvent } from "react";
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
  const [weekInfo] = useAtom(WeekInfo);
  aiRequestManager.setAPI(apiInfo.url, apiInfo.nonce);

  const videosByWeek = Object.groupBy(videos, v => v.week_index ?? -1);
  const resourcesByWeek = Object.groupBy(appResources ?? [], v => v.week_index ?? -1);

  async function generateEmailMarkup(
    videos: AiVimeoResult[],
    resources: { link: string, label: string; }[],
    introContent: string,
    weekIndex: string,
  ) {

    const response = await api.post('/email-preview', {
      postId: parseInt(window.vtsACLEditor.postId.toString()),
      content: introContent,
      resources,
      videos: videos.map(v => {
        return {
          image_url: v.pictures.base_link,
          vimeoId: v.vimeoId,
          title: v.name,
          summary: v.summary,
          video_type: v.video_type,
          clip_id: v.clip_id,
        }
      }),
      week_index: weekIndex,
    })
    if (response.code) {
      toast.error(response.message);
      return null;
    }

    return response;
  }

  const [emailConfig, setEmailConfig] = useState<any>(undefined);

  async function previewEmail(weekIndex: string) {
    const weekIndexNum = parseInt(weekIndex.replace('week_', ''));
    const videosInWeek = videosByWeek[weekIndexNum]!;
    const emailIntro = weekInfo[weekIndex]?.emails[0]?.textContent ?? ""
    const email = await generateEmailMarkup(videosInWeek, resourcesByWeek[weekIndexNum] ?? [], emailIntro, weekIndex);
    if (!email) {
      toast.error("No email markup found");
      return;
    }
    setEmailConfig(email)
  }

  function handleAccordionValueChange(week: string) {
    setActiveWeek(Number(week));
    setEmailConfig(undefined);
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
    <AddAiSummaryButton weeks={Object.keys(weekInfo).map(Number)} btnText={`Fill in missing Email Intros`} overwrite={false} />
    <Accordion type="single" collapsible onValueChange={handleAccordionValueChange} value={activeWeek.toString()}>
      {WEEKS.map((week, i) => {
        const weekIndexNum = i + 1;
        return <AccordionItem key={i} value={weekIndexNum.toString()}>
          <AccordionTrigger className="no-underline w-full " >
            <div className="flex flex-row items-center gap-2 ">
              {week}
              <Badge className="ml-2" variant="secondary">Vidoes {videosByWeek[weekIndexNum]?.length ?? 0}</Badge>
              <Badge className="ml-2" variant="secondary">Resources {resourcesByWeek[weekIndexNum]?.length ?? 0}</Badge>
            </div>
          </AccordionTrigger>
          <AccordionContent className="flex flex-col gap-4">
            <header className="flex flex-row items-center gap-2 justify-between">
              <p className="text-lg font-bold">Week {i + 1}</p>

              <PreviewEmailContent onOpen={() => previewEmail(`week_${i + 1}`)} email={emailConfig} />

              <AddAiSummaryButton weeks={[weekIndexNum]} btnText={`Add Ai Summary to Week ${weekIndexNum}`} overwrite={true} />
            </header>

            <WeekInfoEditor weekIndexNum={weekIndexNum} />

            {videosByWeek[weekIndexNum] && videosByWeek[weekIndexNum].length > 0 ? (
              <>
                <VideosInWeek weekIndex={weekIndexNum} videos={videosByWeek[weekIndexNum]} />
                <ResourcesInWeek weekIndex={weekIndexNum} resources={resourcesByWeek[weekIndexNum] ?? []} />
              </>
            ) : null}
          </AccordionContent>
        </AccordionItem>
      })}
    </Accordion>
  </div>
}

function PreviewEmailContent({ email, onOpen }: { email: any, onOpen: () => Promise<void> }) {
  const [isLoading, setIsLoading] = useState(false);
  function handleOpenChange(open: boolean) {
    if (open && !email) {
      setIsLoading(true);
      onOpen().finally(() => setIsLoading(false));
    }
  }

  return <Dialog onOpenChange={handleOpenChange}>
    <DialogTrigger asChild>
      <Button variant="secondary" onClick={onOpen}>Preview Email</Button>
    </DialogTrigger>
    <DialogContent className="sm:w-full sm:max-w-[800px] ">
      <DialogHeader>
        <DialogTitle>Preview Email</DialogTitle>
        <DialogDescription>
          <p>This is a preview of the email that will be sent to your subscribers.</p>
        </DialogDescription>
      </DialogHeader>
      {email?.content ?
        <PreviewEmailDisplay email={email} /> : <p>Loading...</p>
      }
    </DialogContent>
  </Dialog>
}

function PreviewEmailDisplay({ email }: { email: { subject: string, content: string } }) {
  return <div>
    <header className="border-b">
      <h1>{email.subject}</h1>
    </header>
    <iframe srcDoc={email.content} width="100%" style={{ height: "70vh" }} title={email.subject}></iframe>
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

  const [openDetails, setOpenDetails] = useState<(() => void) | undefined>(undefined);

  return <div className="flex flex-col gap-2 bg-neutral-100 p-4 rounded-md hover:bg-neutral-200">
    <header className="relative">
      <div>
        <div className="flex flex-row items-start gap-2 ">
          <p>{video.name}</p>
          <VideoInWeekDetails videoInfo={video} save={handleSave} setOpenDetails={setOpenDetails} />
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
      <Button variant="secondary" onClick={() => openDetails?.()}>
        Watch
      </Button>
    </div>
  </div>
}


function VideoInWeekDetails({ videoInfo, save, setOpenDetails }: { videoInfo: AiVimeoResult, save: (options: ClipListMetaItem) => void, setOpenDetails: Function }) {
  const [isopen, setIsOpen] = useState(false);
  const [updatedVideoType, setUpdatedVideoType] = useState(videoInfo.video_type ?? "secondary-lecture");
  const [updatedVideoSummary, setUpdatedSummary] = useState(videoInfo.summary ?? "");

  // allow parent to open the details
  useEffect(() => { setOpenDetails(() => () => setIsOpen(true)) }, [])

  function handleSave() {
    const updated: ClipListMetaItem = {
      ...{
        clip_id: videoInfo.clip_id,
        vimeoId: videoInfo.vimeoId,
        start: videoInfo.start,
        end: videoInfo.end,
        summary: videoInfo.summary,
        in_list: videoInfo.in_list,
        week_index: videoInfo.week_index,
      },
      video_type: updatedVideoType,
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
        <DialogTitle className="m-0">{videoInfo.name}</DialogTitle>
        <DialogDescription className="m-0">
          {formatTime(videoInfo.start)} - {videoInfo.end ? formatTime(videoInfo.end) : 'end'}
        </DialogDescription>
      </DialogHeader>
      <div className="flex flex-col gap-4">
        <FormInput className="flex-row justify-between">
          <Label className="text-sm font-bold">Video Type</Label>
          <VideoInWeekTypeOptions value={updatedVideoType} onChange={setUpdatedVideoType} />
        </FormInput>

        <div className="flex flex-col gap-2">
          <Label className="text-sm font-bold" htmlFor={`${videoInfo.clip_id}-summary`}>Video Summary</Label>
          <Textarea name="summary" id={`${videoInfo.clip_id}-summery`} value={updatedVideoSummary} onChange={(e) => setUpdatedSummary(e.target.value)} />
        </div>
        <div>
          <iframe src={videoInfo.player_embed_url} width="100%" height="400" title={videoInfo.name}></iframe>
        </div>
      </div>
      <DialogFooter>
        <Button onClick={handleSave}>Save</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog >
}
function VideoInWeekTypeOptions({ value, onChange }: { value: string, onChange: (value: string) => void }) {
  return <Select name="video_type" onValueChange={onChange} value={value}>
    <SelectTrigger className="w-[180px]">
      <SelectValue placeholder="Select a video type" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="lecture">Lecture</SelectItem>
      <SelectItem value="secondary-lecture">Secondary Lecture</SelectItem>
      <SelectItem value="lab">Lab</SelectItem>
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

export function WeekInfoEditor({  weekIndexNum }: { weekIndexNum: number }) {
  const [weekInfo, setWeekInfo] = useAtom(WeekInfo);
  const weekIndexSlug = `week_${weekIndexNum}`;

  const week = useMemo(()=>weekInfo[weekIndexSlug] ?? defaultWeekInfo(weekIndexNum), [weekIndexNum, weekInfo]);

  const [localEmailInfo, setLocalEmailInfo] = useState(week.emails[0]!);

  const setLocalEmailInfoCB = useCallback((email: Partial<WeekInfoType['emails'][number]>) => {
    setLocalEmailInfo({ ...localEmailInfo, ...email });
  }, [localEmailInfo]);

  useEffect(() => {
    console.log("setting local email info, ", week.emails[0]!);
    setLocalEmailInfoCB(week.emails[0]!);
  }, [week]);

  function saveWeekInfo(weekIndexSlug: string, updatedInfo: Partial<WeekInfoType['emails'][number]>) {
    const copy = { ...weekInfo };
    copy[weekIndexSlug] = { ...week, emails: [{ ...week.emails[0], ...updatedInfo }] } as WeekInfoType;
    setWeekInfo({
      data: copy,
    })
  }
  return <form
    className="flex flex-col gap-2 p-2 bg-neutral-50/20 rounded-md "
    onSubmit={(e) => { e.preventDefault(); saveWeekInfo(weekIndexSlug, localEmailInfo); }}
  >
    <div className="flex flex-row items-center gap-2 justify-end">
      <Button type="submit" variant="secondary">Save Intro</Button>
    </div>
    <FormInput>
      <Label className="text-sm font-bold">Week {weekIndexNum}'s Introduction to the Email</Label>
      <textarea
        placeholder="Week Intro"
        className="min-h-[200px] rounded-sm w-full md:text-lg border-1 border-neutral-200 focus-visible:border-transparent h-auto font-medium focus-visible:ring-4 focus-visible:ring-blue-200/35"
        value={localEmailInfo.textContent}
        onChange={(e) => setLocalEmailInfo({ ...localEmailInfo, textContent: e.target.value })}
      ></textarea>
    </FormInput>
  </form>
}


function AddAiSummaryButton({ weeks, btnText, overwrite }: { weeks: number[], btnText: string, overwrite?: boolean }) {
  const [weekInfo, setWeekInfo] = useAtom(WeekInfo);
  const [items, setItems] = useAtom(ListItems);
  const [aiManagerMessages] = useAtom(AiRequestManagerMessages);
  const [seenMessageCount, setSeenMessageCount] = useState(0);
  const [progress, setProgress] = useState({ done: 0, total: 0 });
  const [isOpen, setIsOpen] = useState(false);
  const [currentState, setCurrentState] = useState<"confirm" | "progress" | "finished">("confirm");

  const weekHasIntro = (weekIndexNum: number) => {
    const weekIndexSlug = `week_${weekIndexNum}`;
    const content = weekInfo[weekIndexSlug]?.emails[0]?.textContent;
    return (content?.length && content !== defaultWeekInfo(weekIndexNum).emails[0]!.textContent);
  }

  const {
    aiEmailsToCreate,
    aiVideosToCreate,
  } = useMemo(() => {
    const videos = items.filter(item => weeks.includes(item.week_index ?? -1));
    const aiEmails = (overwrite ? weeks : weeks.filter(weekIndex => !weekHasIntro(weekIndex)))
      .map(weekIndex => ({
        weekIndex: `week_${weekIndex}`,
        clipIds: videos.filter(v => v.week_index === weekIndex).map(v => v.clip_id),
        summary: undefined,
      }));
    const aiEmailVideoClipIds = aiEmails.map(email => email.clipIds).flat();
    const aiVideos = videos
      .filter((video) => aiEmailVideoClipIds.includes(video.clip_id))
      .map(v => ({
        vimeoId: v.vimeoId,
        start: v.start,
        end: v.end,
        clipId: v.clip_id,
        summary: undefined,
      }))

    return {
      aiEmailsToCreate: aiEmails,
      aiVideosToCreate: aiVideos,
    }
  }, [weeks, overwrite, items])

  const handleIncMessageCount = useCallback(() => setSeenMessageCount(prev => prev + 1), [setSeenMessageCount]);


  async function handleAddAiSummary() {
    // if we overwrite then return all the emails and their videos else only the ones that don't have an intro/have the
    // default intro
    aiRequestManager.setWork({ videos: aiVideosToCreate, emails: aiEmailsToCreate });
    await aiRequestManager.start(overwrite);
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
    console.log("before update", weekInfo);
    const weekIndexNum = parseInt(emails[0]?.weekIndex?.split('_')?.[1] ?? '0')
    if(weekIndexNum === 0){
      throw new Error("weekIndex is 0, das bad yo!");
    }
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
     
    console.log("UPDATING WEEK INFO", `week_${weekIndexNum}`, weekInfo);

    if(!(weekIndexNum in weekInfo)){
      weekInfo[`week_${weekIndexNum}`] = defaultWeekInfo(weekIndexNum);
    }

    const newWeekInfo = Object.entries(weekInfo).reduce((newWeekInfo, [weekIndexSlug, weekInfoItem]) => {
      const weekIndexNum = parseInt(weekIndexSlug.replace('week_', ''));
      if (weeks.includes(Number(weekIndexNum))) {
				const email = emails.find(e => e.weekIndex === weekIndexSlug);
				if (email && email.summary?.length) { 
					weekInfoItem.emails[0]!.textContent = email.summary;
				}
      }
      newWeekInfo[weekIndexSlug] = weekInfoItem;
      return newWeekInfo;
    }, {} as WeekInfoRecords)

    console.log("after update", newWeekInfo);

    setWeekInfo({
      data: newWeekInfo
    })
    setCurrentState("finished");
  }, [items, setItems, weekInfo, setWeekInfo, weeks])

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
    {btnText}
  </Button>;

  const dialogTitle = titleBasedOnState(currentState, aiEmailsToCreate.length, aiVideosToCreate.length);
  const dialogDescription = dialogDescriptionBasedOnState(currentState, aiEmailsToCreate.length, aiVideosToCreate.length, Boolean(overwrite));
  const showingGenerateSummariesButton = shouldShowGenerateSummariesButton(currentState, aiVideosToCreate.length);

  return <Dialog open={isOpen} onOpenChange={handleOpenChange}>
    <DialogTrigger asChild>
      {button}
    </DialogTrigger>
    <DialogContent>
      <DialogHeader>
        <DialogTitle className="mb-0">
          {dialogTitle}
        </DialogTitle>
        <DialogDescription>
          {dialogDescription}
        </DialogDescription>
      </DialogHeader>
      {
        currentState == "finished" || currentState == "progress" ? (
          <div className="mb-4">
            <div className="flex flex-row items-center gap-2">
              <Progress value={Math.floor((progress.done / progress.total) * 100)} className="w-full" />
            </div>
          </div>
        ) : null}

      {showingGenerateSummariesButton ?
        <Button onClick={handleConfirm} >
          Generate Summaries
        </Button> : null}

      {currentState == "finished" ?
        <div>
          <Button size={"sm"} onClick={() => setIsOpen(false)}>Close</Button>
        </div> : null}
    </DialogContent>
  </Dialog>
}

function titleBasedOnState(currentState: "finished" | "confirm" | "progress", aiEmailsToCreate: number, aiVideosToCreate: number) {
  if (currentState == "finished") return "Finished";
  if (currentState == "confirm" && aiVideosToCreate <= 0) return "No Videos to Summarize";
  if (currentState == "confirm") return "Ready?"
  return `Generating Summaries for ${aiEmailsToCreate} Email Intros, and ${aiVideosToCreate} Video Summaries`
}
function dialogDescriptionBasedOnState(currentState: "finished" | "confirm" | "progress", aiEmailsToCreate: number, aiVideosToCreate: number, overwrite: boolean) {
  if (currentState == "finished") return "The summaries have been added to this week, please review.";
  if (currentState == "confirm" && aiVideosToCreate <= 0 && overwrite == false) return "This button wont override content already set, if you wish to override the content, then you must go into each of the weeks and click the generate summary button for that one week";
  if (currentState == "confirm" && aiVideosToCreate <= 0 && overwrite == true) return "Couldnt find any videos to summarize, does this week have any videos?";

  if (currentState == "confirm") return "This will replace the summary and all the videos in this weeks email, are you sure you want to continue?";
  return `Please wait, and dont close this window while the AI generates the summary for this weeks email and video.`
}
function shouldShowGenerateSummariesButton(currentState: "finished" | "confirm" | "progress", aiVideosToCreate: number) {
  return currentState == "confirm" && aiVideosToCreate > 0
}
