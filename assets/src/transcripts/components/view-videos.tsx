import { useEffect, useRef, useState } from "react";
import { formatTime } from "@/lib/format-time";
import { Button } from "@/components/ui/button";
import React from "react";
import Vimeo from "@vimeo/player";
import toast from "react-hot-toast";

type AiVimeoResult = {
  start: number,
  end: number,
  vimeoId: string,
  name: string,
  uri: string,
  pictures: {
    base_link: string,
  },
  player_embed_url: string,
};

export function ViewVideos() {
  const [videos, setVideos] = useState<AiVimeoResult[]>([]);
  const [selectedResult, setSelectedResult] = useState<number>(0);
  const [edits, setEdits] = useState<AiVimeoResult | undefined>(undefined);
	const [pickedVideos, setPickedVideos] = useState<AiVimeoResult[]>([]);
  const previewRef = useRef<HTMLIFrameElement>(null);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const videos = params.get("videos");
    if (!videos) {
      toast.error("No videos found");
      return;
    }
    let parsed
    try {
      parsed = JSON.parse(videos) as AiVimeoResult[];
      console.log(parsed);
    } catch (e) {
      toast.error("Could not parse videos");
      return;
    }
    if (parsed) {
      toast.promise(async () => fetch(`${window.vtsAdmin.apiUrl}/vid-info`, {
        method: "POST",
        credentials: "include",
        mode: "cors",
        headers: {
          "Content-Type": "application/json",
          //@ts-ignore - its there I just dont care to type it
          "X-WP-Nonce": window.vtsAdmin.nonce,
        },
        body: JSON.stringify({
          videos: parsed,
        }),
      })
        .then(res => res.json())
        .then(data => {
          setVideos(data);
        })
        .catch(e => {
          toast.error(e.message);
        }), {
        loading: "Loading videos...",
        success: "Videos loaded",
        error: "Error loading videos",
      });
    }
  }, [])

  if (!videos.length) {
    return <div>No videos found</div>
  }
  return <div className="max-w-[2000px] bg-white p-4 lg:p-8 xl:p-12 xl:py-8 rounded-lg shadow-sm border-neutral-200 border flex flex-col gap-6 items-start max-h-[90vh]">
    <header>
      <h2 className="m-0 text-3xl">Review Ai Selected Videos</h2>
    </header>
    <div className="w-full flex flex-col gap-4 lg:flex-row flex-1 max-h-full">
      <div className="flex-1 shrink-0 lg:w-1/2 lg:max-w-[720px] sm:min-w-[350px] max-h-[70vh]">
        <h3 className="m-0 text-lg sticky top-0 bg-white">Results</h3>
        <VideoList results={videos} selectResult={setSelectedResult} selectedResult={selectedResult} />
      </div>
      <div className="flex flex-col gap-4 flex-1 shrink-1 min-w-2">
        <PreviewVimeoVideo video={videos[selectedResult]} videoRef={previewRef} />
        <TimeStampEditor selectedResult={videos[selectedResult]} videoRef={previewRef} applyEdits={() => { }} />
				<SelectedVideos pickedVideos={pickedVideos} />
      </div>
    </div>
  </div>
}


