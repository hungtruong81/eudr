"use client";

import { SettingOutlined } from "@ant-design/icons";
import type { TableProps, MenuProps } from "antd";
import { Button, Checkbox, Popover, Table, Dropdown } from "antd";
import type { ColumnType } from "antd/es/table";
import { useEffect, useMemo, useState } from "react";
import dayjs from "dayjs";

export type CustomColumnTypeTable<T> = ColumnType<T> & {
  type?: "number" | "text" | "date";
  autoFilter?: boolean;
};

export interface CustomTableProps<T> extends Omit<TableProps<T>, "columns"> {
  tableId: string;
  columns: CustomColumnTypeTable<T>[];
  hideToolbar?: boolean;
  contextMenuItems?: (record: T) => MenuProps["items"];
  autoFilter?: boolean;
}

const getRecordValue = (
  record: any,
  dataIndex: string | string[] | undefined,
) => {
  if (!dataIndex) return undefined;
  if (Array.isArray(dataIndex)) {
    return dataIndex.reduce(
      (acc, key) => (acc && acc[key] !== undefined ? acc[key] : undefined),
      record,
    );
  }
  return record[dataIndex];
};

export function CustomTable<T extends object>({
  tableId,
  columns,
  hideToolbar = false,
  contextMenuItems,
  autoFilter = true,
  dataSource,
  ...restProps
}: CustomTableProps<T>) {
  const processedColumns = useMemo(() => {
    return columns.map((col) => {
      let newCol = { ...col };

      // 1. Xử lý format number
      if (newCol.type === "number") {
        newCol = {
          align: "right" as const,
          ...newCol,
          render: newCol.render
            ? newCol.render
            : (text: any) => {
                if (text === null || text === undefined || text === "")
                  return "";
                const num = Number(text);
                if (!isNaN(num)) {
                  return new Intl.NumberFormat("vi-VN").format(num);
                }
                return text;
              },
        };
      }

      // 2. Xử lý format hiển thị date trên Cell (tùy chọn nhưng khuyên dùng để đồng bộ)
      if (newCol.type === "date") {
        newCol = {
          ...newCol,
          render: newCol.render
            ? newCol.render
            : (text: any) => {
                if (!text) return "";
                return dayjs(text).format("DD/MM/YYYY"); // Định dạng ngày tùy ý bạn
              },
        };
      }

      // 3. Xử lý tự động filter
      if (autoFilter && !newCol.filters && newCol.dataIndex) {
        const rawValues = (dataSource || []).map((record) =>
          getRecordValue(record, newCol.dataIndex as string | string[]),
        );

        const uniqueValues = Array.from(new Set(rawValues)).filter(
          (val) => val !== undefined && val !== null && val !== "",
        );

        if (uniqueValues.length > 0) {
          newCol.filters = uniqueValues.map((val) => {
            // Xử lý format text hiển thị trong list filter nếu là ngày
            let filterText = String(val);
            if (newCol.type === "date" && val) {
              filterText = dayjs(val as string | number | Date).format(
                "DD/MM/YYYY",
              );
            }

            return {
              text: filterText,
              value: val as string | number | boolean,
            };
          });

          newCol.onFilter = (value, record) => {
            const recordVal = getRecordValue(
              record,
              newCol.dataIndex as string | string[],
            );
            return recordVal === value;
          };

          newCol.filterSearch = true;
        }
      }

      return newCol;
    });
  }, [columns, autoFilter, dataSource]);

  const allColumnKeys = useMemo(() => {
    return processedColumns
      .map((col: any) => (col.key || col.dataIndex) as string)
      .filter(Boolean);
  }, [processedColumns]);

  const [visibleColumns, setVisibleColumns] = useState<string[]>(allColumnKeys);
  const [isLoaded, setIsLoaded] = useState(false);
  const [popupInfo, setPopupInfo] = useState<{
    record: T;
    x: number;
    y: number;
  } | null>(null);

  useEffect(() => {
    if (!popupInfo) return;

    const handleHide = () => setPopupInfo(null);

    window.addEventListener("click", handleHide);
    window.addEventListener("contextmenu", handleHide);
    window.addEventListener("scroll", handleHide, true);

    return () => {
      window.removeEventListener("click", handleHide);
      window.removeEventListener("contextmenu", handleHide);
      window.removeEventListener("scroll", handleHide, true);
    };
  }, [popupInfo]);

  useEffect(() => {
    try {
      const stored = localStorage.getItem(`table-config-${tableId}`);
      if (stored) {
        const parsed = JSON.parse(stored);
        if (Array.isArray(parsed)) {
          const validKeys = parsed.filter((key) => allColumnKeys.includes(key));
          setVisibleColumns(validKeys.length > 0 ? validKeys : allColumnKeys);
        }
      }
    } catch (e) {
      console.error("Failed to parse table config from local storage", e);
    }
    setIsLoaded(true);
  }, [tableId, allColumnKeys]);

  useEffect(() => {
    if (isLoaded) {
      localStorage.setItem(
        `table-config-${tableId}`,
        JSON.stringify(visibleColumns),
      );
    }
  }, [visibleColumns, tableId, isLoaded]);

  const onVisibleColumnsChange = (checkedValues: string[]) => {
    setVisibleColumns(checkedValues);
  };

  const onCheckAllChange = (e: any) => {
    setVisibleColumns(e.target.checked ? allColumnKeys : []);
  };

  const filteredColumns = useMemo(() => {
    return processedColumns.filter((col: any) => {
      const key = (col.key || col.dataIndex) as string;
      if (!key) return true;
      return visibleColumns.includes(key);
    });
  }, [processedColumns, visibleColumns]);

  const checkboxOptions = useMemo(() => {
    return processedColumns
      .filter((col: any) => col.key || col.dataIndex)
      .map((col: any) => {
        const key = (col.key || col.dataIndex) as string;
        let title = key;
        if (typeof col.title === "string") {
          title = col.title;
        } else {
          title = `Cột: ${key}`;
        }
        return { label: title, value: key };
      });
  }, [processedColumns]);

  // eslint-disable-next-line react-hooks/exhaustive-deps
  const popoverContent = (
    <div className="flex flex-col gap-2 p-1 min-w-[200px]">
      <div className="border-b pb-2 mb-2">
        <Checkbox
          indeterminate={
            visibleColumns.length > 0 &&
            visibleColumns.length < allColumnKeys.length
          }
          checked={visibleColumns.length === allColumnKeys.length}
          onChange={onCheckAllChange}>
          <span className="font-semibold">Hiển thị tất cả</span>
        </Checkbox>
      </div>
      <Checkbox.Group
        value={visibleColumns}
        onChange={onVisibleColumnsChange}
        className="flex flex-col gap-2 max-h-[300px] overflow-y-auto">
        {checkboxOptions.map((opt) => (
          <Checkbox key={opt.value} value={opt.value}>
            {opt.label}
          </Checkbox>
        ))}
      </Checkbox.Group>
    </div>
  );

  const onRowHandler = (record: T, rowIndex?: number) => {
    const defaultRowProps = restProps.onRow
      ? restProps.onRow(record, rowIndex)
      : {};
    return {
      ...defaultRowProps,
      onContextMenu: (event: React.MouseEvent) => {
        if (contextMenuItems) {
          event.preventDefault();
          event.stopPropagation();
          setPopupInfo({ record, x: event.clientX, y: event.clientY });
        }
        defaultRowProps.onContextMenu?.(event);
      },
    };
  };

  const columnsWithSetting = useMemo(() => {
    return [
      ...filteredColumns,
      {
        key: "__column_setting__",
        width: 50,
        align: "center" as const,
        fixed: "right" as const,
        title: (
          <Popover
            content={popoverContent}
            title="Tùy chỉnh cột hiển thị"
            trigger="click"
            placement="bottomRight">
            <Button
              type="text"
              icon={<SettingOutlined />}
              title="Tùy chỉnh cột"
            />
          </Popover>
        ),
        render: () => null,
      },
    ];
  }, [filteredColumns, popoverContent]);

  return (
    <div className="flex flex-col w-full gap-2 relative h-full overflow-hidden">
      <Table<T>
        dataSource={dataSource}
        columns={columnsWithSetting as any}
        scroll={{
          ...restProps.scroll,
        }}
        locale={{
          filterConfirm: "Xác nhận",
          filterReset: "Đặt lại",
          filterSearchPlaceholder: "Tìm kiếm...",
          filterEmptyText: "Không có dữ liệu",
          emptyText: "Không có dữ liệu",
          ...restProps.locale,
        }}
        {...restProps}
        onRow={onRowHandler}
      />
      {popupInfo && contextMenuItems && (
        <Dropdown
          key={`${popupInfo.x}-${popupInfo.y}`}
          menu={{ items: contextMenuItems(popupInfo.record) }}
          open={true}
          onOpenChange={(open) => {
            if (!open) setPopupInfo(null);
          }}>
          <div
            style={{
              position: "fixed",
              left: popupInfo.x,
              top: popupInfo.y,
              width: 1,
              height: 1,
              zIndex: 9999,
              pointerEvents: "none",
            }}
          />
        </Dropdown>
      )}
    </div>
  );
}

export default CustomTable;
