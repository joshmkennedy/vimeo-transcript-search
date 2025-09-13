import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Category, FormId } from "../store";
import { Input } from "@/components/ui/input";
import { FormInput } from "@/components/ui/form-input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { useAtom } from "jotai";

export function MetaEditor({ categories, setShowing, isShowing }: { categories: Record<number, string>, setShowing: (s: boolean) => void, isShowing: boolean }) {
  const [formId, setFormId] = useAtom(FormId);
  const [category, setCategory] = useAtom(Category);

  function handleSave(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    if (formData.has('formId')) {
      setFormId({ data: parseInt(formData.get('formId')?.toString() ?? '0') })
    }
    if (formData.has('category')) {
      setCategory({ data: parseInt(formData.get('category')?.toString() ?? '0') })
    }
  }
  console.log(formId);
  if (!isShowing) {
    return null;
  }
  return (
    <Dialog
      open={isShowing}
      onOpenChange={(s) => {
        setShowing(s);
        setTimeout(() => {
          document.querySelector('body')?.style.removeProperty('pointer-events');
        }, 100);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Clip List Meta</DialogTitle>
          <DialogDescription>
            <p>These options effect how the video is displayed in the email.</p>
          </DialogDescription>
        </DialogHeader>
        <div>
          <form onSubmit={handleSave} className="flex flex-col gap-4">
            <FormInput>
              <Label className="text-sm font-bold">Form Id</Label>
              <Input
                defaultValue={formId}
                id="formId"
                name="formId"
                placeholder="Form Id"
                className="w-full md:text-3xl rounded-none border-0 focus-visible:border-transparent focus-visible:ring-blue-200/35 h-auto font-bold"
                type="number"
              />
            </FormInput>
            <FormInput>
              <Label className="text-sm font-bold">Category</Label>
              <CategorySelect categories={categories} category={category} />
            </FormInput>
            <Button variant="secondary" type="submit">Save</Button>
          </form>
        </div>
      </DialogContent>
    </Dialog>
  );
}


function CategorySelect({ categories, category }: { category: number, categories: Record<number, string> }) {
  const options = Object.keys(categories).map(v => String(v));

  return <Select name="category" defaultValue={category?.toString() ?? ""} >
    <SelectTrigger className="w-[180px]">
      <SelectValue placeholder="Selecte a Category" />
    </SelectTrigger>
    <SelectContent>
      {options.map(option => <SelectItem key={option} value={option}>{categories[parseInt(option)]}</SelectItem>)}
    </SelectContent>
  </Select>
}
