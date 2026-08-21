import { DeleteOutlined, EditOutlined } from "@ant-design/icons";
import { Space, Tag } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { ICustomer } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { CRUD } from "@/types/permission-context";
import { useTranslations } from "next-intl";

interface CustomerTableProps {
  data: ICustomer[];
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: ICustomer) => void;
  onDelete: (record: ICustomer) => void;
  deletingRecord?: string | null;
  permissions: CRUD;
}

const CustomerTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  deletingRecord,
  permissions,
}: CustomerTableProps) => {
  const t = useTranslations("Customer");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");
  const columns: CustomColumnTypeTable<ICustomer>[] = [
    {
      title: t("customer_code"),
      dataIndex: "customer_code",
      render: (val) => val?.toUpperCase(),
    },
    {
      title: t("customer_name"),
      dataIndex: "customer_name",
    },
    {
      title: t("company_name"),
      dataIndex: "customer_company_name",
    },
    {
      title: tc("address"),
      dataIndex: "shipping_address",
    },
    {
      title: tc("email"),
      dataIndex: "customer_email",
    },
    {
      title: tc("phone_number"),
      dataIndex: "customer_phone",
    },
    {
      title: t("type"),
      dataIndex: "customer_type",
      render: (type: string) => (type === "individual" ? t("individual") : t("enterprise")),
    },
    {
      title: tc("status"),
      dataIndex: "status",
      render: (status: string) => {
        const color = status === "active" ? "green" : "red";
        return (
          <Tag color={color}>
            {status === "active" ? ts("active") : ts("inactive")}
          </Tag>
        );
      },
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      fixed: "right",
      align: "center",
      render: (_, record) => (
        <Space>
          {(permissions.full || permissions.update) && (
            <TooltipButton
              tooltip={tc("edit")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => onEdit(record)}
            />
          )}
          {(permissions.full || permissions.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tc("confirm_delete")}
              confirmDescription={t("confirm_delete_description", {
                name: record.customer_name,
                code: record.customer_code,
              })}
              tooltip={tc("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => onDelete(record)}
              loading={deletingRecord === record.customer_code}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<ICustomer>
      rowKey="customer_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="customer-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: 1500 }}
    />
  );
};

export default CustomerTable;
