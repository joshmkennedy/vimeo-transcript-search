import Vimeo from "@vimeo/player";
import { Button } from "@/components/ui/button"
import { Form, FormField, FormItem, FormLabel, FormControl, FormDescription, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { zodResolver } from "@hookform/resolvers/zod"
import React from "react"
import { useForm } from "react-hook-form"
import z from "zod"
import toast from "react-hot-toast";
import { formatTime } from "@/lib/format-time";

const formSchema = z.object({
  query: z.string(),
})

type SearchResultType = {
  start_time: number,
  end_time?: number,
  title: string,
  vimeoId: string,
  iframeSrcUrl: string,
  thumbnail?: string,
  score: number,
}

export function Search() {
  const [results, setResults] = React.useState<SearchResultType[]>([]);
  const [selectedResult, setSelectedResult] = React.useState<undefined | SearchResultType>(undefined);
  async function onSubmit(data: z.infer<typeof formSchema>) {
    const results = await toast.promise(async () => {
      //@ts-ignore - its there I just dont care to type it
      const results = await fetch(`${window.vtsAdmin.apiUrl}/search-transcription-embeds?query=${encodeURIComponent(data.query)}`, {
        method: "GET",
        credentials: "include",
        mode: "cors",
        headers: {
          "Content-Type": "application/json",
          //@ts-ignore - its there I just dont care to type it
          "X-WP-Nonce": window.vtsAdmin.nonce,
        },
      })
        .then(res => res.json())
      return results as SearchResultType[];
    }, {
      loading: "Searching...",
      error: (err) => `Error searching, ${err}`,
      success: "Successfully searched",
    });
    setResults(results)
  }

  function selectResult(vimeoId: string, start_time: number) {
    setSelectedResult(results.find(r => r.vimeoId == vimeoId && r.start_time == start_time))
  }

  return <div className="max-w-[2000px] bg-white p-4 lg:p-8 xl:p-12 xl:py-8 rounded-lg shadow-sm border-neutral-200 border flex flex-col gap-6 items-start">
    <header>
      <h2 className="m-0 text-3xl">Search Transcripts for Clips</h2>
    </header>
    <SearchForm onSubmit={onSubmit} />
    <div className="w-full flex flex-col gap-4 lg:flex-row">
      <div className="flex-1 shrink-0 lg:w-1/2 lg:max-w-[720px] sm:min-w-[350px] ">
        <h3 className="m-0 text-lg">Results</h3>
        <SearchResultList results={results} selectResult={selectResult} />

      </div>

      <div className="flex flex-col gap-4 flex-1 shrink-1 min-w-2">

        <PreviewVimeoVideo selectedResult={selectedResult} />

        <PageListWithVideo videoId={selectedResult?.vimeoId} timeStart={selectedResult?.start_time} />
      </div>
    </div>
  </div>
}

function SearchForm({ onSubmit }: { onSubmit: (data: z.infer<typeof formSchema>) => void }) {
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      query: "",
    },
  });
  return <Form {...form}>
    <form
      onSubmit={form.handleSubmit(onSubmit)}
      className="flex flex-col gap-4 items-start sm:flex-row sm:items-end w-full min-w-full"
    >
      <FormField
        name="query"
        render={({ field }) => (
          <FormItem className="w-full flex-1 ">
            <FormLabel className="text-lg">What would you like to find</FormLabel>
            <FormMessage />
            <FormDescription className="my-1 text-xs">Search transcripts for clips</FormDescription>
            <FormControl>
              <Input placeholder="Search transcripts" className="w-full" {...field} />
            </FormControl>
          </FormItem>
        )}
      />
      <Button type="submit">Search</Button>
    </form>
  </Form>
}

function SearchResultList(
  {
    results,
    selectResult
  }: {
    results: SearchResultType[]
    selectResult: (vimeoId: string, start_time: number) => void
  }
) {

  return <div className="min-h-96 flex flex-col gap-2 ">
    {results.length == 0
      ? <p className="text-muted-foreground">No results yet...</p>
      : results.map((result, i) => <SearchResult key={i} {...result} selectResult={selectResult} />)}
  </div>
}

function SearchResult({
  score,
  title,
  vimeoId,
  end_time,
  start_time,
  thumbnail,
  selectResult,
}: SearchResultType & { selectResult: (vimeoId: string, start_time: number) => void }) {
  return <div
    className="flex flex-col gap-2 sm:flex-row bg-neutral-100 p-2 rounded-md hover:bg-neutral-200"
    onClick={() => selectResult(vimeoId, start_time)}
  >
    <div className="w-fit">
      <div className=" bg-neutral-200 rounded-sm overflow-hidden w-fit">
        {thumbnail && <img src={thumbnail} className="object-cover w-auto sm:h-[84px] aspect-video" />}
      </div>
    </div>
    <div className="flex-1">
      <h3 className="m-0 text-base">{title}</h3>
      <p className="text-sm m-0">{score}% match</p>
      <p className="text-xs">
        {formatTime(start_time)} - {end_time ? formatTime(end_time) : 'end'}
      </p>
    </div>
  </div>
}



function PreviewVimeoVideo({ selectedResult }: { selectedResult: SearchResultType | undefined }) {

  React.useEffect(() => {
    console.log(selectedResult?.vimeoId, selectedResult?.start_time);
    if (selectedResult) {
      const player = new Vimeo(document.getElementById('preview-player')!);
      setTimeout(() => {
        player.setCurrentTime(selectedResult.start_time);
      }, 1000)
    }
  }, [selectedResult?.vimeoId, selectedResult?.start_time])

  return <div className="">
    {selectedResult
      ? <iframe id="preview-player" className="aspect-video w-full" src={`https://player.vimeo.com/video/${selectedResult?.vimeoId}`} allowFullScreen></iframe>
      : null}
  </div>
}

async function fetchPagesWithVideo(videoId: string) {
  const res = await fetch(`${window.vtsAdmin.apiUrl}/pages-with-video?videoId=${videoId}`, {
    method: "GET",
    credentials: "include",
    mode: "cors",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": window.vtsAdmin.nonce,
    },
  }).then(res => res.json());
  if (res.error) {
    throw new Error(res.error);
  }
  return res.records;
}

function PageListWithVideo({ videoId, timeStart }: { videoId: string | undefined, timeStart: number | undefined }) {
  const [pages, setPages] = React.useState<string[]>([]);
  React.useEffect(() => {
    if (videoId) {
      fetchPagesWithVideo(videoId).then(pages => {
        setPages(pages);
      }).catch(e => {
        console.error(e);
      })
    }
  }, [videoId])
  if (!pages.length || !timeStart) {
    return null;
  }

  return <ul className="flex flex-col gap-1">
    {pages.map(page => {
      const url = new URL(page)
      url.searchParams.set("ts", timeStart?.toString());
      url.searchParams.set("skip-to-clip-video", videoId ?? "");

      return <li key={url.toString()} className="flex gap-2 justify-between items-baseline bg-neutral-100 p-2 rounded-md overflow-hidden">
        <Button onClick={async () => { await navigator.clipboard.writeText(url.toString()) }}>Copy</Button>
        <a className="text-base text-blue-900 hover:underline" target="_blank" href={url.toString()}>{url.toString()}</a>
      </li>
    })}
  </ul>
}
