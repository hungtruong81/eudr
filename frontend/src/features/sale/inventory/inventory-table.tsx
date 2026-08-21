import { Tag } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { IProductLotInventory } from "@/features/factory/lot/types";
import { useTranslations } from "next-intl";
import dayjs from "dayjs";

interface InventoryTableProps {
  data: IProductLotInventory[];
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
}

const InventoryTable = ({
  data,
  loading,
  pagination,
  onPageChange,
}: InventoryTableProps) => {
  const t = useTranslations("Inventory");
  const tStatus = useTranslations("Status");

  const columns: CustomColumnTypeTable<IProductLotInventory>[] = [
    {
      title: t("lot_code"),
      dataIndex: "product_lot_code",
      render: (val) => <span className="uppercase">{val}</span>,
    },
    {
      title: t("grade"),
      dataIndex: "lot_type",
      render: (val: string) => {
        const typeMap: Record<string, { label: string; color: string }> = {
          external: { label: tStatus("external"), color: "default" },
          internal: { label: tStatus("internal"), color: "success" },
        };
        const config = typeMap[val] || { label: val, color: "default" };
        return (
          <Tag color={config.color} className="capitalize">
            {config.label}
          </Tag>
        );
      },
    },
    {
      title: t("total_weight"),
      dataIndex: "total_weight",
      type: "number",
    },
    {
      title: t("total_blocks"),
      dataIndex: "total_blocks",
    },
    {
      title: t("production_date"),
      render: (_, record) => {
        const from = dayjs(record.production_date_from).format("DD/MM/YYYY");
        const to = dayjs(record.production_date_to).format("DD/MM/YYYY");
        return (
          <div className="flex flex-col text-xs">
            <span>{from}</span>
            <span className="text-[10px] opacity-50">-</span>
            <span>{to}</span>
          </div>
        );
      },
    },
    {
      title: t("factory_name"),
      dataIndex: "factory_name",
    },
    {
      title: t("eudr_type"),
      dataIndex: "eudr_type",
      render: (type: string) => {
        const isEudr = type === "eudr";
        return (
          <Tag color={isEudr ? "green" : "blue"}>
            {isEudr ? "EUDR" : "Non-EUDR"}
          </Tag>
        );
      },
    },
    {
      title: t("status"),
      dataIndex: "status",
      render: (status: string) => {
        const statusMap: Record<string, { label: string; color: string }> = {
          draft: { label: tStatus("draft"), color: "default" },
          confirmed: { label: tStatus("confirmed"), color: "success" },
          shipped: { label: tStatus("shipped"), color: "processing" },
          cancelled: { label: tStatus("cancelled"), color: "error" },
        };
        const config = statusMap[status] || { label: status, color: "default" };
        return (
          <Tag color={config.color} className="capitalize">
            {config.label}
          </Tag>
        );
      },
    },
  ];

  return (
    <CustomTable<IProductLotInventory>
      rowKey="product_lot_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="inventory-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: 1200 }}
    />
  );
};

export default InventoryTable;
