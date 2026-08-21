// DataTableDateRangeFilter.tsx
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { Cross2Icon } from "@radix-ui/react-icons";
import { type Column } from "@tanstack/react-table";
import { format } from "date-fns";
import { vi } from "date-fns/locale";
import { CalendarIcon } from "lucide-react";
import * as React from "react";

interface DataTableDateRangeFilterProps<TData> {
  column: Column<TData, unknown>;
  title: string;
}

export function DataTableDateRangeFilter<TData>({
  column,
  title,
}: DataTableDateRangeFilterProps<TData>) {
  const [startDate, setStartDate] = React.useState<string>("");
  const [endDate, setEndDate] = React.useState<string>("");

  const handleApplyFilter = () => {
    const filterValue = startDate && endDate ? `${startDate}|${endDate}` : "";
    column.setFilterValue(filterValue);
  };

  const handleClearFilter = () => {
    setStartDate("");
    setEndDate("");
    column.setFilterValue("");
  };

  return (
    <div className="flex items-center space-x-2">
      <Popover>
        <PopoverTrigger asChild>
          <Button variant="outline" className="h-8">
            <CalendarIcon className="mr-2 h-4 w-4" />
            {title}
            {(startDate || endDate) && (
              <span className="ml-2">
                {startDate &&
                  format(new Date(startDate), "dd/MM/yyyy", { locale: vi })}
                {startDate && endDate && " - "}
                {endDate &&
                  format(new Date(endDate), "dd/MM/yyyy", { locale: vi })}
              </span>
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-4">
          <div className="space-y-4">
            <div className="flex gap-2">
              <div>
                <label className="text-sm font-medium">Từ ngày</label>
                <Calendar
                  mode="single"
                  selected={startDate ? new Date(startDate) : undefined}
                  onSelect={(date) =>
                    setStartDate(date ? format(date, "yyyy-MM-dd") : "")
                  }
                  className="rounded-md border"
                  locale={vi}
                  captionLayout="dropdown"
                />
              </div>
              <div>
                <label className="text-sm font-medium">Đến ngày</label>
                <Calendar
                  mode="single"
                  selected={endDate ? new Date(endDate) : undefined}
                  onSelect={(date) =>
                    setEndDate(date ? format(date, "yyyy-MM-dd") : "")
                  }
                  className="rounded-md border"
                  locale={vi}
                  captionLayout="dropdown"
                />
              </div>
            </div>
            <div className="flex justify-end space-x-2">
              <Button
                variant="outline"
                size="sm"
                onClick={handleClearFilter}
                disabled={!startDate && !endDate}
              >
                <Cross2Icon className="mr-2 h-4 w-4" />
                Xóa
              </Button>
              <Button variant="default" size="sm" onClick={handleApplyFilter}>
                Áp dụng
              </Button>
            </div>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  );
}
