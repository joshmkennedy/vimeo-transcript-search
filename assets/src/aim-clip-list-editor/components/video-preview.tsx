import { useEffect } from "react";
import type { AiVimeoResult } from "../types";
import Vimeo from "@vimeo/player";

export function PreviewVimeoVideo({ video, videoRef , setVimeoInstance}: { video: AiVimeoResult | undefined, videoRef: React.RefObject<HTMLIFrameElement | null>, setVimeoInstance: (instance: Vimeo) => void }) {
  useEffect(() => {
    if (video) {
      if (!videoRef.current) {
        console.log("no video ref was given yet");
        return;
      }

      const player = new Vimeo(videoRef.current);
      setVimeoInstance(player);
      setTimeout(() => {
        player.setCurrentTime(video.start);
      }, 500)
    }

  }, [video?.vimeoId, video?.start])

  return <div className="">
    {video
      ? <iframe id="preview-player" ref={videoRef} className="aspect-video w-full" src={`https://player.vimeo.com/video/${video?.vimeoId}`} allowFullScreen></iframe>
      : <div className="w-full h-full flex flex-col items-center justify-center bg-neutral-100 text-center text-neutral-500 aspect-video font-bold text-xl">
        Click on a clip to preview and edit clip
    </div>}
  </div>
}
