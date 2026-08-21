"use client";

import { TooltipProvider } from "@/components/ui/tooltip";
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import {
  dataTableConfig,
  useFeatureFlagsStore,
} from "@/hooks/use-feature-flag";
import { cn } from "@/lib/utils";

export function FeatureFlagsToggle() {
  const { featureFlags, setFeatureFlags } = useFeatureFlagsStore();

  return (
    <TooltipProvider>
      <div className="w-full overflow-x-auto flex justify-end ">
        <ToggleGroup
          type="multiple"
          variant="outline"
          size="sm"
          value={featureFlags}
          onValueChange={setFeatureFlags}
          className="w-fit gap-0"
        >
          {dataTableConfig.featureFlags.map((flag, index) => (
            <Tooltip key={flag.value}>
              <ToggleGroupItem
                value={flag.value}
                className={cn(
                  "gap-2  whitespace-nowrap rounded-none px-3 text-xs data-[state=on]:bg-accent/70 data-[state=on]:hover:bg-accent/90",
                  {
                    "rounded-l-sm border-r-0": index === 0,
                    "rounded-r-sm":
                      index === dataTableConfig.featureFlags.length - 1,
                  }
                )}
                asChild
              >
                <TooltipTrigger>
                  <flag.icon className="size-3.5 shrink-0" aria-hidden="true" />
                  {flag.label}
                </TooltipTrigger>
              </ToggleGroupItem>
              <TooltipContent
                align="start"
                side="bottom"
                sideOffset={6}
                className="flex max-w-60 flex-col space-y-1.5 border bg-background py-2 font-semibold text-foreground"
              >
                <div>{flag.tooltipTitle}</div>
                <div className="text-muted-foreground text-xs">
                  {flag.tooltipDescription}
                </div>
              </TooltipContent>
            </Tooltip>
          ))}
        </ToggleGroup>
      </div>
    </TooltipProvider>
  );
}
