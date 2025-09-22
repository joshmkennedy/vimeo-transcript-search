import type { Resource } from "./app"

export function ResourceList({ resources }: { resources: Resource[] }) {
  return <div className="aim-resource-list">
    {resources.map(resource => {
      return <div key={resource.link}>
        <a
          className=""
          href={resource.link}
          target="_blank"
          rel="noreferrer"
        >
          <span dangerouslySetInnerHTML={{ __html: resource.label }} />
        </a>
      </div>
    })}
    </div>
}
