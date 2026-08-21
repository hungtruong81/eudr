"use client";

import * as React from "react";
import Link from "next/link";
import type {
  DataTableFilterableColumn,
  DataTableSearchableColumn,
} from "@/types/table";
import { Cross2Icon, PlusCircledIcon, TrashIcon } from "@radix-ui/react-icons";
import type { Table } from "@tanstack/react-table";

import { cn } from "@/lib/utils";
import { Button, buttonVariants } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { DataTableFacetedFilter } from "@/components/data-table/data-table-faceted-filter";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { Calendar } from "@/components/ui/calendar";
import { Calendar as CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { vi } from "date-fns/locale";
import { DateRange } from "react-day-picker";

interface DataTableToolbarProps<TData> {
  table: Table<TData>;
  filterableColumns?: DataTableFilterableColumn<TData>[];
  searchableColumns?: DataTableSearchableColumn<TData>[];
  newRowLink?: string;
  columnLabels?: Record<string, string>;
  deleteRowsAction?: React.MouseEventHandler<HTMLButtonElement>;
  dateRangeFilterId?: { start: string; end: string };
}

export function DataTableToolbar<TData>({
  table,
  filterableColumns = [],
  searchableColumns = [],
  columnLabels,
  newRowLink,
  deleteRowsAction,
  dateRangeFilterId,
}: DataTableToolbarProps<TData>) {
  const isFiltered = table.getState().columnFilters.length > 0;
  const [isPending, startTransition] = React.useTransition();
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const handleGlobalSearch = React.useCallback(
    (value: string) => {
      searchableColumns.forEach((column) => {
        const columnId = String(column.id);
        table.getColumn(columnId)?.setFilterValue(value);
      });

      const params = new URLSearchParams(searchParams);
      if (value) {
        params.set("search", value);
      } else {
        params.delete("search");
      }
      router.push(`${pathname}?${params.toString()}`);
    },
    [searchableColumns, table, pathname, router, searchParams]
  );

  const currentSearchValue =
    searchableColumns.length > 0
      ? (table
          .getColumn(String(searchableColumns[0].id))
          ?.getFilterValue() as string) ?? ""
      : "";

  const [dateRange, setDateRange] = React.useState<DateRange | undefined>(
    () => {
      if (!dateRangeFilterId) return undefined;
      const start = searchParams.get(dateRangeFilterId.start);
      const end = searchParams.get(dateRangeFilterId.end);
      return start || end
        ? {
            from: start ? new Date(start) : undefined,
            to: end ? new Date(end) : undefined,
          }
        : undefined;
    }
  );

  const updateDateRange = (range: DateRange | undefined) => {
    setDateRange(range);
    if (!dateRangeFilterId) return;
    const params = new URLSearchParams(searchParams);
    if (range?.from) {
      params.set(dateRangeFilterId.start, format(range.from, "yyyy-MM-dd"));
    } else {
      params.delete(dateRangeFilterId.start);
    }
    if (range?.to) {
      params.set(dateRangeFilterId.end, format(range.to, "yyyy-MM-dd"));
    } else {
      params.delete(dateRangeFilterId.end);
    }
    router.push(`${pathname}?${params.toString()}`);
  };

  return (
    <div className="flex w-full flex-wrap gap-2 items-center justify-between p-1">
      <div className="flex flex-wrap items-center gap-2 flex-1 min-w-[200px]">
        {searchableColumns.length > 0 && (
          <Input
            placeholder="Tìm kiếm..."
            value={currentSearchValue}
            onChange={(event) => handleGlobalSearch(event.target.value)}
            className="h-8 w-[150px] sm:w-[200px] lg:w-[250px] focus-visible:ring-0 focus-visible:ring-offset-0"
          />
        )}

        {filterableColumns.map(
          (column) =>
            table.getColumn(column.id ? String(column.id) : "") && (
              <DataTableFacetedFilter
                key={String(column.id)}
                column={table.getColumn(column.id ? String(column.id) : "")}
                title={column.title}
                options={column.options}
              />
            )
        )}

        {dateRangeFilterId && (
          <Popover>
            <PopoverTrigger asChild>
              <Button
                variant="outline"
                className={cn(
                  "h-8 px-3 justify-start font-normal",
                  !dateRange?.from && !dateRange?.to && "text-muted-foreground"
                )}
              >
                <CalendarIcon className="mr-2 h-4 w-4" />
                {dateRange?.from ? (
                  dateRange.to ? (
                    <>
                      {format(dateRange.from, "dd/MM/yyyy", { locale: vi })} -{" "}
                      {format(dateRange.to, "dd/MM/yyyy", { locale: vi })}
                    </>
                  ) : (
                    format(dateRange.from, "dd/MM/yyyy", { locale: vi })
                  )
                ) : (
                  "Chọn ngày"
                )}
              </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
              <Calendar
                mode="range"
                selected={dateRange}
                onSelect={(range) => updateDateRange(range)}
                locale={vi}
              />
            </PopoverContent>
          </Popover>
        )}

        {(isFiltered || dateRangeFilterId) && (
          <Button
            aria-label="Reset filters"
            variant="ghost"
            className="h-8 px-2 lg:px-3"
            onClick={() => {
              table.resetColumnFilters();
              if (dateRangeFilterId) {
                updateDateRange(undefined);
              }
            }}
          >
            Xóa lọc
            <Cross2Icon className="ml-2 size-4" aria-hidden="true" />
          </Button>
        )}
      </div>

      <div className="flex items-center space-x-2 flex-shrink-0">
        {deleteRowsAction && table.getSelectedRowModel().rows.length > 0 ? (
          <Button
            aria-label="Delete selected rows"
            variant="outline"
            size="sm"
            className="h-8"
            onClick={(event) => {
              startTransition(() => {
                table.toggleAllPageRowsSelected(false);
                deleteRowsAction(event);
              });
            }}
            disabled={isPending}
          >
            <TrashIcon className="mr-2 size-4" aria-hidden="true" />
            Xóa
          </Button>
        ) : newRowLink ? (
          <Link aria-label="Create new row" href={newRowLink}>
            <div
              className={cn(
                buttonVariants({
                  variant: "outline",
                  size: "sm",
                  className: "h-8",
                })
              )}
            >
              <PlusCircledIcon className="mr-2 size-4" aria-hidden="true" />
              Thêm mới
            </div>
          </Link>
        ) : null}
      </div>
    </div>
  );
}
