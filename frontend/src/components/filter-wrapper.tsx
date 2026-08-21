"use client";

import { useState } from "react";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import {
  Collapsible,
  CollapsibleTrigger,
  CollapsibleContent,
} from "@/components/ui/collapsible";
import { ChevronUp, ChevronDown, Filter } from "lucide-react";
import type { ReactNode } from "react";
import { useIsMobile } from "@/hooks/use-mobile";

type FilterWrapperProps = {
  children: ReactNode;
  onSubmit: () => void;
  onReset?: () => void;
};

export default function FilterWrapper({
  children,
  onSubmit,
  onReset,
}: FilterWrapperProps) {
  const isMobile = useIsMobile();
  const [open, setOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(false);

  const formContent = (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit();
        if (isMobile) setOpen(false);
      }}
      className="mb-2"
    >
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
        {children}
      </div>
      <div className="mt-2 flex flex-wrap gap-2 justify-end">
        <Button type="submit">Tìm kiếm</Button>
        {onReset && (
          <Button type="button" variant="outline" onClick={onReset}>
            Đặt lại
          </Button>
        )}
      </div>
    </form>
  );

  if (isMobile) {
    return (
      <div className="mb-4">
        <Sheet open={open} onOpenChange={setOpen}>
          <SheetTrigger asChild>
            <Button variant="outline" className="w-full">
              <Filter className="mr-2 h-4 w-4" />
              Bộ lọc
            </Button>
          </SheetTrigger>
          <SheetContent
            side="right"
            className="p-4 max-h-[100vh] overflow-y-auto"
          >
            <SheetHeader>
              <SheetTitle>Bộ lọc</SheetTitle>
            </SheetHeader>
            {formContent}
          </SheetContent>
        </Sheet>
      </div>
    );
  }

  return (
    <Collapsible
      open={!collapsed}
      onOpenChange={(v) => setCollapsed(!v)}
      className="mb-2"
      title="Bộ lọc"
    >
      <Card className="p-2 gap-1 py-2">
        <div className="flex items-center justify-end gap-1">
          <CollapsibleTrigger asChild>
            <Button
              variant="ghost"
              size="sm"
              className="flex items-center gap-1"
            >
              {collapsed ? (
                <>
                  <ChevronDown className="h-4 w-4" />
                </>
              ) : (
                <>
                  <ChevronUp className="h-4 w-4" />
                </>
              )}
            </Button>
          </CollapsibleTrigger>
        </div>
        <CollapsibleContent>{formContent}</CollapsibleContent>
      </Card>
    </Collapsible>
  );
}
