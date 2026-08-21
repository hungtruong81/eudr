"use client";

import { HTMLAttributes, ReactNode } from "react";
import { Empty, Skeleton } from "antd";
import { cn } from "@/lib/utils";

// 1. Định nghĩa cấu trúc của một Cột
export interface ColumnDef<T> {
  header: string; // Tên cột hiển thị ở Header và Label mobile
  accessorKey?: keyof T; // Key để lấy dữ liệu từ object (nếu chỉ hiển thị text đơn giản)
  cell?: (item: T) => ReactNode; // Hàm render custom (dùng cho nút bấm, badge, avatar...)
  className?: HTMLAttributes<HTMLElement>["className"];
  headerClassName?: HTMLAttributes<HTMLElement>["className"];
  cellClassName?: HTMLAttributes<HTMLElement>["className"];
  hideOnMobile?: boolean; // Ẩn hoàn toàn trên mobile?
  hideLabelOnMobile?: boolean; // Ẩn label "Header:" trên mobile? (thường dùng cho Avatar/Actions)
}

interface SmartListViewProps<T> {
  data: T[] | undefined;
  columns: ColumnDef<T>[];
  pagination?: ReactNode;
  isLoading?: boolean;
  onRowClick?: (item: T) => void;
  emptyMessage?: string;
}

export function SmartListView<T>({
  data,
  columns,
  pagination,
  isLoading,
  onRowClick,
  emptyMessage = "Không có dữ liệu",
}: SmartListViewProps<T>) {
  // --- Layout Loading (Sử dụng Skeleton của Antd) ---
  if (isLoading) {
    return (
      <div className="flex flex-col space-y-3 w-full">
        <div className="hidden md:flex bg-gray-100 p-3 rounded-lg h-10 animate-pulse" />
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="bg-white border rounded-lg p-4 shadow-sm">
            <Skeleton active paragraph={{ rows: 1 }} title={{ width: "30%" }} />
          </div>
        ))}
      </div>
    );
  }

  // --- Layout Empty (Sử dụng Empty của Antd) ---
  if (!data || data.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12 border border-dashed rounded-lg bg-gray-50/50">
        <Empty description={emptyMessage} />
      </div>
    );
  }

  return (
    <div className="flex flex-col space-y-3 w-full">
      {/* --- DESKTOP HEADER --- */}
      <div className="hidden md:flex flex-row items-center bg-gray-100 text-gray-700 font-medium p-3 rounded-lg text-sm border border-gray-200">
        {columns.map((col, index) => (
          <div
            key={index}
            className={cn("px-2", col.className, col.headerClassName)}>
            {col.header}
          </div>
        ))}
      </div>

      {/* --- BODY (LIST) --- */}
      <div className="flex flex-col gap-3">
        {data.map((item, rowIndex) => (
          <div
            key={rowIndex}
            onClick={() => onRowClick?.(item)}
            className={cn(
              "group relative flex flex-col md:flex-row md:items-center bg-white border rounded-lg p-4 md:p-3 shadow-sm hover:shadow-md transition-all",
              onRowClick && "cursor-pointer hover:border-blue-400", // Cập nhật màu hover phù hợp với theme Antd
            )}>
            {columns.map((col, colIndex) => {
              // Logic lấy nội dung: Ưu tiên hàm cell(), nếu không có thì lấy theo accessorKey
              const content = col.cell
                ? col.cell(item)
                : col.accessorKey
                  ? (item[col.accessorKey] as ReactNode)
                  : null;

              if (col.hideOnMobile) {
                return (
                  <div
                    key={colIndex}
                    className={cn(
                      "hidden md:block px-2",
                      col.className,
                      col.cellClassName,
                    )}
                    title={typeof content === "string" ? content : undefined}>
                    {content}
                  </div>
                );
              }

              return (
                <div
                  key={colIndex}
                  className={cn(
                    // Mobile Styles
                    "flex flex-row justify-between items-center py-1 md:py-0 border-b border-gray-50 md:border-none last:border-0",
                    // Desktop Styles
                    "md:block md:px-2",
                    col.className,
                    col.cellClassName,
                  )}>
                  {/* Label trên Mobile */}
                  {!col.hideLabelOnMobile && (
                    <span className="text-xs font-semibold text-gray-500 uppercase md:hidden mr-2">
                      {col.header}
                    </span>
                  )}

                  {/* Nội dung chính */}
                  <div
                    className="text-sm text-gray-900 whitespace-normal break-words max-w-full"
                    title={typeof content === "string" ? content : undefined}>
                    {content}
                  </div>
                </div>
              );
            })}
          </div>
        ))}
      </div>

      {/* --- PAGINATION --- */}
      {pagination && <div className="mt-2">{pagination}</div>}
    </div>
  );
}
