import { buildCategoryConfig } from "@/configuration/video-types";
import type { Video } from "../types";
import { formatTime } from "@/lib/format-time";
import { getAbovePlayerDomElement } from "./above-the-player";

function slideUpToVideo(){
	const el = getAbovePlayerDomElement();
	el.scrollIntoView({ behavior: "smooth", block: "start"});
}

export function AimVideoSelection({ selectedVideo, setSelectedVideo, videos }: { selectedVideo: Video, setSelectedVideo: (id: string) => void, videos: Video[] }) {

  const config = buildCategoryConfig<Video>(videos);

	function onSelectVideo(clipId: string){
		setSelectedVideo(clipId);
		slideUpToVideo();
	}

  return <div className="aim-video-selection section">
    <header className="section-header">
      <h3 className="video-selector-title">Videos</h3>
      <p>Select a video to watch</p>
    </header>
    {
      Object.entries(config).map(([key, section]) => {
        return <div key={key} className="video-selector-video-type-section">
					<header className="video-section__header">
						<h4>{section.label}</h4>
						<p>{section.description}</p>
					</header>
          <div className="aim-video-selection-list">
            {
              section.videos.map(video => <div
                className={"aim-video-selection-item hoverable " + (video.clipId === selectedVideo.clipId ? "aim-video-selection-item-selected" : "")}

                key={video.clipId}
                onClick={() => onSelectVideo(video.clipId)}
              >
                {selectedVideo.clipId === video.clipId ?
                  <div className="aim-video-selection-item-selected-indicator">
                    selected
                  </div>
                  : null}
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
                  {
                    video.clipId !== selectedVideo.clipId ?
                      <button className="aim-video-selection-item-footer-button">
                        Watch Clip
                      </button>
                      : null}
                </div>
              </div>)
            }
          </div>
        </div>
      })}
  </div>
}
