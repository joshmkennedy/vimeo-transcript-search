import { VideoTypeCategoryMapper, type VideoType } from "@/configuration/video-types";

function getVideoTypeLabel(videoType: VideoType) {
  return VideoTypeCategoryMapper[videoType].singleLabel;
}
function getVideoTypeColorConfig(videoType: VideoType) {
  return colorConfig[VideoTypeCategoryMapper[videoType].key];
}

const colorConfig = {
  "featured": {
    ['--video-type-badge-color']: "var(--brand-c-primary)",
    ['--video-type-badge-text-color']: "var(--brand-c-light)",
  } as React.CSSProperties,
  "supporting": {
    ['--video-type-badge-color']: "var(--slate-100)",
    ['--video-type-badge-text-color']: "var(--brand-c-secondary)",
  } as React.CSSProperties,
}

export function VideoTypeBadge({ videoType }: { videoType: VideoType }) {

  return <div className="aim-vimeo-plugin--video-type-badge" style={getVideoTypeColorConfig(videoType)}>
    {getVideoTypeLabel(videoType)}
  </div>
}
