import type { Resource } from "./app"

export function ResourceList({ resources }: { resources: Resource[] }) {
  return <div className="aim-resource-list-section section">
    <header className="section-header">
      <h3 className="video-selector-title">Resources</h3>
      <p>Accompanying resources from the videos</p>
    </header>
    <div className="aim-resource-list">

      {resources.map(resource => {
        return <div key={resource.link}>
          <a
            className="hoverable"
            href={resource.link}
            target="_blank"
            rel="noreferrer"
          >
            <span dangerouslySetInnerHTML={{ __html: resource.label }} />
          </a>
        </div>
      })}
    </div>
  </div>
}
