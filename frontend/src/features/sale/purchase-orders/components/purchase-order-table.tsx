import { Space, Tag } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { IPurchaseOrder } from "../types";
import { formatVnCurrency } from "@/lib/utils";
import { CRUD } from "@/types/permission-context";
import { useTranslations } from "next-intl";

interface PurchaseOrderTableProps {
  data: IPurchaseOrder[] | undefined;
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  permissions: CRUD;
}

const PurchaseOrderTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  permissions,
}: PurchaseOrderTableProps) => {
  const t = useTranslations("Sales.purchase_order");
  const tStatus = useTranslations("Status");

  const columns: CustomColumnTypeTable<IPurchaseOrder>[] = [
    {
      title: t("order_code"),
      dataIndex: "sale_order_code",
      fixed: "left",
      render: (val) => <span className="font-bold uppercase">{val}</span>,
    },
    {
      title: t("order_date"),
      dataIndex: "order_date",
      render: (val) => (val ? new Date(val).toLocaleDateString("vi-VN") : "-"),
    },
    {
      title: t("customer"),
      dataIndex: "customer_name",
      render: (_, record) => (
        <div>
          <div>{record.customer_name || record.buyer_user_name}</div>
          <div className="text-xs text-gray-400">
            {record.customer_phone} {record.customer_email}
          </div>
        </div>
      ),
    },
    {
      title: t("source"),
      dataIndex: "order_source_type",
      render: (val) => {
        switch (val) {
          case "warehouse":
            return t("warehouse");
          case "transaction_ticket":
            return t("transaction_ticket");
          case "product_lot":
            return t("product_lot");
          default:
            return val;
        }
      },
    },
    {
      title: t("total_amount"),
      dataIndex: "total_amount",
      align: "right",
      render: (val) => formatVnCurrency(val),
    },
    {
      title: t("status"),
      dataIndex: "status",
      render: (status: string) => {
        let color = "blue";
        let text = status;
        switch (status) {
          case "draft":
            color = "blue";
            text = tStatus("draft");
            break;
          case "approved":
            color = "green";
            text = tStatus("approved");
            break;
          case "allocated":
            color = "cyan";
            text = tStatus("allocated");
            break;
          case "shipping":
            color = "orange";
            text = tStatus("delivering");
            break;
          case "closed":
            color = "gray";
            text = tStatus("closed");
            break;
          case "cancelled":
            color = "red";
            text = tStatus("cancelled");
            break;
          case "pending":
            color = "orange";
            text = tStatus("pending");
            break;
          default:
            text = tStatus(status as any);
        }
        return <Tag color={color}>{text}</Tag>;
      },
    },
  ];

  return (
    <CustomTable<IPurchaseOrder>
      rowKey="sale_order_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="purchase-order-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: 1300 }}
    />
  );
};

export default PurchaseOrderTable;
