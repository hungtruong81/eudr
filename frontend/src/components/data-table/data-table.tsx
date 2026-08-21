import * as React from "react";
import type {
  DataTableFilterableColumn,
  DataTableSearchableColumn,
} from "@/types/table";
import {
  flexRender,
  type ColumnDef,
  type Table as TanstackTable,
} from "@tanstack/react-table";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

import { DataTableFloatingBar } from "./data-table-floating-bar";
import { DataTablePagination } from "./data-table-pagination";
import { DataTableToolbar } from "./data-table-toolbar";

interface DataTableProps<TData, TValue> {
  dataTable: TanstackTable<TData>;
  columns: ColumnDef<TData, TValue>[];
  searchableColumns?: DataTableSearchableColumn<TData>[];
  filterableColumns?: DataTableFilterableColumn<TData>[];
  advancedFilter?: boolean;
  floatingBarContent?: React.ReactNode | null;
  columnLabels?: Record<string, string>;
  newRowLink?: string;
  deleteRowsAction?: React.MouseEventHandler<HTMLButtonElement>;
  dateRangeFilterId?: {
    start: string;
    end: string;
  };
}

export function DataTable<TData, TValue>({
  dataTable,
  columns,
  searchableColumns = [],
  filterableColumns = [],
  advancedFilter = false,
  columnLabels,
  newRowLink,
  floatingBarContent,
  deleteRowsAction,
  dateRangeFilterId,
}: DataTableProps<TData, TValue>) {
  return (
    <div className="space-y-4">
      <DataTableToolbar
        table={dataTable}
        filterableColumns={filterableColumns}
        searchableColumns={searchableColumns}
        deleteRowsAction={deleteRowsAction}
        columnLabels={columnLabels}
        newRowLink={newRowLink}
        dateRangeFilterId={dateRangeFilterId}
      />
      <div className="rounded-lg border border-green-200 dark:border-green-800 shadow-md overflow-hidden">
        <div className="overflow-auto">
          <Table className="w-full">
            <TableHeader className="bg-green-50 dark:bg-green-900/30 sticky top-0 z-4">
              {dataTable.getHeaderGroups().map((headerGroup) => (
                <TableRow
                  key={headerGroup.id}
                  className="border-b border-gray-200 dark:border-gray-700"
                >
                  {headerGroup.headers.map((header) => (
                    <TableHead
                      key={header.id}
                      className="text-gray-900 dark:text-gray-100 font-bold"
                      style={{}}
                    >
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                            header.column.columnDef.header,
                            header.getContext()
                          )}
                    </TableHead>
                  ))}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody>
              {dataTable.getRowModel().rows?.length ? (
                dataTable.getRowModel().rows.map((row, index) => (
                  <TableRow
                    key={row.id}
                    data-state={row.getIsSelected() && "selected"}
                    className="hover:bg-green-100 dark:hover:bg-green-800/50"
                  >
                    {row.getVisibleCells().map((cell) => (
                      <TableCell
                        key={cell.id}
                        className="text-gray-800 dark:text-gray-200 font-semibold"
                        style={{
                          minWidth: cell.column.id === "name" ? "50px" : "20px",
                          maxWidth:
                            cell.column.id === "name" ? "250px" : "150px",
                        }}
                      >
                        {flexRender(
                          cell.column.columnDef.cell,
                          cell.getContext()
                        )}
                      </TableCell>
                    ))}
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell
                    colSpan={columns.length}
                    className="h-24 text-center text-gray-500 dark:text-gray-400 italic"
                  >
                    No data
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
      </div>
      <div className="flex flex-col gap-2.5">
        <DataTablePagination table={dataTable} />
        {dataTable.getFilteredSelectedRowModel().rows.length > 0 &&
          floatingBarContent}
      </div>
    </div>
  );
}
