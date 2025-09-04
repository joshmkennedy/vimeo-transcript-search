import { Badge } from "./badge";

type BadgeVariant = "outline" | "destructive" | "secondary" | "default";
export function CountLabel({ count, variant = "default" }: { count: number, variant?: BadgeVariant }) {
  return <Badge className="h-5 min-w-5 rounded-full px-1 font-medium tabular-nums" variant={variant}>
    {count}
  </Badge>
}
