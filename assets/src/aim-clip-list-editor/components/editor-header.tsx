import { EllipsisVertical } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input";
import { AppDataDirty, AppLocation, AppState, AppStore, PostData } from "../store";
import { useAtom } from "jotai";
import { Button } from "@/components/ui/button";
import { Label } from "@radix-ui/react-label";
import { FormInput } from "@/components/ui/form-input";
import { useAPI } from '../hooks/useAPI';
import { useState } from 'react';
type EditorHeaderProps = {
  menuItems: {
    label: string;
    onClick: () => void;
  }[];
};
export function EditorHeader({ menuItems }: EditorHeaderProps) {
  const api = useAPI()
  const [isDirty, setIsDirty] = useAtom(AppDataDirty);
  const [appStore, setAppStore] = useAtom(AppStore);
  const [, setPostData] = useAtom(PostData);
  const [post] = useAtom(PostData);
  const [, setAppState] = useAtom(AppState);
  const [location, setLocation] = useAtom(AppLocation);

  async function save() {

    const data = await api.post('/save', appStore).catch((e) => {
      setAppState({
        status: 'error',
        error: e.message,
      });
    });
    if (data.code) {
      setAppState({
        status: 'error',
        error: data.message,
      });
      return;
    }
    if (
      data.postId &&
      data.post &&
      data.post.title &&
      data.items
    ) {
      setAppStore(data)
      setAppState({
        status: 'success',
      });
      setIsDirty(false);
    } else {
      setAppState({
        status: 'error',
        error: 'Something went wrong, recieved invalid data form server',
      });

    }
  }

  function updateTitle(e: React.ChangeEvent<HTMLInputElement>) {
    setPostData({
      data: {
        ...post,
        title: e.target.value,
      }
    })
  }

  return <div className="flex flex-col gap-4 w-full">
    <div className="flex flex-row items-center justify-between w-full">
      <div className="flex flex-row items-start gap-10 w-full">
        <FormInput className="w-full">
          <Label className="text-sm font-bold">Title</Label>
          <Input
            value={post.title} id="title"
            placeholder="Title"
            className="w-full md:text-3xl rounded-none border-0 focus-visible:border-transparent focus-visible:ring-blue-200/35 h-auto font-bold"
            onChange={updateTitle}
          />
        </FormInput>
        <div>
          <div className="flex flex-row items-center gap-2">

            <Button onClick={save} className="rounded-noneu disabled:opacity-35" disabled={!isDirty} >
              Save
            </Button>
            <Menu menuItems={menuItems} />
          </div>
        </div>
      </div>
    </div>
    <div className="flex flex-row items-center justify-start w-full">
      <NavItem isActive={location==='videos'} label="Videos" onClick={() => setLocation('videos')} />
      <NavItem isActive={location==='email-campaign'} label="Email Campaign" onClick={() => setLocation('email-campaign')} />
    </div>
  </div>
}

function NavItem({ isActive, label, onClick }: { isActive: boolean, label: string, onClick: () => void }) {
  return <Button variant={isActive ? "secondary":"ghost"} onClick={onClick}>
    {label}
  </Button>
}

function Menu({ menuItems }: { menuItems: EditorHeaderProps['menuItems'] }) {
  const [isopen, setIsOpen] = useState(false);
  return <DropdownMenu open={isopen} onOpenChange={(s) => setIsOpen(s)}>
    <DropdownMenuTrigger className={`hover:bg-neutral-100 hover:border-neutral-300 active:text-white p-2 rounded-full ${isopen && "bg-blue-50 text-blue-800 border-solid"}`}>
      <EllipsisVertical className="h-5 w-5" />
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" alignOffset={10} sideOffset={20} className="border-neutral-200 border rounded-md bg-white p-2 shadow-sm">
      <DropdownMenuLabel>Actions</DropdownMenuLabel>
      <DropdownMenuSeparator />
      {menuItems.map((item, index) => (
        <DropdownMenuItem key={index} onClick={item.onClick}>
          {item.label}
        </DropdownMenuItem>
      ))}
    </DropdownMenuContent>
  </DropdownMenu>
}
