import { Button } from "@/components/ui/button";
import type { ClipListMetaItem } from "../types";
import { useEffect, useState, type FormEvent } from "react";
import { inPublishList } from "./video-list";
import { useAtom } from "jotai";
import { AppState, ListItems } from "../store";
import { Tabs, TabsContent, TabsList, VtsTabsTrigger } from "@/components/ui/tabs";
import { FormInput } from "@/components/ui/form-input";
import { Label } from "@/components/ui/label";
import { FormMessage, FormDescription, FormControl } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { FastForward, Rewind } from "lucide-react";
import { formatTime } from "@/lib/format-time";
import toast from "react-hot-toast";

export function ListItemEditor({ item, vimeoInstance, duplicateItem }: { item: ClipListMetaItem | undefined, vimeoInstance: Vimeo, duplicateItem: (item: ClipListMetaItem) => void }) {
  console.log(item?.clip_id);
  const [items, setItems] = useAtom(ListItems);
  const [startTime, setStartTime] = useState(item?.start ?? 0);
  const [endTime, setEndTime] = useState(item?.end ?? 0);
  const [summary, setSummary] = useState(item?.summary ?? '');
  const [inList, setInList] = useState(() => (Boolean(item?.in_list)));
  const [appState] = useAtom(AppState);

  useEffect(() => {
    setInList(Boolean(item?.in_list));
    setSummary(item?.summary ?? '');
    setStartTime(item?.start ?? 0);
    setEndTime(item?.end ?? 0);
  }, [item])


  async function handleSetStartTime() {
    const curr = await vimeoInstance.getCurrentTime();
    setStartTime(curr);
  }

  async function handleSetEndTime() {
    const curr = await vimeoInstance.getCurrentTime();
    setEndTime(curr);
  }
  function handleToggleInList() {
    console.log("in list", inList);
    setInList(s => {
      const newValue = !s;
      console.log("new value", newValue);
      return newValue;
    });
  }

  function handleSave() {
    if (!item) {
      return;
    }
    const itemIndex = items.findIndex(v => v.clip_id === item.clip_id);
    const copy = [...items];
    copy[itemIndex] = {
      ...item,
      start: startTime,
      end: endTime,
      in_list: inList,
      summary: summary,
    }
    setItems({
      data: copy
    })
  }

  if (!item || appState.status === 'loading') {
    return null;
  }

  return <div className="flex flex-col gap-6">
    <Tabs defaultValue="times" className="w-full lg:pr-12">
      <div className="flex flex-row items-center gap-2 justify-between mb-6">
        <TabsList className="w-full bg-white rounded-0 max-w-[500px]">
          <VtsTabsTrigger value="times">
            Times
          </VtsTabsTrigger>
          <VtsTabsTrigger value="summary">
            Summary
          </VtsTabsTrigger>
        </TabsList>
        <div className="flex flex-row items-center gap-2 justify-end">
          <Button onClick={() => duplicateItem(item)} variant={"secondary"} className="hover:bg-neutral-400">Duplicate</Button>
          <Button onClick={handleToggleInList} variant="secondary">{inList ? "Remove from List" : "Add to List"}</Button>
          <Button onClick={handleSave} className="hover:bg-neutral-600">Save</Button>
        </div>
      </div>
      <TabsContent value="times" className="w-full flex flex-col gap-4">
        <div className="flex justify-center items-center gap-2">
          <Button onClick={handleSetStartTime} className="bg-neutral-200 border border-neutral-300 hover:bg-neutral-300 text-neutral-950">Set Start Time</Button>
          <div className="flex flex-row items-center gap-2 bg-secondary p-4 rounded-md">
            <div><span className="font-bold">Start:</span> {formatTime(startTime)}</div> |
            <div><span className="font-bold">End:</span> {formatTime(endTime)}</div> |
            <div><span className="font-bold">Duration:</span> {formatTime(endTime - startTime)}</div>
          </div>
          <Button onClick={handleSetEndTime}  className="bg-neutral-200 border border-neutral-300 hover:bg-neutral-300 text-neutral-950">Set End Time</Button>
        </div>
        <div className="flex flex-row justify-between items-center gap-2">
          <Controls vimeoInstance={vimeoInstance} start={startTime} end={endTime} />
        </div>
      </TabsContent>
      <TabsContent value="summary" className="w-full">
        <div className="flex flex-col gap-2">
          <FormInput className="w-full">
            <Label className="text-sm font-bold">Summary</Label>
            <textarea
              placeholder="Summary"
              className="rounded-sm w-full md:text-lg border-0 focus-visible:border-transparent h-auto font-medium focus-visible:ring-4 focus-visible:ring-blue-200/35"
              value={summary}
              onChange={(e) => setSummary(e.target.value)}
            ></textarea>
          </FormInput>
					<div>
						<Button onClick={()=>toast.error("Not Implemented Yet")} className="bg-purple-50 text-purple-800 hover:bg-purple-100 hover:text-purple-900 ">
							Generate with Ai
						</Button>
					</div>
        </div>
      </TabsContent>
    </Tabs>
  </div >
}

function Controls({ vimeoInstance, start, end }: { vimeoInstance: Vimeo, start: number, end: number }) {
  async function rewind() {
    const _currentTime = await vimeoInstance.getCurrentTime();
    vimeoInstance.setCurrentTime(_currentTime - 5);
  }
  async function fastForward() {
    const _currentTime = await vimeoInstance.getCurrentTime();
    vimeoInstance.setCurrentTime(_currentTime + 5);
  }
  async function setToStart() {
    vimeoInstance.setCurrentTime(start);
  }
  async function setToEnd() {
    vimeoInstance.setCurrentTime(end);
  }
  async function handleScrub(e: FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    const scrubTo = formData.get('scrub-to');
    if (scrubTo?.toString().includes(':') || scrubTo?.toString().includes('.')) {
      const [m, s] = scrubTo.toString().includes(':') ? scrubTo.toString().split(':') : [scrubTo.toString().split('.')[0], scrubTo.toString().split('.')[1]];
      vimeoInstance.setCurrentTime(Number(m) * 60 + Number(s));
      return;
    } else {
      vimeoInstance.setCurrentTime(Number(formData.get('scrub-to')));
    }
  }

  return <div className="flex flex-row items-center gap-2 justify-center w-full">
    <Button onClick={setToStart} variant="ghost">
      <Rewind className="h-5 w-5" />
      Got to Start
    </Button>
    <Button onClick={rewind} variant="ghost">
      <Rewind className="h-5 w-5" />
      5s
    </Button>
    <div className="flex flex-row items-center justify-center gap-2 ">
      <form onSubmit={handleScrub} className="flex flex-row items-center gap-0 w-[250px] border border-neutral-400 rounded-md">
        <input type="text" name="scrub-to" id="scrub-to" className=" border-0 min-w-0" />
        <Button type="submit" variant="secondary">Set Time</Button>
      </form>
    </div>
    <Button onClick={fastForward} variant="ghost">
      5s
      <FastForward className="h-5 w-5" />
    </Button>
    <Button onClick={setToEnd} variant="ghost">
      Got to End
      <FastForward className="h-5 w-5" />
    </Button>
  </div>
}
