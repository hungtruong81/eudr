"use client";

import {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from "@/components/ui/popover";
import { Calendar } from "@/components/ui/calendar";
import { Button } from "@/components/ui/button";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { vi } from "date-fns/locale";

interface Props {
  label: string;
  from?: Date;
  to?: Date;
  onChange: (from: Date, to: Date) => void;
}

export function DateRangePicker({ label, from, to, onChange }: Props) {
  const displayLabel =
    from && to
      ? `${format(from, "dd/MM/yyyy", { locale: vi })} - ${format(
          to,
          "dd/MM/yyyy",
          { locale: vi }
        )}`
      : label;

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="outline">
          <CalendarIcon className="mr-2 h-4 w-4" />
          {displayLabel}
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-auto p-0">
        <Calendar
          mode="range"
          selected={{ from, to }}
          onSelect={(range) => {
            const newFrom = range?.from || new Date();
            const newTo = range?.to || range?.from || new Date();
            onChange(newFrom, newTo);
          }}
          locale={vi}
          captionLayout="dropdown"
          className="rounded-md border"
          numberOfMonths={2}
        />
      </PopoverContent>
    </Popover>
  );
}