function TimeStampEditor({ selectedResult, videoRef, applyEdits }: { selectedResult: AiVimeoResult | undefined, videoRef: React.RefObject<HTMLIFrameElement | null>, applyEdits: (edits: Partial<AiVimeoResult>) => void }) {
  const [edits, setEdits] = useState<Partial<AiVimeoResult> | undefined>(undefined);

  function handleScrub(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    if (!videoRef.current) return;
    const player = new Vimeo(videoRef.current);
    const scrubTo = formData.get('scrub-to');
    if (scrubTo?.toString().includes(':') || scrubTo?.toString().includes('.')) {
      const [m, s] = scrubTo.toString().includes(':') ? scrubTo.toString().split(':') : [scrubTo.toString().split('.')[0], scrubTo.toString().split('.')[1]];
      player.setCurrentTime(Number(m) * 60 + Number(s));
      return;
    } else {
      player.setCurrentTime(Number(formData.get('scrub-to')));
    }
  }
  async function handleRewind() {
    if (!videoRef.current) {
      console.log("no video ref was given yet");
      return;
    }
    const player = new Vimeo(videoRef.current);
    const _currentTime = await player.getCurrentTime();
    player.setCurrentTime(_currentTime - 5);
  }
  async function handleFastForward() {
    if (!videoRef.current) {
      console.log("no video ref was given yet");
      return;
    }
    const player = new Vimeo(videoRef.current);
    const _currentTime = await player.getCurrentTime();
    player.setCurrentTime(_currentTime + 5);
  }
  async function handleTogglePlayState() {
    if (!videoRef.current) {
      console.log("no video ref was given yet");
      return;
    }
    const player = new Vimeo(videoRef.current);
    const _playState = await player.getPaused();
    if (!_playState) {
      await player.pause();
    } else {
      await player.play();
    }
  }
  return <div>
    <div>
      <Button onClick={handleRewind}>Rewind 5s</Button>
      <form onSubmit={handleScrub}>
        <input type="text" name="scrub-to" id="scrub-to" />
        <Button type="submit">Set</Button>
      </form>
      <Button onClick={handleFastForward}>Fast Forward 5s</Button>
      <Button onClick={handleTogglePlayState}>Toggle PlayState</Button>
    </div>
    <div>
      <p>Update Video Start and End times to the current time</p>
      <Button>Set Start Time</Button>
      <Button>Set End Time</Button>
    </div>
    {edits && (edits.start !== selectedResult?.start || edits.end !== selectedResult?.end)
      ? <div className="p-2">
        <p>Video has been edited</p>
        <p>Start: {formatTime(edits.start ?? selectedResult?.start ?? 0)}</p>
        <p>End: {formatTime(edits.end ?? selectedResult?.end ?? 0)}</p>
        <Button onClick={() => applyEdits(edits)}>Save</Button>
        <Button onClick={() => setEdits(undefined)}>Cancel</Button>
      </div>
      : null
    }
		<Button onClick={()=>{}}>Add Video To List</Button>
  </div>

}

function VideoList(
  {
    results,
    selectedResult,
    selectResult
  }: {
    results: AiVimeoResult[]
    selectedResult: number
    selectResult: (idx: number) => void
  }
) {

  return <div className="min-h-96 h-full overflow-y-scroll flex flex-col gap-2 p-3">
    {results.length == 0
      ? <p className="text-muted-foreground">No results yet...</p>
      : results.map((result, i) => result.name ? <VideoListItem isSelected={i === selectedResult} key={i} idx={i} {...result} selectResult={selectResult} /> : null)}
  </div>
}

function VideoListItem({ start, end, name, pictures, selectResult, idx, isSelected }: Omit<AiVimeoResult, 'player_embed_url'> & { idx: number, selectResult: (idx: number) => void, isSelected: boolean }) {
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

function PreviewVimeoVideo({ video, videoRef }: { video: AiVimeoResult | undefined, videoRef: React.RefObject<HTMLIFrameElement | null> }) {
  React.useEffect(() => {
    if (video) {
      if (!videoRef.current) {
        console.log("no video ref was given yet");
        return;
      }

      const player = new Vimeo(videoRef.current);
      setTimeout(() => {
        player.setCurrentTime(video.start);
      }, 500)
    }

  }, [video?.vimeoId, video?.start])

  return <div className="">
    {video
      ? <iframe id="preview-player" ref={videoRef} className="aspect-video w-full" src={`https://player.vimeo.com/video/${video?.vimeoId}`} allowFullScreen></iframe>
      : null}
  </div>
}
function SelectedVideos({ pickedVideos }: { pickedVideos: AiVimeoResult[] }) {
	return <div className="flex flex-col gap-4 flex-1 shrink-1 min-w-2 mt-10">
		<h3 className="m-0 text-lg sticky top-0 bg-white mb-0">Picked Videos</h3>
		<VideoList results={pickedVideos} selectResult={() => { }} selectedResult={0} />
	</div>
}
