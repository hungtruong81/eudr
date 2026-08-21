import { DeleteOutlined, EditOutlined } from "@ant-design/icons";
import { Space, Tag, Modal } from "antd";
import React from "react";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { ICompany } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { CRUD } from "@/types/permission-context";
import { useTranslations } from "next-intl";

interface CompanyTableProps {
  data: ICompany[] | undefined;
  loading?: boolean;
  pagination?: {
    current: number;
    pageSize: number;
    total: number;
  };
  onPageChange: (page: number, pageSize: number) => void;
  onEdit: (record: ICompany) => void;
  onDelete: (record: ICompany) => void;
  deletingCode?: string | null;
  permissions: CRUD;
}

const CompanyTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onEdit,
  onDelete,
  deletingCode,
  permissions,
}: CompanyTableProps) => {
  const t = useTranslations("Manage.Company");
  const tc = useTranslations("Common");

  const columns: CustomColumnTypeTable<ICompany>[] = [
    {
      title: t("company_code"),
      dataIndex: "company_code",
      fixed: "left",
      render: (val) => <span className="font-bold uppercase">{val}</span>,
    },
    {
      title: t("company_name"),
      dataIndex: "company_name",
    },
    {
      title: t("short_name"),
      dataIndex: "short_name",
    },
    {
      title: t("tax_code"),
      dataIndex: "tax_code",
    },
    {
      title: t("member_count"),
      dataIndex: "member_count",
      render: (val) => val || 0,
    },
    {
      title: tc("status"),
      dataIndex: "status",
      width: 120,
      render: (status: string) => {
        const color = status === "active" ? "green" : "red";
        return (
          <Tag color={color}>
            {status === "active" ? tc("status_active") : tc("status_inactive")}
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
              confirmTitle={tc("delete_confirm_title")}
              confirmDescription={t("delete_confirm", {
                name: record.company_name,
              })}
              tooltip={tc("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => onDelete(record)}
              loading={deletingCode === record.company_code}
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <CustomTable<ICompany>
      rowKey="company_id"
      columns={columns}
      dataSource={data}
      loading={loading}
      tableId="company-table"
      pagination={{
        current: pagination?.current || 1,
        pageSize: pagination?.pageSize || 10,
        total: pagination?.total || 0,
        showSizeChanger: true,
      }}
      onChange={(pagination) => {
        onPageChange(pagination.current || 1, pagination.pageSize || 10);
      }}
      scroll={{ x: 1000 }}
    />
  );
};

export default CompanyTable;
