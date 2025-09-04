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
import { useAtom } from "jotai";
import { AppState, ListItems, Resources } from "../store";
import { FormInput } from "@/components/ui/form-input";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { EllipsisVertical } from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useState, type FormEvent } from "react";
import { useAPI } from "../hooks/useAPI";

const WEEKS = ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5", "Week 6", "Week 7", "Week 8", "Week 9", "Week 10", "Week 11", "Week 12", "Week 13", "Week 14"];
export function AimEmailListEditor({ activeWeek, setActiveWeek, videos }: { activeWeek: number, setActiveWeek: (week: number) => void, videos: AiVimeoResult[] }) {
  const [, setAppState] = useAtom(AppState);
  const [appResources, setResources] = useAtom(Resources);
  const api = useAPI();
  const videosByWeek = Object.groupBy(videos, v => v.week_index ?? -1);
  console.log(videosByWeek);
  const resourcesByWeek = Object.groupBy(appResources ?? [], v => v.week_index ?? -1);
  console.log(resourcesByWeek);

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
          <AccordionContent>
            <header className="flex flex-row items-center gap-2 justify-between">
              <p className="text-lg font-bold">Week {i + 1}</p>
              <Button variant="secondary" onClick={() => previewEmail(weekIndex)}>Preview Email</Button>
            </header>
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
      return <div>
        <a
          className="text-blue-800 no-underline hover:text-blue-600 font-bold text-base px-2 py-1"
          href={resource.link}
          target="_blank"
          rel="noreferrer"
        >{resource.label}</a>
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
    console.log(newItems[index].video_type);
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
    <header>
      <div>
        <p>{video.name}</p>
        <p className="text-xs">
          {formatTime(video.start)} - {video.end ? formatTime(video.end) : 'end'}
        </p>
				<div>{video.video_type}</div>
      </div>
      <VideoInWeekMenu options={options} save={handleSave} />
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
  console.log(options);
  return <Dialog open={isopen} onOpenChange={(s) => setIsOpen(s)}>
    <DialogTrigger>
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
      <SelectItem value="lecture">Lecture</SelectItem>
      <SelectItem value="lab">Lab</SelectItem>
    </SelectContent>
  </Select>
}
