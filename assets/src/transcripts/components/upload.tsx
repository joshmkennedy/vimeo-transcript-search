import Vimeo from "@vimeo/player";
import { Button } from "@/components/ui/button";
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import z from "zod";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Input } from "@/components/ui/input";
import React from "react";
import toast from "react-hot-toast";

const baseURL = "https://jp.test/wp-json/vts/v1";

const formSchema = z.object({
  vimeoUrl: z.url({ error: "This is required so we know what id the transcript is for" }),
  transcriptFile: z.instanceof(FileList, { error: "This is required so we know what transcript to upload" }),
})

export function Upload() {

  function onSubmit(resetForm: () => void) {
    return async (data: z.infer<typeof formSchema>) => {
      let contents = await toast.promise<string>(async () => {
        const contents = await data?.transcriptFile?.[0]?.text();
        if (!contents) {
          throw new Error("No transcript file content");
        }
        return contents;
      }, {
        loading: "Validating content...",
        error: (err) => `Error validating content, ${err}`
      });

      const { title, videoId, transcript } = await toast.promise(async () => {
        const transcript = JSON.parse(contents);
        const div = document.createElement("div")
        div.setAttribute("id", 'temp')
        document.body.appendChild(div)
        const player = new Vimeo(div, {
          url: data.vimeoUrl,
        });

        const title = await player.getVideoTitle().catch(() => "private video");
        const videoId = await player.getVideoId().catch(() => /https:\/\/vimeo.com\/(\d+)/.exec(data.vimeoUrl)?.[1]);

        player.destroy();
        document.body.removeChild(div)
        return { title, videoId, transcript }
      }, { loading: "Extracting Vimeo Info", error: (err) => `Error extracting Vimeo info, ${err}` });


      const results = await toast.promise(
        async () => fetch(`${baseURL}/upload-transcript`, {
          method: "POST",
          credentials: "include",
          mode: "cors",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": window.vtsAdmin.nonce,
          },
          body: JSON.stringify({
            title,
            videoId,
            transcript,
          }),
        })
          .then(r => r.json()),
        {
          loading: `Creating embeddings for ${title}...`,
          error: (err) => `Error creating embeddings, ${err}`,
          success: `Successfully created embeddings for ${title}`,
        })

      if (results.status === "ok") {
        resetForm();
      }
    }
  }
  return <>
    <div className="max-w-3xl ">
      <UploadForm onSubmit={onSubmit} />
    </div>
  </>;
}

function UploadForm({ onSubmit: onSubmitProp }: { onSubmit: (resetForm: () => void) => (data: z.infer<typeof formSchema>) => void }) {
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      vimeoUrl: "",
      transcriptFile: undefined,
    },
  });

  // its a bit of a hack but it works
  const { reset } = form
  const fileRef = React.useRef<HTMLInputElement>(null);
  function resetForm() {
    reset();
    if (fileRef.current) {
      fileRef.current.value = "";
    }
  }

  return <Form {...form}>
    <form onSubmit={form.handleSubmit(onSubmitProp(resetForm))} className="bg-white p-4 lg:p-8 xl:p-12 xl:py-8 rounded-lg shadow-sm border-neutral-200 border flex flex-col gap-6 items-start">
      <div>
        <h3 className="m-0 text-3xl">Upload Transcripts</h3>
        <p className="mb-0">Upload transcripts from Vimeo videos to create embeddings for easy searching.</p>
      </div>
      <div className="border-b border-neutral-300 w-full" />
      <FormField
        name="vimeoUrl"
        render={({ field }) => (
          <FormItem className="w-full">
            <FormLabel>Vimeo Url</FormLabel>
            <FormControl>
              <Input placeholder="https://vimeo.com/123456" accept="application/json,.txt" className="w-full" {...field} />
            </FormControl>
            <FormDescription className="my-1">The url of the vimeo video you are uploading transcripts for</FormDescription>
            <FormMessage />
          </FormItem>
        )}
      />
      <FormField
        {...form.register("transcriptFile")}
        name="transcriptFile"
        render={({ field }) => (
          <FormItem>
            <FormLabel>Transcript File</FormLabel>
            <FormControl>
              <div>
                <Input
                  className="w-fit hover:border-blue-600 hover:text-blue-800"
                  type="file"
                  ref={fileRef}
                  onChange={(e) => {
                    field.onChange(e.target.files);
                  }}
                />
              </div>
            </FormControl>
            <FormDescription className="my-1">A .json file that contains the transcripts with timestamps. Each chunck should have a "content", and a "ts" property.</FormDescription>
            <FormMessage />
          </FormItem>
        )}
      />
      <Button type="submit">Upload</Button>
    </form>
  </Form>
}
