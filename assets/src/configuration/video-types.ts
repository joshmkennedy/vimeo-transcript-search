export type VideoType = "lecture" | "secondary-lecture" | "lab";

export const VideoTypeLabelMapper = {
  "lecture": "Lecture",
  "secondary-lecture": "Secondary Lecture",
  "lab": "Lab",
}

const sectionConfig = {
  "featured": {
    label: "Featured Lesson",
    singleLabel: "Featured Lesson",
    key: "featured",
    description: "Core concepts and initial set up to get you started",
  },
  "supporting": {
    label: "Supporting Lessons",
    singleLabel: "Supporting Lesson",
    key: "supporting",
    description: "Go further with the these concepts and how to use them",
  },
} as const

export const VideoTypeCategoryMapper = {
  "lecture": sectionConfig["featured"],
  "secondary-lecture": sectionConfig["supporting"],
  "lab": sectionConfig["supporting"],
} as const;

type VideoCategoryMapConfig<Video extends { video_type: VideoType }> = Record<"supporting" | "featured", {
  label: string;
  key: string;
  description: string;
  videos: Video[];
}
>

export function buildCategoryConfig<Video extends { video_type: VideoType }>(videos: Video[]) {
  const categoryObjConfig = videos.reduce((categories, video) => {
    const category = VideoTypeCategoryMapper[video.video_type];
    if (!(category.key in categories)) {
      categories[category.key] = {
        ...category,
        videos: [video],
      };
    } else{
      categories[category.key].videos.push(video);
    }
    return categories;
  }, {} as VideoCategoryMapConfig<Video>);
  return categoryObjConfig;
}
