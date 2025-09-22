import type { Video } from "./app";
import { formatTime } from "@/lib/format-time";

export function AimVideoSelection({ selectedVideo, setSelectedVideo, videos }: { selectedVideo: Video, setSelectedVideo: (id: string) => void, videos: Video[] }) {
  return <div>
    <div className="aim-video-selection-list">
      {
        videos.map(video => <div className="aim-video-selection-item" key={video.clipId} onClick={() => setSelectedVideo(video.clipId)}>
          <div className="aim-video-selection-item-image">
            <img src={video.image_url} alt={video.name} />
          </div>
          <div className="aim-video-selection-item-text">
            <div className="aim-video-selection-item-text-name">From {video.name}</div>
          </div>
          <div className="aim-video-selection-item-times">
            <div className="aim-video-selection-item-times-duration">Duration: <span className="time">{formatTime(video.end - video.start)}</span></div>
            <div className="aim-video-selection-item-times-timestamps"><span className="time">{formatTime(video.start)} - {formatTime(video.end)}</span></div>
          </div>
          <div className="aim-video-selection-item-footer">
            <button className="aim-video-selection-item-footer-button">Watch</button>
          </div>
        </div>)
      }
    </div>
  </div>
}
