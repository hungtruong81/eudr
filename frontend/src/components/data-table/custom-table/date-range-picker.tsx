"use client";

import * as React from "react";
import { format } from "date-fns";
import { CalendarIcon } from "lucide-react";
import { type DateRange } from "react-day-picker";

import { cn } from "@/lib/utils";
import { Button, type ButtonProps } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { usePathname, useSearchParams } from "next/navigation";

type PickerMode = "single" | "range";

interface FlexibleDatePickerProps
  extends React.ComponentPropsWithoutRef<typeof PopoverContent> {
  mode?: PickerMode;
  defaultDateRange?: DateRange;
  defaultDate?: Date;
  placeholder?: string;
  triggerVariant?: Exclude<ButtonProps["variant"], "destructive" | "link">;
  triggerSize?: Exclude<ButtonProps["size"], "icon">;
  triggerClassName?: string;
  shallow?: boolean;
  numberOfMonths?: number;
}

export function FlexibleDatePicker({
  mode = "range",
  defaultDateRange,
  defaultDate,
  placeholder = "Pick a date",
  triggerVariant = "outline",
  triggerSize = "default",
  triggerClassName,
  shallow = true,
  numberOfMonths,
  className,
  ...props
}: FlexibleDatePickerProps) {
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const from = searchParams?.get("from") ?? "";
  const to = searchParams?.get("to") ?? "";
  const singleDate = searchParams?.get("date") ?? "";

  const date = React.useMemo(() => {
    function parseDate(dateString: string | null) {
      if (!dateString) return undefined;
      const parsedDate = new Date(dateString);
      return isNaN(parsedDate.getTime()) ? undefined : parsedDate;
    }

    if (mode === "single") {
      return parseDate(singleDate) ?? defaultDate;
    }

    return {
      from: parseDate(from) ?? defaultDateRange?.from,
      to: parseDate(to) ?? defaultDateRange?.to,
    };
  }, [from, to, singleDate, defaultDateRange, defaultDate, mode]);

  const handleDateChange = React.useCallback(
    (newDate: Date | DateRange | undefined) => {
      const newParams = new URLSearchParams(searchParams?.toString());

      if (mode === "single") {
        if (newDate instanceof Date) {
          newParams.set("date", format(newDate, "yyyy-MM-dd"));
        } else {
          newParams.delete("date");
        }
        newParams.delete("from");
        newParams.delete("to");
      } else {
        const dateRange = newDate as DateRange | undefined;
        if (dateRange?.from) {
          newParams.set("from", format(dateRange.from, "yyyy-MM-dd"));
        } else {
          newParams.delete("from");
        }
        if (dateRange?.to) {
          newParams.set("to", format(dateRange.to, "yyyy-MM-dd"));
        } else {
          newParams.delete("to");
        }
        newParams.delete("date");
      }

      const newQueryString = newParams.toString();
      const url = `${pathname}${newQueryString ? `?${newQueryString}` : ""}`;
      window.history.pushState({}, "", url);
    },
    [mode, pathname, searchParams]
  );

  const defaultMonths = mode === "single" ? 1 : 2;
  const actualNumberOfMonths = numberOfMonths ?? defaultMonths;

  const renderSelectedDate = () => {
    if (mode === "single") {
      return date instanceof Date ? (
        format(date, "LLL dd, y")
      ) : (
        <span>{placeholder}</span>
      );
    }

    const dateRange = date as DateRange;
    if (dateRange?.from) {
      return dateRange.to ? (
        <>
          {format(dateRange.from, "LLL dd, y")} -{" "}
          {format(dateRange.to, "LLL dd, y")}
        </>
      ) : (
        format(dateRange.from, "LLL dd, y")
      );
    }
    return <span>{placeholder}</span>;
  };

  return (
    <div className="grid gap-2">
      <Popover>
        <PopoverTrigger asChild>
          <Button
            variant={triggerVariant}
            size={triggerSize}
            className={cn(
              "w-full justify-start gap-2 truncate text-left font-normal",
              !date && "text-muted-foreground",
              triggerClassName
            )}
          >
            <CalendarIcon className="size-4" />
            {renderSelectedDate()}
          </Button>
        </PopoverTrigger>
        <PopoverContent className={cn("w-auto p-0", className)} {...props}>
          {mode === "single" ? (
            <Calendar
              initialFocus
              mode="single"
              defaultMonth={date instanceof Date ? date : undefined}
              selected={date instanceof Date ? date : undefined}
              onSelect={handleDateChange as (date: Date | undefined) => void}
              numberOfMonths={actualNumberOfMonths}
            />
          ) : (
            <Calendar
              initialFocus
              mode="range"
              defaultMonth={(date as DateRange)?.from}
              selected={date as DateRange}
              onSelect={
                handleDateChange as (date: DateRange | undefined) => void
              }
              numberOfMonths={actualNumberOfMonths}
            />
          )}
        </PopoverContent>
      </Popover>
    </div>
  );
}
