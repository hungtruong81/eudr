import { cn } from "@/lib/utils";
import { VariantProps, cva } from "class-variance-authority";

const shellVariants = cva(
  "grid items-center gap-8 min-w-full overflow-x-auto",
  {
    variants: {
      variant: {
        default: "container px-4",
        sidebar: "w-full",
        centerd: "mx-auto mb-16 mt-20 max-w-md justify-center",
        markdown: "container max-w-3xl gap-0 py-8 md:py-10 lg:py-10",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

interface ShellProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof shellVariants> {
  as?: React.ElementType;
}

function Shell({
  className,
  as: Comp = "section",
  variant,
  ...props
}: ShellProps) {
  return (
    <div className="w-full overflow-x-auto">
      <Comp className={cn(shellVariants({ variant }), className)} {...props} />
    </div>
  );
}

export { Shell, shellVariants };
