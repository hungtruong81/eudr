import {
  CheckOutlined,
  CloseOutlined,
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
} from "@ant-design/icons";
import { Space, Tag, Modal, Button } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { IOrder } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { formatVnCurrency } from "@/lib/utils";
import { CRUD } from "@/types/permission-context";
import { useTranslations, useLocale } from "next-intl";

interface OrderTableProps {
  data: IOrder[] | undefined;
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: IOrder) => void;
  onDelete: (record: IOrder) => void;
  onApprove: (record: IOrder) => void;
  onCancel: (record: IOrder) => void;
  onView: (record: IOrder) => void;
  deletingCode?: string | null;
  processingCode?: string | null;
  permissions: CRUD;
}

const OrderTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  onApprove,
  onCancel,
  onView,
  deletingCode,
  processingCode,
  permissions,
}: OrderTableProps) => {
  const ts = useTranslations("Sales");
  const tst = useTranslations("Status");
  const tc = useTranslations("Common");
  const tr = useTranslations("Register");
  const locale = useLocale();

  const columns: CustomColumnTypeTable<IOrder>[] = [
    {
      title: ts("order_code"),
      dataIndex: "sale_order_code",
      fixed: "left",
      render: (val) => <span className="font-bold uppercase">{val}</span>,
    },
    {
      title: ts("order_date"),
      dataIndex: "order_date",
      render: (val) =>
        val ? new Date(val).toLocaleDateString(locale === "vi" ? "vi-VN" : "en-US") : "-",
    },
    {
      title: ts("buyer_type"),
      dataIndex: "buyer_type",
      render: (_, record) =>
        record?.customer_name?.trim()
          ? tr("customers")
          : record?.buyer_company_name
            ? tr("purchaser")
            : "",
    },
    {
      title: ts("customer"),
      dataIndex: "customer_name",
      render: (_, record) => {
        if (record?.customer_name?.trim()) {
          return (
            <div>
              <div>{record.customer_name}</div>
              <div className="text-xs text-gray-400">
                {record.customer_phone} | {record.customer_email}
              </div>
            </div>
          );
        }
        return (
          <div>
            <div>{record.buyer_company_name}</div>
            <div className="text-xs text-gray-400">
              {record.buyer_company_code} | {record.buyer_user_name}
            </div>
          </div>
        );
      },
    },
    {
      title: ts("source"),
      dataIndex: "order_source_type",
      render: (val) =>
        val === "warehouse" ? ts("warehouse") : ts("purchase_order.transaction_ticket"),
    },
    {
      title: ts("total_amount"),
      dataIndex: "total_amount",
      align: "right",
      render: (val) => formatVnCurrency(val),
    },
    {
      title: ts("status"),
      dataIndex: "status",
      render: (status: string) => {
        let color = "blue";
        let text = status;
        switch (status) {
          case "draft":
            color = "blue";
            text = tst("draft");
            break;
          case "approved":
            color = "green";
            text = tst("approved");
            break;
          case "allocated":
            color = "cyan";
            text = tst("allocated");
            break;
          case "shipping":
            color = "orange";
            text = tst("delivering");
            break;
          case "closed":
            color = "gray";
            text = tst("closed");
            break;
          case "cancelled":
            color = "red";
            text = tst("cancelled");
            break;
          case "pending":
            color = "orange";
            text = tst("pending");
            break;
        }
        return <Tag color={color}>{text}</Tag>;
      },
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      fixed: "right",
      align: "center",
      render: (_, record) => (
        <Space>
          <TooltipButton
            tooltip={tc("view_detail")}
            icon={<EyeOutlined />}
            onClick={() => onView(record)}
          />
          {record.status === "pending" && (
            <>
              {(permissions.full || permissions.update) && (
                <ConfirmTooltipButton
                  confirmTitle={ts("confirm_approve")}
                  confirmDescription={`${ts("confirm_approve")} "${record.sale_order_code?.toUpperCase()}"?`}
                  tooltip={ts("approve_order")}
                  type="primary"
                  icon={<CheckOutlined />}
                  onConfirm={() => onApprove(record)}
                  loading={processingCode === record.sale_order_code}
                />
              )}
              {(permissions.full || permissions.update) && (
                <ConfirmTooltipButton
                  confirmTitle={ts("confirm_cancel")}
                  confirmDescription={`${ts("confirm_cancel")} "${record.sale_order_code?.toUpperCase()}"?`}
                  tooltip={ts("cancel_order")}
                  danger
                  icon={<CloseOutlined />}
                  onConfirm={() => onCancel(record)}
                  loading={processingCode === record.sale_order_code}
                />
              )}
              {(permissions.full || permissions.update) && (
                <TooltipButton
                  tooltip={tc("edit")}
                  type="primary"
                  ghost
                  icon={<EditOutlined />}
                  onClick={() => onEdit(record)}
                />
              )}
            </>
          )}
          {(record.status === "pending" ||
            record.status === "draft" ||
            permissions.full ||
            permissions.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tc("confirm_delete")}
              confirmDescription={`${tc("confirm_delete")} "${record.sale_order_code?.toUpperCase()}"?`}
              tooltip={tc("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => onDelete(record)}
              loading={deletingCode === record.sale_order_code}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<IOrder>
      rowKey="sale_order_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="order-table"
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

export default OrderTable;
