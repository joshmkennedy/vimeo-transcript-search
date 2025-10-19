import { createRoot } from 'react-dom/client';
import "./app.css";
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import toast, { Toaster } from 'react-hot-toast';
import { AimVideoSelection } from './video-selector';
import type { PlayerApi } from '../aim-clip-player';
import { ResourceList } from './resources';
import type { VideoType } from '@/configuration/video-types';

export type Video = {
  vimeoId: string;
  start: number;
  end: number;
  clipId: string;
  summary: string;
  name: string;
  image_url: string;
	video_type: VideoType;
};

export type Resource = {
  label: string;
  link: string;
};

export type AimClipPlayerProps = {
  intro: string;
  playerApi: PlayerApi;
  videos: Video[];
  selectedVideo: string;
  resources: Resource[];
};

function App({ playerApi, videos, resources, selectedVideo: defaultSelectedVideo}: AimClipPlayerProps) {
  const [selectedClipId, setSelectedClipId] = useState(defaultSelectedVideo);
  const [finishedVideos, setFinishedVideos] = useState<Video["clipId"][]>([]);
  const selectedVideo = useMemo(() => (videos.length ? videos.find(v => v.clipId === selectedClipId) : videos[0]), [videos, selectedClipId]);
  
  const addVideoCompleted = useCallback((clipId: string) => {
    setFinishedVideos((f) => [...f, clipId]);
  }, [setFinishedVideos]);

  function setVideo(id:string){
    setSelectedClipId(id);
    const video = videos.find(v => v.clipId === id);
    if(video){
      playerApi.setCurrentVideo(video);
    }
  }

  const onVideoFinished = useCallback((e: any)=>{
    if(finishedVideos.includes(e.detail.clipId)){
      return;
    } 
    addVideoCompleted(e.detail.clipId);
    watchedVideoNotification();
  }, [finishedVideos])

  useEffect(()=>{
    playerApi.getPlayerEl().addEventListener("finishedVideo", onVideoFinished);
    return ()=>{
      playerApi.getPlayerEl().removeEventListener("finishedVideo", onVideoFinished);
    }
  },[onVideoFinished])

  useEffect(() => {
    if(!selectedVideo){
      return;
    }
    playerApi.setCurrentVideo(selectedVideo);
  }, [])
  return (
    <div>
      {selectedVideo && <p>{selectedVideo.summary}</p>}
      <div className="">
        {selectedVideo && <AimVideoSelection selectedVideo={selectedVideo} setSelectedVideo={setVideo} videos={videos} />}
        <ResourceList resources={resources} />
      </div>
      <Toaster
        containerStyle={{ top: '100px' }}
        toastOptions={{
          className: 'toast-toast',
        }}
      />
    </div>
  );
}

export function mountReactApp(el: HTMLElement, props: AimClipPlayerProps) {
  const root = createRoot(el);
  root.render(<App {...props} />);
}

const toastMarkup = {
  heading: `You have completed the currated clip!`,
  message: `Keep watching to go deeper and learn more about this topic.`,
}
function watchedVideoNotification() {
  toast(() => {
    return (<div className="toast-content">
      <div className="toast-heading">{toastMarkup.heading}</div>
      <div className="toast-text">{toastMarkup.message}</div>
    </div>)
  }, {
    icon: "🎉",
    duration: 5000,
  });
}
