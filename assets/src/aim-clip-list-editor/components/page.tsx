import { useAtom } from "jotai";
import { AppLocation } from "../store";

export function Page({ slug, children }: { slug: string, children: React.ReactNode }) {
  const [location] = useAtom(AppLocation);
  if (location !== slug) {
    return null;
  }
  return children;
}
