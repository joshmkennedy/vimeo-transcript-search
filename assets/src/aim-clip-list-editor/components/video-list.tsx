import { formatTime } from "@/lib/format-time"
import type { AiVimeoResult, ClipListMetaItem } from "../types"
import { Tabs, TabsContent, TabsList, VtsTabsTrigger } from "@/components/ui/tabs"
import { CountLabel } from "@/components/ui/vts-badge"

export function TabbedVideoList(
  {
    results,
    selectedResult,
    selectResult
  }: {
    results: readonly AiVimeoResult[]
    selectedResult: string
    selectResult: (clip_id: string) => void
  }
) {
  return <div className="flex-1 shrink-0 lg:max-w-[720px] sm:min-w-[350px] max-h-[70vh] overflow-y-scroll">
    <Tabs defaultValue="not-published" className="w-full lg:pr-12">
      <TabsList className="sticky top-0 bg-white w-full">
        <VtsTabsTrigger value="not-published" >
          In-active Videos
          <CountLabel count={results.filter(v => !inPublishList(v)).length} variant="secondary" />
        </VtsTabsTrigger>
        <VtsTabsTrigger value="published">
          Active Videos
          <CountLabel count={results.filter(inPublishList).length} />
        </VtsTabsTrigger>
      </TabsList>
      <TabsContent value="not-published" className="w-full">
        <div className="min-h-96 h-full flex flex-col gap-2 p-3">
          {results.filter(v => !inPublishList(v)).length == 0
            ? <p className="text-muted-foreground">No results yet...</p>
            : results.filter(v => !inPublishList(v)).map((result, i) => result.name ? <VideoListItem isSelected={result.clip_id === selectedResult} key={i} idx={result.clip_id} {...result} selectResult={selectResult} /> : null)}
        </div>
      </TabsContent>
      <TabsContent value="published">
        <div className="min-h-96 h-full flex flex-col gap-2 p-3">
          {results.filter(inPublishList).length == 0
            ? <p className="text-muted-foreground">No results yet...</p>
            : results.filter(inPublishList).map((result, i) => result.name ? <VideoListItem isSelected={result.clip_id === selectedResult} key={i} idx={result.clip_id} {...result} selectResult={selectResult} /> : null)}
        </div>
      </TabsContent>
    </Tabs>
  </div>

}


function VideoListItem({ start, end, name, pictures, selectResult, idx, isSelected, video_type }: Omit<AiVimeoResult, 'player_embed_url'> & { idx: string, selectResult: (idx: string) => void, isSelected: boolean }) {
  return <div
    className={
      "flex flex-col gap-2 sm:flex-row bg-neutral-100 p-2 rounded-md hover:bg-neutral-200"
      + (isSelected ? " ring-blue-300/35 ring-4 bg-neutral-50" : "")
    }
    onClick={() => selectResult(idx)}
  >
    <div className="flex-1 max-w-[200px]">
      <img className="w-full rounded-md" src={pictures.base_link} alt={name} />
    </div>
    <div className="flex-1 p-4">
			<div>{video_type}</div>
      <h3
        className={"m-0 text-base font-bold text-neutral-600" + (isSelected ? " text-neutral-900" : "")}
      >
        {name}
      </h3>
      <p className="text-xs">
        {formatTime(start)} - {end ? formatTime(end) : 'end'}
      </p>
    </div>
  </div>
}


export function VideoList({ videos, onVideoSelect }: { videos: AiVimeoResult[], onVideoSelect: (clip_id: string) => void }) {
  return <div className="min-h-96 h-full flex flex-col gap-2 p-3  lg:max-w-[720px] sm:min-w-[350px] max-h-[70vh] overflow-y-scroll">
    {videos.length == 0
      ? <p className="text-muted-foreground">No results yet...</p>
      : videos.map((result, i) => result.name ? <VideoListItem isSelected={false} key={i} idx={result.clip_id} {...result} selectResult={onVideoSelect} /> : null)}
  </div>

}


export function inPublishList(item: ClipListMetaItem) {
  return item.in_list;
}
